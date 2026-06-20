<?php

namespace App\Livewire\Admin\Vendor;

use App\Models\Vendor;
use App\Services\AccurateService;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin', ['title' => 'Kelola Vendor - ZedPOS'])]
class VendorManage extends Component
{
    use WithPagination;

    public $search = '';
    public $filterBusinessUnitId = '';
    public $isLoading = false;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    /**
     * SINKRONISASI VENDOR DARI ACCURATE ONLINE
     */
    public function syncVendors()
    {
        $this->isLoading = true;

        try {
            $service = app(AccurateService::class);

            $sources = [];
            if ($this->filterBusinessUnitId) {
                $bu = \App\Models\BusinessUnit::find($this->filterBusinessUnitId);
                if ($bu) $sources[] = $bu;
            } else {
                $sources = \App\Models\BusinessUnit::where('is_active', true)->get();
            }

            if (empty($sources)) {
                $this->dispatch('admin-alert', type: 'error', message: 'Tidak ada unit usaha yang dipilih atau aktif.');
                $this->isLoading = false;
                return;
            }

            $syncedCount = 0;

            foreach ($sources as $bu) {
                try {
                    $response = $service->getVendors($bu->code);

                    if (!empty($response)) {
                        foreach ($response as $vnd) {
                            $vendorNo = $vnd['vendorNo'] ?? '';

                            if ($vendorNo !== '') {
                                Vendor::updateOrCreate(
                                    [
                                        'vendor_no' => $vendorNo,
                                    ],
                                    [
                                        'accurate_vendor_id' => $vnd['id'],
                                        'database_source' => $bu->code,
                                        'vendor_name' => $vnd['name'],
                                        'email'       => $vnd['email'] ?? null,
                                        'phone'       => $vnd['mobilePhone'] ?? null,
                                    ]
                                );
                            } else {
                                Vendor::updateOrCreate(
                                    [
                                        'accurate_vendor_id' => $vnd['id'],
                                        'database_source' => $bu->code,
                                    ],
                                    [
                                        'vendor_no'   => $vendorNo,
                                        'vendor_name' => $vnd['name'],
                                        'email'       => $vnd['email'] ?? null,
                                        'phone'       => $vnd['mobilePhone'] ?? null,
                                    ]
                                );
                            }

                            $syncedCount++;
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("Gagal Sinkronisasi Vendor untuk BU {$bu->code}: " . $e->getMessage());
                }
            }

            if ($syncedCount > 0) {
                $this->dispatch('admin-alert', type: 'success', message: "Berhasil menyelaraskan $syncedCount data vendor dari semua unit usaha.");
            } else {
                $this->dispatch('admin-alert', type: 'warning', message: 'Tidak ada data vendor baru yang disinkronisasi.');
            }
        } catch (\Exception $e) {
            Log::error('Gagal Sinkronisasi Vendor: ' . $e->getMessage());
            $this->dispatch('admin-alert', type: 'error', message: 'Gagal sinkronisasi: ' . $e->getMessage());
        }

        $this->isLoading = false;
    }

    public function render()
    {
        $query = Vendor::orderBy('vendor_name', 'asc');

        // Multi-Tenant Filter
        if ($this->filterBusinessUnitId) {
            $bu = \App\Models\BusinessUnit::find($this->filterBusinessUnitId);
            if ($bu) {
                $query->where('database_source', $bu->code);
            }
        } else {
            $bu = \Illuminate\Support\Facades\Auth::user()->getActiveBusinessUnit();
            $buCode = $bu ? $bu->code : 'syihab';
            if ($buCode) {
                $query->where('database_source', $buCode);
            }
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('vendor_name', 'like', '%' . $this->search . '%')
                    ->orWhere('vendor_no', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%')
                    ->orWhere('phone', 'like', '%' . $this->search . '%');
            });
        }

        return view('livewire.admin.vendor.vendor-manage', [
            'vendorsList' => $query->paginate(10),
            'businessUnits' => \App\Models\BusinessUnit::where('is_active', true)->get()
        ]);
    }
}

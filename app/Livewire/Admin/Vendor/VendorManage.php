<?php

namespace App\Livewire\Admin\Vendor;

use App\Models\Vendor;
use App\Services\AccurateService;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin', ['title' => 'Kelola Vendor - TokoPun'])]
class VendorManage extends Component
{
    use WithPagination;

    public $search = '';
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
            $response = $service->getVendors();

            if (empty($response)) {
                $this->dispatch('admin-alert', type: 'error', message: 'Gagal mengambil data atau tidak ada data vendor di Accurate.');
                $this->isLoading = false;
                return;
            }

            $syncedCount = 0;

            foreach ($response as $vnd) {
                Vendor::updateOrCreate(
                    [
                        'accurate_vendor_id' => $vnd['id'],
                    ],
                    [
                        'vendor_no'   => $vnd['vendorNo'] ?? '',
                        'vendor_name' => $vnd['name'],
                        'email'       => $vnd['email'] ?? null,
                        'phone'       => $vnd['mobilePhone'] ?? null,
                    ]
                );
                $syncedCount++;
            }

            $this->dispatch('admin-alert', type: 'success', message: "Berhasil menyelaraskan $syncedCount data vendor dengan Accurate.");
        } catch (\Exception $e) {
            Log::error('Gagal Sinkronisasi Vendor: ' . $e->getMessage());
            $this->dispatch('admin-alert', type: 'error', message: 'Gagal sinkronisasi: ' . $e->getMessage());
        }

        $this->isLoading = false;
    }

    public function render()
    {
        $query = Vendor::orderBy('vendor_name', 'asc');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('vendor_name', 'like', '%' . $this->search . '%')
                    ->orWhere('vendor_no', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%')
                    ->orWhere('phone', 'like', '%' . $this->search . '%');
            });
        }

        return view('livewire.admin.vendor.vendor-manage', [
            'vendorsList' => $query->paginate(10)
        ]);
    }
}

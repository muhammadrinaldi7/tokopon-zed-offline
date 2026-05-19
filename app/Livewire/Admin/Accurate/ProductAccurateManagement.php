<?php

namespace App\Livewire\Admin\Accurate;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ProductAccurate;
use App\Services\AccurateService;
use Illuminate\Support\Facades\Log;

class ProductAccurateManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $activeTab = 'syihab'; // 'syihab' or 'second'
    public $isLoading = false;

    // Menangani perubahan search agar reset pagination
    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedActiveTab()
    {
        $this->resetPage();
        $this->search = '';
    }

    public function syncItems()
    {
        $this->isLoading = true;

        try {
            $service = app(AccurateService::class);
            $response = $service->getItemList($this->activeTab);
            Log::info('Response Accurate : ' . json_encode($response));
            $items = $response['d'] ?? $response;

            if (empty($items) || !is_array($items)) {
                $this->dispatch('notify', [
                    'type' => 'warning',
                    'message' => 'Data tidak ditemukan dari server Accurate.'
                ]);
                $this->isLoading = false;
                return;
            }

            $importedCount = 0;

            foreach ($items as $item) {
                if (!isset($item['no'])) continue;

                ProductAccurate::updateOrCreate(
                    [
                        'accurate_id' => $item['no'],
                        'database_source' => $this->activeTab,
                    ],
                    [
                        'item_no' => $item['no'] ?? null,
                        'name' => $item['modifierName'] ?? null,
                        'base_price' => $item['unitPrice'] ?? 0,
                        'stock' => $item['availableToSell'] ?? 0,
                        'raw_data' => $item,
                    ]
                );
                $importedCount++;
            }

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => "Berhasil sinkronisasi $importedCount data produk."
            ]);
        } catch (\Exception $e) {
            Log::error("Error Sync Accurate ({$this->activeTab}): " . $e->getMessage());
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Gagal sinkronisasi: ' . $e->getMessage()
            ]);
        }

        $this->isLoading = false;
    }

    public function render()
    {
        $query = ProductAccurate::where('database_source', $this->activeTab)
            ->orderBy('updated_at', 'desc');

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('item_no', 'like', '%' . $this->search . '%')
                    ->orWhere('accurate_id', 'like', '%' . $this->search . '%');
            });
        }

        return view('livewire.admin.accurate.product-accurate-management', [
            'products' => $query->paginate(15)
        ])->layout('layouts.admin');
    }
}

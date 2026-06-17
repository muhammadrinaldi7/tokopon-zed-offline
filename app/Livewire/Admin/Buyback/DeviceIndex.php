<?php

namespace App\Livewire\Admin\Buyback;

use App\Models\BuybackDevice;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin', ['title' => 'Daftar Perangkat Buyback'])]
class DeviceIndex extends Component
{
    public $showEditModal = false;
    public $editingDeviceId = null;
    public $editModelName = '';
    public $editBasePrice = 0;
    public $editRam = '';
    public $editStorage = '';
    public $editIsActive = true;

    // Sync Master Data
    public $showSyncAccurateModal = false;
    public $syncTargetBuId = null;
    public $syncKeyword = '';

    public function editDevice($id)
    {
        $device = BuybackDevice::find($id);
        if ($device) {
            $this->editingDeviceId = $id;
            $this->editModelName = $device->model_name;
            $this->editRam = $device->ram ?? '';
            $this->editStorage = $device->storage ?? '';
            $this->editBasePrice = $device->base_price;
            $this->editIsActive = $device->is_active;
            $this->showEditModal = true;
        }
    }

    public function updateDevice()
    {
        $this->validate([
            'editModelName' => 'required|string|max:255',
            'editBasePrice' => 'required|numeric|min:0',
            'editRam' => 'nullable|string|max:255',
            'editStorage' => 'nullable|string|max:255',
        ]);

        $device = BuybackDevice::find($this->editingDeviceId);
        if ($device) {
            $device->update([
                'model_name' => $this->editModelName,
                'ram' => $this->editRam,
                'storage' => $this->editStorage,
                'base_price' => $this->editBasePrice,
                'is_active' => $this->editIsActive,
            ]);

            // Auto re-assign tier berdasarkan harga baru
            $device->assignTierByPrice();
        }

        $this->showEditModal = false;
        $this->dispatch('toast', title: 'Berhasil', message: 'Data perangkat berhasil diperbarui.', type: 'success');
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->editingDeviceId = null;
    }
    public function syncTierDevice()
    {
        $devices = BuybackDevice::whereNotNull('base_price')->get();

        $count = 0;
        foreach ($devices as $device) {
            $device->assignTierByPrice();
            $count++;
        }

        $this->dispatch(
            'toast',
            title: 'Berhasil Disinkronisasi',
            message: "Berhasil meng-assign tier untuk {$count} perangkat berdasarkan harganya.",
            type: 'success'
        );
    }

    public function openSyncAccurateModal()
    {
        $this->syncTargetBuId = \App\Models\BusinessUnit::first()->id ?? null;
        $this->syncKeyword = '';
        $this->showSyncAccurateModal = true;
    }

    public function processSyncAccurate()
    {
        $query = \App\Models\ProductAccurate::query();

        if ($this->syncTargetBuId) {
            $query->where('business_unit_id', $this->syncTargetBuId);
            $query->where('categoryName', 'HandPhone');
        }

        if (!empty($this->syncKeyword)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->syncKeyword . '%')
                    ->orWhere('item_no', 'like', '%' . $this->syncKeyword . '%');
            });
        }

        $products = $query->get();
        $count = 0;

        foreach ($products as $prod) {
            // Jangan sinkronisasi jika sudah ada
            $existing = BuybackDevice::where('product_accurate_id', $prod->id)
                ->orWhere('model_name', $prod->name)
                ->first();

            if (!$existing) {
                // Coba cocokan Brand atau buat baru
                $brandId = null;
                $brandName = trim($prod->brandName);

                if (empty($brandName)) {
                    $brandName = 'Lainnya'; // Fallback jika kosong
                }

                // Cari brand atau buat baru jika belum ada berdasarkan slug
                $slug = \Illuminate\Support\Str::slug($brandName);
                $brand = \App\Models\Brand::firstOrCreate(
                    ['slug' => $slug],
                    ['name' => ucwords(strtolower($brandName))]
                );
                $brandId = $brand->id;

                BuybackDevice::create([
                    'brand_id'            => $brandId,
                    'product_accurate_id' => $prod->id,
                    'model_name'          => $prod->name,
                    'ram'                 => null,
                    'storage'             => '-', // Default
                    'base_price'          => $prod->base_price ?? 0,
                    'is_active'           => true,
                ]);

                $count++;
            }
        }

        // Auto assign tier untuk semua data
        $this->syncTierDeviceSilently();

        $this->showSyncAccurateModal = false;

        $this->dispatch(
            'toast',
            title: 'Sinkronisasi Selesai',
            message: "Berhasil menambahkan {$count} perangkat baru dari Master Data Accurate.",
            type: 'success'
        );
    }

    public function syncTierDeviceSilently()
    {
        $devices = BuybackDevice::whereNotNull('base_price')->where('base_price', '>', 0)->get();
        foreach ($devices as $device) {
            $device->assignTierByPrice();
        }
    }
    public function render()
    {
        $devices = BuybackDevice::with(['brand', 'tier'])
            ->orderBy('brand_id')
            ->orderBy('model_name')
            ->get();

        return view('livewire.admin.buyback.device-index', compact('devices'));
    }
}

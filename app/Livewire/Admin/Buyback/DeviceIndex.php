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
    public $editIsActive = true;

    public function editDevice($id)
    {
        $device = BuybackDevice::find($id);
        if ($device) {
            $this->editingDeviceId = $id;
            $this->editModelName = $device->model_name;
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
        ]);

        $device = BuybackDevice::find($this->editingDeviceId);
        if ($device) {
            $device->update([
                'model_name' => $this->editModelName,
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

    public function syncFromSecondCatalog()
    {
        $variants = \App\Models\SecondProductVariant::with('secondProduct')->get();
        $count = 0;

        foreach ($variants as $variant) {
            if (!$variant->secondProduct) continue;

            $existing = BuybackDevice::where('second_product_variant_id', $variant->id)->first();
            if (!$existing) {
                BuybackDevice::create([
                    'brand_id' => $variant->secondProduct->brand_id,
                    'second_product_variant_id' => $variant->id,
                    'model_name' => $variant->secondProduct->name,
                    'ram' => $variant->ram,
                    'storage' => $variant->storage,
                    'base_price' => 0, // Set 0 agar staf mengisi manual
                    'is_active' => true,
                ]);
                $count++;
            }
        }

        $this->dispatch(
            'toast',
            title: 'Sinkronisasi Berhasil',
            message: "Berhasil menambahkan {$count} perangkat baru dari Katalog Second.",
            type: 'success'
        );
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

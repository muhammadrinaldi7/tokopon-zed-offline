<?php

namespace App\Livewire\Admin\Buyback;

use App\Models\Brand;
use App\Models\BuybackDevice;
use App\Models\BuybackTier;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin', ['title' => 'Tambah Perangkat Buyback'])]
class DeviceForm extends Component
{
    // Status
    public $is_active = true;
    public $selected_tier_id = null;

    // Data HP
    public $selectedProducts = []; // Array of product_accurate_id => product_name

    // Pencarian Product Accurate
    public $searchProduct = '';
    public $product_accurate_id = null;
    public $productsAccurateList = [];
    public $target_business_unit_id = 2;

    public function mount()
    {
        $this->target_business_unit_id = config('settings.target_business_unit_id');

        if (request()->has('tier_id')) {
            $this->selected_tier_id = request()->query('tier_id');
        }
    }



    public function updatedSearchProduct()
    {
        if (strlen($this->searchProduct) >= 2) {
            $query = \App\Models\ProductAccurate::where(function ($q) {
                $q->where('name', 'like', '%' . $this->searchProduct . '%')
                    ->orWhere('item_no', 'like', '%' . $this->searchProduct . '%');
            })->whereNotIn('id', function ($query) {
                $query->select('product_accurate_id')->from('buyback_devices');
            });
            $query->where('business_unit_id', 2);


            $this->productsAccurateList = $query->limit(20)->get();
        } else {
            $this->productsAccurateList = [];
        }
    }

    public function selectProduct($id)
    {
        $product = \App\Models\ProductAccurate::find($id);
        if ($product && !isset($this->selectedProducts[$product->id])) {
            $this->selectedProducts[$product->id] = $product->name . ' (' . $product->item_no . ')';
        }

        $this->searchProduct = '';
        $this->productsAccurateList = [];
    }

    public function removeProduct($id)
    {
        unset($this->selectedProducts[$id]);
    }

    public function save()
    {
        $this->validate([
            'selectedProducts' => 'required|array|min:1',
            'selected_tier_id' => 'required|exists:buyback_tiers,id',
        ], [
            'selectedProducts.required' => 'Pilih minimal satu perangkat.',
            'selectedProducts.min'      => 'Pilih minimal satu perangkat.',
        ]);

        foreach ($this->selectedProducts as $productId => $productName) {
            // Cek apakah sudah ter-mapping di tier yang sama
            $exists = BuybackDevice::where('product_accurate_id', $productId)->exists();
            if (!$exists) {
                BuybackDevice::create([
                    'product_accurate_id' => $productId,
                    'buyback_tier_id'     => $this->selected_tier_id,
                    'is_active'           => $this->is_active,
                ]);
            } else {
                // Update mapping jika sudah ada
                BuybackDevice::where('product_accurate_id', $productId)->update([
                    'buyback_tier_id' => $this->selected_tier_id,
                    'is_active'       => $this->is_active,
                ]);
            }
        }

        $tier = BuybackTier::find($this->selected_tier_id);
        $count = count($this->selectedProducts);

        $tierMsg = "Sebanyak {$count} perangkat berhasil di-mapping ke Tier \"<strong>{$tier->name}</strong>\".";

        $this->dispatch(
            'toast',
            title: 'Mapping Berhasil',
            message: $tierMsg,
            type: 'success'
        );

        return $this->redirect(route('admin.buyback.mapped-devices.show', $tier->id), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.buyback.device-form', [
            'allTiers'     => BuybackTier::orderBy('name')->get(),
        ]);
    }
}

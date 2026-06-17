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
    // Data HP
    public $brand_id;
    public $model_name;
    public $ram;
    public $storage;
    public $base_price;
    public $is_active = true;
    
    // Pencarian Product Accurate
    public $searchProduct = '';
    public $product_accurate_id = null;
    public $productsAccurateList = [];
    public $target_business_unit_id = null;

    public function mount()
    {
        // Default ke BU pertama
        $this->target_business_unit_id = \App\Models\BusinessUnit::first()->id ?? null;
    }

    // Tier yang ter-detect dari base_price (read-only preview)
    public ?int $detected_tier_id    = null;
    public string $detected_tier_name = '';

    // ──────────────────────────────────────────────
    // Auto-detect tier saat base_price berubah
    // ──────────────────────────────────────────────

    public function updatedBasePrice($value)
    {
        $this->detected_tier_id   = null;
        $this->detected_tier_name = '';

        if (is_numeric($value) && $value > 0) {
            $tier = BuybackTier::findByPrice((float) $value);
            if ($tier) {
                $this->detected_tier_id   = $tier->id;
                $this->detected_tier_name = $tier->name;
            }
        }
    }

    public function updatedSearchProduct()
    {
        if (strlen($this->searchProduct) >= 2) {
            $query = \App\Models\ProductAccurate::where(function($q) {
                $q->where('name', 'like', '%' . $this->searchProduct . '%')
                  ->orWhere('item_no', 'like', '%' . $this->searchProduct . '%');
            });

            // Batasi hanya untuk Business Unit yang dipilih dari UI
            if ($this->target_business_unit_id) {
                $query->where('business_unit_id', $this->target_business_unit_id);
            }

            $this->productsAccurateList = $query->limit(20)->get();
        } else {
            $this->productsAccurateList = [];
        }
    }

    public function selectProduct($id)
    {
        $product = \App\Models\ProductAccurate::find($id);
        if ($product) {
            $this->product_accurate_id = $product->id;
            $this->model_name = $product->name;
            $this->searchProduct = $product->name . ' (' . $product->item_no . ')';
            $this->productsAccurateList = [];
            
            // Auto-assign brand based on brandName if exists
            if ($product->brandName) {
                $brand = Brand::where('name', 'like', '%' . $product->brandName . '%')->first();
                if ($brand) {
                    $this->brand_id = $brand->id;
                }
            }
            
            // You can also assign base_price if you want (Optional)
            if (empty($this->base_price) && $product->base_price > 0) {
                $this->base_price = $product->base_price;
                $this->updatedBasePrice($this->base_price);
            }
        }
    }

    public function save()
    {
        $this->validate([
            'brand_id'            => 'required|exists:brands,id',
            'product_accurate_id' => 'required|exists:product_accurates,id',
            'model_name'          => 'required|string|max:255',
            'storage'             => 'required|string',
            'base_price'          => 'required|numeric|min:0',
        ]);

        // Cari tier yang sesuai dengan harga
        $tier = BuybackTier::findByPrice((float) $this->base_price);

        $device = BuybackDevice::create([
            'brand_id'            => $this->brand_id,
            'product_accurate_id' => $this->product_accurate_id,
            'buyback_tier_id'     => $tier?->id,
            'model_name'          => $this->model_name,
            'ram'                 => $this->ram,
            'storage'             => $this->storage,
            'base_price'          => $this->base_price,
            'is_active'           => $this->is_active,
        ]);

        $tierMsg = $tier
            ? "Tier \"<strong>{$tier->name}</strong>\" berhasil di-assign otomatis."
            : 'Tidak ada tier yang cocok dengan harga ini. Harap cek konfigurasi tier.';

        $this->dispatch('toast',
            title:   'Perangkat Tersimpan',
            message: $tierMsg,
            type:    $tier ? 'success' : 'warning'
        );

        return $this->redirect(route('admin.buyback.index'), navigate: true);
    }

    public function render()
    {
        $detectedTier = $this->detected_tier_id
            ? BuybackTier::find($this->detected_tier_id)
            : null;

        return view('livewire.admin.buyback.device-form', [
            'brands'       => Brand::orderBy('name')->get(),
            'allTiers'     => BuybackTier::orderBy('min_price')->get(),
            'detectedTier' => $detectedTier,
        ]);
    }
}

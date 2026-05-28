<?php

namespace App\Livewire\Admin\Promo;

use App\Models\Brand;
use App\Models\Promo;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin', ['title' => 'Form Promo & Voucher'])]
class Form extends Component
{
    public ?Promo $promo = null;

    public $name;
    public $code;
    public $category = 'internal';
    public $brand_id;
    public $accurate_account_no;
    public $discount_type = 'fixed';
    public $discount_value;
    public $max_discount;
    public $start_date;
    public $end_date;
    public $is_active = true;

    // Conditions
    public $min_transaction_amount;
    public $min_qty;
    public $apply_to_all_items = true;
    
    // SKU Selection
    public $selected_skus = []; // array of ['sku' => '...', 'name' => '...']
    public $search_sku = '';
    public $sku_search_results = [];

    public function mount(Promo $promo = null)
    {
        if ($promo && $promo->exists) {
            $this->promo = $promo;
            $this->name = $promo->name;
            $this->code = $promo->code;
            $this->category = $promo->category;
            $this->brand_id = $promo->brand_id;
            $this->accurate_account_no = $promo->accurate_account_no;
            $this->discount_type = $promo->discount_type;
            $this->discount_value = $promo->discount_value;
            $this->max_discount = $promo->max_discount;
            $this->start_date = $promo->start_date ? $promo->start_date->format('Y-m-d') : null;
            $this->end_date = $promo->end_date ? $promo->end_date->format('Y-m-d') : null;
            $this->is_active = $promo->is_active;

            $this->min_transaction_amount = $promo->min_transaction_amount;
            $this->min_qty = $promo->min_qty;
            $this->apply_to_all_items = $promo->apply_to_all_items;

            // Load SKUs
            $this->selected_skus = $promo->skus->map(function($promo_sku) {
                // Try to find the name
                $pv = \App\Models\ProductVariant::where('sku', $promo_sku->sku)->first();
                if ($pv) {
                    $name = $pv->product->name . ' ' . $pv->color . ' ' . $pv->storage;
                } else {
                    $sv = \App\Models\SecondProductVariant::where('sku', $promo_sku->sku)->first();
                    $name = $sv ? ($sv->secondProduct->name . ' ' . $sv->color . ' ' . $sv->storage) : $promo_sku->sku;
                }
                return ['sku' => $promo_sku->sku, 'name' => $name];
            })->toArray();
        }
    }

    public function updatedSearchSku()
    {
        if (strlen($this->search_sku) < 2) {
            $this->sku_search_results = [];
            return;
        }

        $results = [];
        $query = $this->search_sku;

        $variants = \App\Models\ProductVariant::with('product')
            ->where('sku', 'like', "%{$query}%")
            ->orWhereHas('product', function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%");
            })
            ->take(5)->get();

        foreach($variants as $v) {
            $results[] = [
                'sku' => $v->sku,
                'name' => '[BARU] ' . $v->product->name . ' ' . $v->color . ' ' . $v->storage
            ];
        }

        $secondVariants = \App\Models\SecondProductVariant::with('secondProduct')
            ->where('sku', 'like', "%{$query}%")
            ->orWhereHas('secondProduct', function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%");
            })
            ->take(5)->get();

        foreach($secondVariants as $v) {
            $results[] = [
                'sku' => $v->sku,
                'name' => '[BEKAS] ' . $v->secondProduct->name . ' ' . $v->color . ' ' . $v->storage
            ];
        }

        $this->sku_search_results = collect($results)->filter(function($item) {
            // Filter out already selected
            return !collect($this->selected_skus)->contains('sku', $item['sku']);
        })->values()->toArray();
    }

    public function addSku($sku, $name)
    {
        if (!collect($this->selected_skus)->contains('sku', $sku)) {
            $this->selected_skus[] = ['sku' => $sku, 'name' => $name];
        }
        $this->search_sku = '';
        $this->sku_search_results = [];
    }

    public function removeSku($sku)
    {
        $this->selected_skus = array_filter($this->selected_skus, function($item) use ($sku) {
            return $item['sku'] !== $sku;
        });
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:promos,code,' . ($this->promo->id ?? 'NULL'),
            'category' => 'required|in:internal,brand',
            'brand_id' => 'required_if:category,brand|nullable|exists:brands,id',
            'accurate_account_no' => 'nullable|string|max:100',
            'discount_type' => 'required|in:fixed,percentage',
            'discount_value' => 'required|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'boolean',
            'min_transaction_amount' => 'nullable|numeric|min:0',
            'min_qty' => 'nullable|integer|min:1',
            'apply_to_all_items' => 'boolean',
        ], [
            'brand_id.required_if' => 'Brand wajib dipilih jika kategori adalah Sponsor Brand.',
        ]);

        if (!$this->apply_to_all_items && count($this->selected_skus) === 0) {
            $this->dispatch('toast', title: 'Error', message: 'Minimal pilih 1 produk/SKU jika promo tidak berlaku untuk semua barang.', type: 'error');
            return;
        }

        $data = [
            'name' => $this->name,
            'code' => $this->code,
            'category' => $this->category,
            'brand_id' => $this->category === 'brand' ? $this->brand_id : null,
            'accurate_account_no' => $this->accurate_account_no,
            'discount_type' => $this->discount_type,
            'discount_value' => $this->discount_value,
            'max_discount' => $this->discount_type === 'percentage' ? $this->max_discount : null,
            'start_date' => $this->start_date ?: null,
            'end_date' => $this->end_date ?: null,
            'is_active' => $this->is_active,
            'min_transaction_amount' => $this->min_transaction_amount ?: null,
            'min_qty' => $this->min_qty ?: null,
            'apply_to_all_items' => $this->apply_to_all_items,
        ];

        if ($this->promo && $this->promo->exists) {
            $this->promo->update($data);
            $message = 'Promo berhasil diperbarui.';
            $promo = $this->promo;
        } else {
            $promo = Promo::create($data);
            $message = 'Promo berhasil ditambahkan.';
        }

        // Sync SKUs
        $promo->skus()->delete();
        if (!$this->apply_to_all_items) {
            $skusToInsert = array_map(function($item) {
                return ['sku' => $item['sku']];
            }, $this->selected_skus);
            $promo->skus()->createMany($skusToInsert);
        }

        $this->dispatch('toast', title: 'Berhasil', message: $message, type: 'success');
        return $this->redirectRoute('admin.promos.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.promo.form', [
            'brands' => Brand::orderBy('name')->get()
        ]);
    }
}

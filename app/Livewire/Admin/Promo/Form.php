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
    public $description;
    public $code;
    public $category = 'internal';
    public $brand_id;
    public $accurate_account_no = '40.02.003';
    public $discount_type = 'fixed';
    public $discount_value;
    public $max_discount;
    public $start_date;
    public $end_date;
    public $is_active = true;

    // Promo v2 Features
    public $is_multiply = false;
    public $is_combinable = true;
    public $quota;
    public $selected_branches = [];
    public $selected_payment_methods = [];

    // Conditions
    public $min_transaction_amount;
    public $min_qty;
    public $apply_to_all_items = true;

    // Bundle
    public $is_bundle = false;
    public $bundle_max_qty;
    public $bundle_discount_type = 'fixed';
    public $bundle_discount_value;
    public $bundle_max_discount;

    // Target SKU Selection (Produk Utama)
    public $selected_skus = []; // array of ['sku' => '...', 'name' => '...']
    public $search_sku = '';
    public $sku_search_results = [];

    // Bundle SKU Selection (Produk Pendamping/Bundle)
    public $selected_bundle_skus = []; // array of ['sku' => '...', 'name' => '...']
    public $search_bundle_sku = '';
    public $bundle_sku_search_results = [];

    public function mount(?Promo $promo = null)
    {
        if ($promo && $promo->exists) {
            $this->promo = $promo;
            $this->name = $promo->name;
            $this->description = $promo->description;
            $this->code = $promo->code;
            $this->category = $promo->category;
            $this->brand_id = $promo->brand_id;
            $this->accurate_account_no = $promo->accurate_account_no;
            $this->discount_type = $promo->discount_type;
            $this->discount_value = $promo->discount_value !== null ? (int) $promo->discount_value : null;
            $this->max_discount = $promo->max_discount !== null ? (int) $promo->max_discount : null;
            $this->start_date = $promo->start_date ? $promo->start_date->format('Y-m-d') : null;
            $this->end_date = $promo->end_date ? $promo->end_date->format('Y-m-d') : null;
            $this->is_active = $promo->is_active;

            $this->is_multiply = $promo->is_multiply;
            $this->is_combinable = $promo->is_combinable;
            $this->quota = $promo->quota;
            $this->selected_branches = $promo->branches->pluck('id')->toArray();
            $this->selected_payment_methods = $promo->paymentMethods->pluck('id')->toArray();

            $this->min_transaction_amount = $promo->min_transaction_amount !== null ? (int) $promo->min_transaction_amount : null;
            $this->min_qty = $promo->min_qty;
            $this->apply_to_all_items = $promo->apply_to_all_items;
            $this->is_bundle = $promo->is_bundle;
            $this->bundle_max_qty = $promo->bundle_max_qty;
            $this->bundle_discount_type = $promo->bundle_discount_type ?: 'fixed';
            $this->bundle_discount_value = $promo->bundle_discount_value !== null ? (int) $promo->bundle_discount_value : null;
            $this->bundle_max_discount = $promo->bundle_max_discount !== null ? (int) $promo->bundle_max_discount : null;

            // Load SKUs (Produk Utama)
            $this->selected_skus = $promo->skus->map(function ($promo_sku) {
                return ['sku' => $promo_sku->sku, 'name' => $this->resolveSkuName($promo_sku->sku)];
            })->toArray();

            // Load Bundle SKUs (Produk Pendamping)
            $this->selected_bundle_skus = $promo->bundleSkus->map(function ($bundleSku) {
                return [
                    'sku' => $bundleSku->sku,
                    'name' => $this->resolveSkuName($bundleSku->sku),
                ];
            })->toArray();
        }
    }

    /**
     * Resolve SKU to a human-readable product name.
     */
    private function resolveSkuName(string $sku): string
    {
        $product = \App\Models\ProductAccurate::where('item_no', $sku)->first();
        if ($product) {
            return $product->name;
        }
        return $sku;
    }

    /**
     * Search variants by query string, excluding already selected SKUs.
     */
    private function searchVariants(string $query, array $excludeSkus): array
    {
        $results = [];
        $terms = array_filter(explode(' ', $query));

        $productsQuery = \App\Models\ProductAccurate::query();

        if (\Illuminate\Support\Facades\Auth::check() && \Illuminate\Support\Facades\Auth::user()->business_unit_id) {
            $productsQuery->where('business_unit_id', \Illuminate\Support\Facades\Auth::user()->business_unit_id);
        }

        foreach ($terms as $term) {
            $productsQuery->where(function ($q) use ($term) {
                $q->where('item_no', 'like', "%{$term}%")
                    ->orWhere('name', 'like', "%{$term}%");
            });
        }

        $products = $productsQuery->take(20)->get();

        foreach ($products as $p) {
            $results[] = [
                'sku' => $p->item_no,
                'name' => $p->name
            ];
        }

        return collect($results)->filter(function ($item) use ($excludeSkus) {
            return !in_array($item['sku'], $excludeSkus);
        })->values()->toArray();
    }

    // ─── Reward SKU (target produk yang dapat diskon) ───────────

    public function updatedSearchSku()
    {
        if (strlen($this->search_sku) < 2) {
            $this->sku_search_results = [];
            return;
        }
        $excludeSkus = collect($this->selected_skus)->pluck('sku')->toArray();
        $this->sku_search_results = $this->searchVariants($this->search_sku, $excludeSkus);
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
        $this->selected_skus = array_values(array_filter($this->selected_skus, function ($item) use ($sku) {
            return $item['sku'] !== $sku;
        }));
    }

    // ─── Bundle SKU (produk pendamping yang dapat diskon tambahan) ───

    public function updatedSearchBundleSku()
    {
        if (strlen($this->search_bundle_sku) < 2) {
            $this->bundle_sku_search_results = [];
            return;
        }
        $excludeSkus = collect($this->selected_bundle_skus)->pluck('sku')->toArray();
        $this->bundle_sku_search_results = $this->searchVariants($this->search_bundle_sku, $excludeSkus);
    }

    public function addBundleSku($sku, $name)
    {
        if (!collect($this->selected_bundle_skus)->contains('sku', $sku)) {
            $this->selected_bundle_skus[] = [
                'sku' => $sku,
                'name' => $name,
            ];
        }
        $this->search_bundle_sku = '';
        $this->bundle_sku_search_results = [];
    }

    public function removeBundleSku($sku)
    {
        $this->selected_bundle_skus = array_values(array_filter($this->selected_bundle_skus, function ($item) use ($sku) {
            return $item['sku'] !== $sku;
        }));
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
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
            'is_multiply' => 'boolean',
            'is_combinable' => 'boolean',
            'quota' => 'nullable|integer|min:1',
            'selected_branches' => 'array',
            'selected_payment_methods' => 'array',
            'min_transaction_amount' => 'nullable|numeric|min:0',
            'min_qty' => 'nullable|integer|min:1',
            'apply_to_all_items' => 'boolean',

            'is_bundle' => 'boolean',
            'bundle_discount_type' => 'nullable|in:fixed,percentage',
            'bundle_discount_value' => 'nullable|numeric|min:0',
            'bundle_max_discount' => 'nullable|numeric|min:0',
            'bundle_max_qty' => 'nullable|integer|min:1',
        ], [
            'brand_id.required_if' => 'Brand wajib dipilih jika kategori adalah Sponsor Brand.',
        ]);

        // Validasi: promo non-all-items harus punya SKU target
        if (!$this->apply_to_all_items && count($this->selected_skus) === 0) {
            $this->dispatch('toast', title: 'Error', message: 'Minimal pilih 1 produk/SKU sebagai target promo.', type: 'error');
            return;
        }

        // Validasi: promo bundle harus punya Bundle SKU dan Diskon Global
        if ($this->is_bundle) {
            if (count($this->selected_bundle_skus) === 0) {
                $this->dispatch('toast', title: 'Error', message: 'Promo bundling harus memiliki minimal 1 produk pendamping (bundle).', type: 'error');
                return;
            }
            if (empty($this->bundle_discount_value) || $this->bundle_discount_value <= 0) {
                $this->dispatch('toast', title: 'Error', message: 'Silakan isi Nilai Diskon untuk bundling.', type: 'error');
                return;
            }
        }

        $data = [
            'name' => $this->name,
            'business_unit_id' => $this->promo?->business_unit_id ?? \Illuminate\Support\Facades\Auth::user()->getActiveBusinessUnitId(),
            'description' => $this->description,
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
            'is_multiply' => $this->is_multiply,
            'is_combinable' => $this->is_combinable,
            'quota' => $this->quota ?: null,
            'min_transaction_amount' => $this->min_transaction_amount ?: null,
            'min_qty' => $this->min_qty ?: null,
            'apply_to_all_items' => $this->apply_to_all_items,
            'is_bundle' => $this->is_bundle,
            'bundle_discount_type' => $this->is_bundle ? $this->bundle_discount_type : null,
            'bundle_discount_value' => $this->is_bundle ? $this->bundle_discount_value : null,
            'bundle_max_discount' => ($this->is_bundle && $this->bundle_discount_type === 'percentage') ? $this->bundle_max_discount : null,
            'bundle_max_qty' => $this->is_bundle ? ($this->bundle_max_qty ?: null) : null,
        ];

        if ($this->promo && $this->promo->exists) {
            $this->promo->update($data);
            $message = 'Promo berhasil diperbharui.';
            $promo = $this->promo;
        } else {
            $promo = Promo::create($data);
            $message = 'Promo berhasil ditambahkan.';
        }

        // Sync Target SKUs (Produk Utama)
        $promo->skus()->delete();
        if (!$this->apply_to_all_items) {
            $skusToInsert = array_map(function ($item) {
                return ['sku' => $item['sku']];
            }, $this->selected_skus);
            $promo->skus()->createMany($skusToInsert);
        }

        // Sync Bundle SKUs (Produk Pendamping)
        $promo->bundleSkus()->delete();
        if ($this->is_bundle) {
            $bundleSkusToInsert = array_map(function ($item) {
                return [
                    'sku' => $item['sku'],
                    'discount_type' => null,
                    'discount_value' => null,
                    'max_discount' => null,
                ];
            }, $this->selected_bundle_skus);
            $promo->bundleSkus()->createMany($bundleSkusToInsert);
        }

        // Sync Promo Branches & Payment Methods
        $promo->branches()->sync($this->selected_branches);
        $promo->paymentMethods()->sync($this->selected_payment_methods);

        $this->dispatch('toast', title: 'Berhasil', message: $message, type: 'success');
        return $this->redirectRoute('admin.promos.index', navigate: true);
    }

    public function render()
    {
        $branchesQuery = \App\Models\Branch::query();
        if (\Illuminate\Support\Facades\Auth::check() && \Illuminate\Support\Facades\Auth::user()->business_unit_id) {
            $branchesQuery->where('business_unit_id', \Illuminate\Support\Facades\Auth::user()->business_unit_id);
        }

        return view('livewire.admin.promo.form', [
            'brands' => Brand::orderBy('name')->get(),
            'branches' => $branchesQuery->orderBy('name')->get(),
            'paymentMethods' => \App\Models\PaymentMethod::where(function ($q) {
                $buId = \Illuminate\Support\Facades\Auth::user()->getActiveBusinessUnitId();
                $q->where('business_unit_id', $buId)->orWhereNull('business_unit_id');
            })->orderBy('name')->get(),
        ]);
    }
}

<?php

namespace App\Livewire\Admin\Buyback;

use App\Models\BuybackTier;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin', ['title' => 'Buyback Tier'])]
class TierIndex extends Component
{
    public $tiers = [];

    public $isModalOpen = false;
    public $isEditMode  = false;
    public $tierId;

    // Form fields
    public $name      = '';

    // JSON rules editor
    // Struktur: [['category' => 'Kondisi Fisik', 'items' => [['name'=>'', 'type'=>'fixed', 'value'=>0]]]]
    public $ruleCategories = [];

    // Mapping Perangkat
    public $selectedProducts = [];
    public $searchProduct = '';
    public $productsAccurateList = [];

    // Mapping Kategori / Brand / OS
    public $mapping_os = '';
    public $mapping_category = '';
    public $mapping_brand = '';

    public $listOs = [];
    public $listCategories = [];
    public $listBrands = [];

    public $search = '';

    public function mount()
    {
        $this->loadTiers();
        $this->loadDropdowns();
    }

    public function loadDropdowns()
    {
        $buId = \Illuminate\Support\Facades\Auth::user()->getActiveBusinessUnitId() ?? 1;

        $this->listOs = \App\Models\ProductAccurate::whereNotNull('os')
            ->where('business_unit_id', $buId)
            ->select('os')->distinct()->pluck('os')->toArray();

        $this->listCategories = \App\Models\ProductAccurate::whereNotNull('categoryName')
            ->where('business_unit_id', $buId)
            ->select('categoryName')->distinct()->pluck('categoryName')->toArray();

        $this->listBrands = \App\Models\ProductAccurate::whereNotNull('brandName')
            ->where('business_unit_id', $buId)
            ->select('brandName')->distinct()->pluck('brandName')->toArray();
    }

    public function updatedSearch()
    {
        $this->loadTiers();
    }

    public function loadTiers()
    {
        $query = BuybackTier::withCount('devices');

        if (!empty($this->search)) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        $this->tiers = $query->get();
    }

    // ──────────────────────────────────────────────
    // CRUD Tier
    // ──────────────────────────────────────────────

    public function create()
    {
        $this->resetForm();
        $this->defaultCategory(); // Mulai dengan kategori default
        $this->isEditMode  = false;
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        $this->resetForm();
        $this->isEditMode = true;

        $tier            = BuybackTier::with('devices.productAccurate')->findOrFail($id);
        $this->tierId    = $tier->id;
        $this->name      = $tier->name;

        // Load existing mapped devices (Specific SKU)
        $this->selectedProducts = [];
        foreach ($tier->devices as $device) {
            if ($device->productAccurate) {
                $this->selectedProducts[$device->product_accurate_id] = $device->productAccurate->name . ' (' . $device->productAccurate->item_no . ')';
            }
        }

        // Load generic mapping (if any)
        $genericMapping = \App\Models\BuybackDevice::where('buyback_tier_id', $tier->id)
            ->whereNull('product_accurate_id')
            ->first();

        if ($genericMapping) {
            $this->mapping_os = $genericMapping->os_name ?? '';
            $this->mapping_category = $genericMapping->category_name ?? '';
            $this->mapping_brand = $genericMapping->brand_name ?? '';
        }

        // Konversi JSON rules ke format array untuk editor
        $rulesJson = $tier->getRulesByCategory();
        foreach ($rulesJson as $category => $data) {
            $items = $data['items'] ?? [];
            foreach ($items as &$item) {
                if (!isset($item['description'])) {
                    $item['description'] = '';
                }
            }
            $this->ruleCategories[] = [
                'category'    => $category,
                'is_multiple' => $data['is_multiple'] ?? false,
                'items'       => array_values($items),
            ];
        }

        if (empty($this->ruleCategories)) {
            $this->addCategory();
        }

        $this->isModalOpen = true;
    }

    public function store()
    {
        $this->validate([
            'name'      => 'required|string|max:255',
        ]);

        // Konversi array editor ke JSON rules
        $rulesJson = $this->buildRulesJson();

        $tier = BuybackTier::updateOrCreate(
            ['id' => $this->tierId],
            [
                'name'      => $this->name,
                'rules'     => $rulesJson,
            ]
        );

        // --- Sinkronisasi Pemetaan Perangkat ---
        $existingDeviceIds = \App\Models\BuybackDevice::where('buyback_tier_id', $tier->id)->pluck('product_accurate_id')->toArray();
        $newDeviceIds = array_keys($this->selectedProducts);

        // Items to delete
        $toDelete = array_diff($existingDeviceIds, $newDeviceIds);
        if (!empty($toDelete)) {
            \App\Models\BuybackDevice::where('buyback_tier_id', $tier->id)
                ->whereIn('product_accurate_id', $toDelete)
                ->delete();
        }

        // Items to add/update
        foreach ($newDeviceIds as $productId) {
            \App\Models\BuybackDevice::updateOrCreate(
                ['product_accurate_id' => $productId],
                [
                    'buyback_tier_id' => $tier->id,
                    'is_active' => true,
                ]
            );
        }

        // --- Sinkronisasi Generic Mapping ---
        if (!empty($this->mapping_os) || !empty($this->mapping_category) || !empty($this->mapping_brand)) {
            \App\Models\BuybackDevice::updateOrCreate(
                [
                    'buyback_tier_id' => $tier->id,
                    'product_accurate_id' => null
                ],
                [
                    'os_name' => $this->mapping_os ?: null,
                    'category_name' => $this->mapping_category ?: null,
                    'brand_name' => $this->mapping_brand ?: null,
                    'is_active' => true,
                ]
            );
        } else {
            \App\Models\BuybackDevice::where('buyback_tier_id', $tier->id)
                ->whereNull('product_accurate_id')
                ->delete();
        }
        // ----------------------------------------

        $this->dispatch(
            'toast',
            title: 'Berhasil',
            message: $this->isEditMode ? 'Tier berhasil diperbarui.' : 'Tier berhasil ditambahkan.',
            type: 'success'
        );

        $this->closeModal();
        $this->loadTiers();
    }

    public function delete($id)
    {
        BuybackTier::findOrFail($id)->delete();
        $this->dispatch('toast', title: 'Dihapus', message: 'Tier berhasil dihapus.', type: 'success');
        $this->loadTiers();
    }

    public function duplicate($id)
    {
        $original = BuybackTier::findOrFail($id);
        BuybackTier::create([
            'name'       => $original->name . ' (Salinan)',
            'rules'      => $original->rules,
        ]);
        $this->dispatch('toast', title: 'Berhasil', message: 'Tier berhasil diduplikat.', type: 'success');
        $this->loadTiers();
    }

    // ──────────────────────────────────────────────
    // Pemetaan Perangkat Helpers
    // ──────────────────────────────────────────────

    public function updatedSearchProduct()
    {
        if (strlen($this->searchProduct) >= 2) {
            $query = \App\Models\ProductAccurate::where(function ($q) {
                $q->where('name', 'like', '%' . $this->searchProduct . '%')
                    ->orWhere('item_no', 'like', '%' . $this->searchProduct . '%');
            });

            $query->whereNotIn('id', function ($subQuery) {
                $subQuery->select('product_accurate_id')->from('buyback_devices');
                if ($this->tierId) {
                    $subQuery->where('buyback_tier_id', '!=', $this->tierId);
                }
            });

            if (!empty($this->selectedProducts)) {
                $query->whereNotIn('id', array_keys($this->selectedProducts));
            }

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

    // ──────────────────────────────────────────────
    // JSON Rules Editor Helpers
    // ──────────────────────────────────────────────

    public function defaultCategory()
    {
        $this->ruleCategories[] = [
            'category' => 'Layar',
            'items'    => [
                ['name' => '', 'type' => 'fixed', 'value' => 0, 'description' => ''],
            ],
        ];
        $this->ruleCategories[] = [
            'category' => 'Fisik',
            'items'    => [
                ['name' => '', 'type' => 'fixed', 'value' => 0, 'description' => ''],
            ],
        ];
        $this->ruleCategories[] = [
            'category' => 'Kelengkapan',
            'items'    => [
                ['name' => '', 'type' => 'fixed', 'value' => 0, 'description' => ''],
            ],
        ];
        $this->ruleCategories[] = [
            'category' => 'Baterai Health',
            'items'    => [
                ['name' => '95%+', 'type' => 'fixed', 'value' => 0, 'description' => ''],
                ['name' => '85-94%', 'type' => 'fixed', 'value' => 0, 'description' => ''],
                ['name' => '<85%', 'type' => 'fixed', 'value' => 0, 'description' => ''],
            ],
        ];
    }
    public function addCategory()
    {
        $this->ruleCategories[] = [
            'category' => '',
            'items'    => [
                ['name' => '', 'type' => 'fixed', 'value' => 0, 'description' => ''],
            ],
        ];
    }

    public function removeCategory($catIndex)
    {
        unset($this->ruleCategories[$catIndex]);
        $this->ruleCategories = array_values($this->ruleCategories);
    }

    public function addItem($catIndex)
    {
        if (!isset($this->ruleCategories[$catIndex]['is_multiple'])) {
            $this->ruleCategories[$catIndex]['is_multiple'] = false;
        }
        $this->ruleCategories[$catIndex]['items'][] = [
            'name'  => '',
            'type'  => 'fixed',
            'value' => 0,
            'description' => '',
        ];
    }

    public function removeItem($catIndex, $itemIndex)
    {
        unset($this->ruleCategories[$catIndex]['items'][$itemIndex]);
        $this->ruleCategories[$catIndex]['items'] = array_values($this->ruleCategories[$catIndex]['items']);
    }

    private function buildRulesJson(): array
    {
        $result = [];
        foreach ($this->ruleCategories as $catData) {
            $category = trim($catData['category']);
            if (empty($category)) continue;

            $items = [];
            foreach ($catData['items'] as $item) {
                if (empty(trim($item['name']))) continue;
                $items[] = [
                    'name'        => trim($item['name']),
                    'type'        => $item['type'],
                    'value'       => (float) $item['value'],
                    'description' => isset($item['description']) ? trim($item['description']) : '',
                ];
            }

            if (!empty($items)) {
                $result[$category] = [
                    'is_multiple' => $catData['is_multiple'] ?? false,
                    'items'       => $items,
                ];
            }
        }
        return $result;
    }

    // ──────────────────────────────────────────────
    // Modal & Reset
    // ──────────────────────────────────────────────

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->tierId          = null;
        $this->name            = '';
        $this->ruleCategories = [];
        $this->selectedProducts = [];
        $this->searchProduct = '';
        $this->productsAccurateList = [];

        $this->mapping_os = '';
        $this->mapping_category = '';
        $this->mapping_brand = '';
    }

    public function render()
    {
        return view('livewire.admin.buyback.tier-index');
    }
}

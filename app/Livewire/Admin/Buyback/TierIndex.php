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

    public $search = '';

    public function mount()
    {
        $this->loadTiers();
    }

    public function updatedSearch()
    {
        $this->loadTiers();
    }

    public function loadTiers()
    {
        $query = BuybackTier::withCount('devices')->orderBy('min_price');
        
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

        // Load existing mapped devices
        $this->selectedProducts = [];
        foreach ($tier->devices as $device) {
            if ($device->productAccurate) {
                $this->selectedProducts[$device->product_accurate_id] = $device->productAccurate->name . ' (' . $device->productAccurate->item_no . ')';
            }
        }

        // Konversi JSON rules ke format array untuk editor
        $rulesJson = $tier->rules ?? [];
        foreach ($rulesJson as $category => $items) {
            foreach($items as &$item) {
                if (!isset($item['description'])) {
                    $item['description'] = '';
                }
            }
            $this->ruleCategories[] = [
                'category' => $category,
                'items'    => array_values($items),
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
                $result[$category] = $items;
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
        $this->ruleCategories  = [];
        $this->selectedProducts = [];
        $this->searchProduct   = '';
        $this->productsAccurateList = [];
    }

    public function render()
    {
        return view('livewire.admin.buyback.tier-index');
    }
}

<?php

namespace App\Livewire\Admin\Qc;

use App\Models\Brand;
use App\Models\QcTemplate;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin', ['title' => 'Template QC'])]
class TemplateIndex extends Component
{
    public $isModalOpen = false;
    public $isEditMode  = false;
    public $templateId;

    // Form fields
    public $name     = '';
    public $business_unit_id = '';
    public $brand_id = '';
    public $device_category = '';
    public $is_default = false;
    public $is_active  = true;
    public $max_weight_threshold = 3;

    // Checklist items editor: [{name, type, weight, is_fatal}]
    public $items = [];

    // ──────────────────────────────────────────────
    // CRUD
    // ──────────────────────────────────────────────

    public function create()
    {
        $this->resetForm();
        $this->loadDefaultItems();
        $this->isEditMode  = false;
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        $this->resetForm();
        $this->isEditMode = true;

        $template           = QcTemplate::findOrFail($id);
        $this->templateId   = $template->id;
        $this->name         = $template->name;
        $this->business_unit_id = $template->business_unit_id ?? '';
        $this->brand_id     = $template->brand_id ?? '';
        $this->device_category = $template->device_category ?? '';
        $this->is_default   = $template->is_default;
        $this->is_active    = $template->is_active;
        $this->max_weight_threshold = $template->max_weight_threshold ?? 3;

        $loadedItems = $template->items ?? [];
        // Inject default weight, is_fatal, and category if editing an old template
        $this->items = collect($loadedItems)->map(function ($item) {
            $item['weight'] = $item['weight'] ?? 1;
            $item['is_fatal'] = $item['is_fatal'] ?? false;
            $item['category'] = $item['category'] ?? 'Lainnya';
            return $item;
        })->toArray();

        if (empty($this->items)) {
            $this->loadDefaultItems();
        }

        $this->isModalOpen = true;
    }

    public function store()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'max_weight_threshold' => 'required|numeric|min:0',
        ]);

        // Filter out empty items and ensure weight/is_fatal exists
        $cleanItems = collect($this->items)
            ->filter(fn($item) => !empty(trim($item['name'] ?? '')))
            ->map(function ($item) {
                return [
                    'category' => $item['category'] ?? 'Lainnya',
                    'name' => $item['name'],
                    'type' => $item['type'] ?? 'boolean',
                    'weight' => (int) ($item['weight'] ?? 1),
                    'is_fatal' => (bool) ($item['is_fatal'] ?? false),
                ];
            })
            ->values()
            ->toArray();

        // If setting as default, unset other defaults within the same business unit scope
        if ($this->is_default) {
            QcTemplate::where('is_default', true)
                ->where('business_unit_id', $this->business_unit_id ?: null)
                ->update(['is_default' => false]);
        }

        QcTemplate::updateOrCreate(
            ['id' => $this->templateId],
            [
                'name'       => $this->name,
                'business_unit_id' => $this->business_unit_id ?: null,
                'brand_id'   => $this->brand_id ?: null,
                'device_category' => $this->device_category ?: null,
                'is_default' => $this->is_default,
                'is_active'  => $this->is_active,
                'max_weight_threshold' => $this->max_weight_threshold,
                'items'      => $cleanItems,
            ]
        );

        $this->dispatch(
            'toast',
            title: 'Berhasil',
            message: $this->isEditMode ? 'Template berhasil diperbarui.' : 'Template berhasil ditambahkan.',
            type: 'success'
        );

        $this->closeModal();
    }

    public function delete($id)
    {
        QcTemplate::findOrFail($id)->delete();
        $this->dispatch('toast', title: 'Dihapus', message: 'Template berhasil dihapus.', type: 'success');
    }

    public function duplicate($id)
    {
        $original = QcTemplate::findOrFail($id);
        QcTemplate::create([
            'name'       => $original->name . ' (Salinan)',
            'business_unit_id' => $original->business_unit_id,
            'brand_id'   => $original->brand_id,
            'device_category' => $original->device_category,
            'is_default' => false,
            'is_active'  => true,
            'max_weight_threshold' => $original->max_weight_threshold,
            'items'      => $original->items,
        ]);
        $this->dispatch('toast', title: 'Berhasil', message: 'Template berhasil diduplikat.', type: 'success');
    }

    // ──────────────────────────────────────────────
    // Items Editor Helpers
    // ──────────────────────────────────────────────

    public function addItem()
    {
        $this->items[] = ['category' => 'Lainnya', 'name' => '', 'type' => 'boolean', 'weight' => 1, 'is_fatal' => false];
    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function reorderItems($fromIndex, $toIndex)
    {
        if (!isset($this->items[$fromIndex]) || !isset($this->items[$toIndex])) {
            return;
        }

        $item = $this->items[$fromIndex];
        array_splice($this->items, $fromIndex, 1);
        array_splice($this->items, $toIndex, 0, [$item]);
        $this->items = array_values($this->items);
    }

    /**
     * Load default 22-item checklist
     */
    private function loadDefaultItems(): void
    {
        $this->items = [
            ['category' => 'Layar & Tampilan', 'name' => 'LCD', 'type' => 'boolean', 'weight' => 2, 'is_fatal' => false],
            ['category' => 'Layar & Tampilan', 'name' => 'Touch Screen', 'type' => 'boolean', 'weight' => 1, 'is_fatal' => false],
            ['category' => 'Baterai', 'name' => 'Health Battery', 'type' => 'text', 'weight' => 1, 'is_fatal' => false],
            ['category' => 'Kamera', 'name' => 'Kamera Belakang 1/2/3', 'type' => 'boolean', 'weight' => 1, 'is_fatal' => false],
            ['category' => 'Kamera', 'name' => 'Kamera Depan', 'type' => 'boolean', 'weight' => 1, 'is_fatal' => false],
            ['category' => 'Tombol Fisik', 'name' => 'Power On/Off', 'type' => 'boolean', 'weight' => 1, 'is_fatal' => false],
            ['category' => 'Tombol Fisik', 'name' => 'Volume', 'type' => 'boolean', 'weight' => 1, 'is_fatal' => false],
            ['category' => 'Tombol Fisik', 'name' => 'Mute Switch (Silent)', 'type' => 'boolean', 'weight' => 1, 'is_fatal' => false],
            ['category' => 'Tombol Fisik', 'name' => 'Home Button', 'type' => 'boolean', 'weight' => 1, 'is_fatal' => false],
            ['category' => 'Sensor & Biometrik', 'name' => 'Touch ID / Face ID', 'type' => 'boolean', 'weight' => 0, 'is_fatal' => true],
            ['category' => 'Audio & Suara', 'name' => 'Microphone', 'type' => 'boolean', 'weight' => 1, 'is_fatal' => false],
            ['category' => 'Port & Sensor', 'name' => 'Sensor Proximity', 'type' => 'boolean', 'weight' => 1, 'is_fatal' => false],
            ['category' => 'Audio & Suara', 'name' => 'Speaker Atas', 'type' => 'boolean', 'weight' => 1, 'is_fatal' => false],
            ['category' => 'Audio & Suara', 'name' => 'Speaker Bawah', 'type' => 'boolean', 'weight' => 1, 'is_fatal' => false],
            ['category' => 'Port & Sensor', 'name' => 'Port Charging', 'type' => 'boolean', 'weight' => 1, 'is_fatal' => false],
            ['category' => 'Port & Sensor', 'name' => 'Port Handsfree', 'type' => 'boolean', 'weight' => 1, 'is_fatal' => false],
            ['category' => 'Kamera', 'name' => 'Flash Light', 'type' => 'boolean', 'weight' => 1, 'is_fatal' => false],
            ['category' => 'Audio & Suara', 'name' => 'Taptic / Vibrate', 'type' => 'boolean', 'weight' => 1, 'is_fatal' => false],
            ['category' => 'Konektivitas', 'name' => 'Wifi / Bluetooth', 'type' => 'boolean', 'weight' => 0, 'is_fatal' => true],
            ['category' => 'Konektivitas', 'name' => 'Signal', 'type' => 'boolean', 'weight' => 0, 'is_fatal' => true],
            ['category' => 'Fisik Bodi', 'name' => 'BackGlass / Housing', 'type' => 'boolean', 'weight' => 1, 'is_fatal' => false],
            ['category' => 'Tombol Fisik', 'name' => 'Tombol', 'type' => 'boolean', 'weight' => 1, 'is_fatal' => false],
        ];
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
        $this->templateId = null;
        $this->name       = '';
        $this->business_unit_id = '';
        $this->brand_id   = '';
        $this->device_category = '';
        $this->is_default = false;
        $this->is_active  = true;
        $this->max_weight_threshold = 3;
        $this->items      = [];
    }

    public function render()
    {
        $activeBuId = Auth::user()->getActiveBusinessUnitId();

        $query = QcTemplate::with('brand', 'businessUnit')
            ->orderBy('is_default', 'desc')
            ->orderBy('name');

        if ($activeBuId) {
            $query->where(function ($q) use ($activeBuId) {
                $q->where('business_unit_id', $activeBuId)
                    ->orWhereNull('business_unit_id');
            });
        }

        return view('livewire.admin.qc.template-index', [
            'templates' => $query->get(),
            'brands' => Brand::where('business_unit_id', \Illuminate\Support\Facades\Auth::user()->getActiveBusinessUnitId())
                ->orderBy('name')->get(),
            'businessUnits' => \App\Models\BusinessUnit::orderBy('name')->get(),
        ]);
    }
}

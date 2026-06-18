<?php

namespace App\Livewire\Admin\Qc;

use App\Models\Brand;
use App\Models\QcTemplate;
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
    public $brand_id = '';
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
        $this->brand_id     = $template->brand_id ?? '';
        $this->is_default   = $template->is_default;
        $this->is_active    = $template->is_active;
        $this->max_weight_threshold = $template->max_weight_threshold ?? 3;
        
        $loadedItems = $template->items ?? [];
        // Inject default weight and is_fatal if editing an old template
        $this->items = collect($loadedItems)->map(function ($item) {
            $item['weight'] = $item['weight'] ?? 1;
            $item['is_fatal'] = $item['is_fatal'] ?? false;
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
                    'name' => $item['name'],
                    'type' => $item['type'] ?? 'boolean',
                    'weight' => (int) ($item['weight'] ?? 1),
                    'is_fatal' => (bool) ($item['is_fatal'] ?? false),
                ];
            })
            ->values()
            ->toArray();

        // If setting as default, unset other defaults
        if ($this->is_default) {
            QcTemplate::where('is_default', true)->update(['is_default' => false]);
        }

        QcTemplate::updateOrCreate(
            ['id' => $this->templateId],
            [
                'name'       => $this->name,
                'brand_id'   => $this->brand_id ?: null,
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
            'brand_id'   => $original->brand_id,
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
        $this->items[] = ['name' => '', 'type' => 'boolean', 'weight' => 1, 'is_fatal' => false];
    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    /**
     * Load default 22-item checklist
     */
    private function loadDefaultItems(): void
    {
        $this->items = [
            ['name' => 'LCD', 'type' => 'boolean', 'weight' => 2, 'is_fatal' => false],
            ['name' => 'Touch Screen', 'type' => 'boolean', 'weight' => 1, 'is_fatal' => false],
            ['name' => 'Health Battery', 'type' => 'text', 'weight' => 1, 'is_fatal' => false],
            ['name' => 'Kamera Belakang 1/2/3', 'type' => 'boolean', 'weight' => 1, 'is_fatal' => false],
            ['name' => 'Kamera Depan', 'type' => 'boolean', 'weight' => 1, 'is_fatal' => false],
            ['name' => 'Power On/Off', 'type' => 'boolean', 'weight' => 1, 'is_fatal' => false],
            ['name' => 'Volume', 'type' => 'boolean', 'weight' => 1, 'is_fatal' => false],
            ['name' => 'Mute Switch (Silent)', 'type' => 'boolean', 'weight' => 1, 'is_fatal' => false],
            ['name' => 'Home Button', 'type' => 'boolean', 'weight' => 1, 'is_fatal' => false],
            ['name' => 'Touch ID / Face ID', 'type' => 'boolean', 'weight' => 0, 'is_fatal' => true],
            ['name' => 'Microphone', 'type' => 'boolean', 'weight' => 1, 'is_fatal' => false],
            ['name' => 'Sensor Proximity', 'type' => 'boolean', 'weight' => 1, 'is_fatal' => false],
            ['name' => 'Speaker Atas', 'type' => 'boolean', 'weight' => 1, 'is_fatal' => false],
            ['name' => 'Speaker Bawah', 'type' => 'boolean', 'weight' => 1, 'is_fatal' => false],
            ['name' => 'Port Charging', 'type' => 'boolean', 'weight' => 1, 'is_fatal' => false],
            ['name' => 'Port Handsfree', 'type' => 'boolean', 'weight' => 1, 'is_fatal' => false],
            ['name' => 'Flash Light', 'type' => 'boolean', 'weight' => 1, 'is_fatal' => false],
            ['name' => 'Taptic / Vibrate', 'type' => 'boolean', 'weight' => 1, 'is_fatal' => false],
            ['name' => 'Wifi / Bluetooth', 'type' => 'boolean', 'weight' => 0, 'is_fatal' => true],
            ['name' => 'Signal', 'type' => 'boolean', 'weight' => 0, 'is_fatal' => true],
            ['name' => 'BackGlass / Housing', 'type' => 'boolean', 'weight' => 1, 'is_fatal' => false],
            ['name' => 'Tombol', 'type' => 'boolean', 'weight' => 1, 'is_fatal' => false],
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
        $this->brand_id   = '';
        $this->is_default = false;
        $this->is_active  = true;
        $this->max_weight_threshold = 3;
        $this->items      = [];
    }

    public function render()
    {
        return view('livewire.admin.qc.template-index', [
            'templates' => QcTemplate::with('brand')->orderBy('is_default', 'desc')->orderBy('name')->get(),
            'brands'    => Brand::orderBy('name')->get(),
        ]);
    }
}

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

    // Checklist items editor: [{name, type}]
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
        $this->items        = $template->items ?? [];

        if (empty($this->items)) {
            $this->loadDefaultItems();
        }

        $this->isModalOpen = true;
    }

    public function store()
    {
        $this->validate([
            'name' => 'required|string|max:255',
        ]);

        // Filter out empty items
        $cleanItems = collect($this->items)
            ->filter(fn($item) => !empty(trim($item['name'] ?? '')))
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
            'items'      => $original->items,
        ]);
        $this->dispatch('toast', title: 'Berhasil', message: 'Template berhasil diduplikat.', type: 'success');
    }

    // ──────────────────────────────────────────────
    // Items Editor Helpers
    // ──────────────────────────────────────────────

    public function addItem()
    {
        $this->items[] = ['name' => '', 'type' => 'boolean'];
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
            ['name' => 'LCD', 'type' => 'boolean'],
            ['name' => 'Touch Screen', 'type' => 'boolean'],
            ['name' => 'Health Battery', 'type' => 'text'],
            ['name' => 'Kamera Belakang 1/2/3', 'type' => 'boolean'],
            ['name' => 'Kamera Depan', 'type' => 'boolean'],
            ['name' => 'Power On/Off', 'type' => 'boolean'],
            ['name' => 'Volume', 'type' => 'boolean'],
            ['name' => 'Mute Switch (Silent)', 'type' => 'boolean'],
            ['name' => 'Home Button', 'type' => 'boolean'],
            ['name' => 'Touch ID / Face ID', 'type' => 'boolean'],
            ['name' => 'Microphone', 'type' => 'boolean'],
            ['name' => 'Sensor Proximity', 'type' => 'boolean'],
            ['name' => 'Speaker Atas', 'type' => 'boolean'],
            ['name' => 'Speaker Bawah', 'type' => 'boolean'],
            ['name' => 'Port Charging', 'type' => 'boolean'],
            ['name' => 'Port Handsfree', 'type' => 'boolean'],
            ['name' => 'Flash Light', 'type' => 'boolean'],
            ['name' => 'Taptic / Vibrate', 'type' => 'boolean'],
            ['name' => 'Wifi / Bluetooth', 'type' => 'boolean'],
            ['name' => 'Signal', 'type' => 'boolean'],
            ['name' => 'BackGlass / Housing', 'type' => 'boolean'],
            ['name' => 'Tombol', 'type' => 'boolean'],
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

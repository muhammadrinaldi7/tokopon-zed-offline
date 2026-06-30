<?php

namespace App\Livewire\Admin\Settings\QcTemplate;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\QcTemplate;
use App\Models\Brand;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin', ['title' => 'Pengaturan Template QC'])]
class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $showForm = false;
    
    // Form fields
    public $templateId = null;
    public $name = '';
    public $brand_id = null;
    public $is_default = false;
    public $is_active = true;
    public $items = [];

    // Temporary variables for adding new item
    public $newItemCategory = '';
    public $newItemName = '';
    public $newItemType = 'boolean';

    // Categories predefined options just to help typing
    public $availableCategories = [
        'Layar & Tampilan',
        'Kamera',
        'Tombol Fisik',
        'Sensor & Biometrik',
        'Konektivitas',
        'Fisik Bodi',
        'Baterai',
        'Audio & Suara',
        'Port & Sensor',
        'Kelengkapan'
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function create()
    {
        $this->resetForm();
        $this->showForm = true;
        // Default minimal items
        $this->items = [
            ['category' => 'Layar & Tampilan', 'name' => 'LCD', 'type' => 'boolean']
        ];
    }

    public function edit($id)
    {
        $this->resetForm();
        $template = QcTemplate::findOrFail($id);
        
        $this->templateId = $template->id;
        $this->name = $template->name;
        $this->brand_id = $template->brand_id;
        $this->is_default = $template->is_default;
        $this->is_active = $template->is_active;
        
        $this->items = $template->items ?? [];
        if (!is_array($this->items)) {
            $this->items = json_decode($this->items, true) ?? [];
        }

        $this->showForm = true;
    }

    public function resetForm()
    {
        $this->templateId = null;
        $this->name = '';
        $this->brand_id = null;
        $this->is_default = false;
        $this->is_active = true;
        $this->items = [];
        $this->newItemCategory = '';
        $this->newItemName = '';
        $this->newItemType = 'boolean';
        $this->resetValidation();
    }

    public function closeForm()
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function addItem()
    {
        $this->validate([
            'newItemCategory' => 'required|string|max:100',
            'newItemName' => 'required|string|max:100',
            'newItemType' => 'required|in:boolean,text'
        ], [
            'newItemCategory.required' => 'Kategori harus diisi',
            'newItemName.required' => 'Nama pengecekan harus diisi',
        ]);

        $this->items[] = [
            'category' => $this->newItemCategory,
            'name' => $this->newItemName,
            'type' => $this->newItemType
        ];

        // Reset inputs
        $this->newItemName = '';
        // Keep category and type same as previous to speed up data entry
    }

    public function removeItem($index)
    {
        if (isset($this->items[$index])) {
            unset($this->items[$index]);
            $this->items = array_values($this->items);
        }
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'brand_id' => 'nullable|exists:brands,id',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'items' => 'required|array|min:1',
        ]);

        if ($this->is_default) {
            // Only 1 default allowed
            QcTemplate::where('id', '!=', $this->templateId)->update(['is_default' => false]);
        }

        QcTemplate::updateOrCreate(
            ['id' => $this->templateId],
            [
                'name' => $this->name,
                'brand_id' => $this->brand_id ?: null,
                'is_default' => $this->is_default,
                'is_active' => $this->is_active,
                'items' => $this->items, // Casts as array automatically if model uses casts
            ]
        );

        $this->dispatch('toast', title: 'Berhasil', message: 'Template QC berhasil disimpan', type: 'success');
        $this->closeForm();
    }

    public function delete($id)
    {
        $template = QcTemplate::findOrFail($id);
        
        // Cek jika sedang digunakan
        if ($template->inspections()->count() > 0) {
            $this->dispatch('toast', title: 'Gagal', message: 'Template sedang digunakan oleh data Inspeksi', type: 'error');
            return;
        }

        $template->delete();
        $this->dispatch('toast', title: 'Berhasil', message: 'Template QC berhasil dihapus', type: 'success');
    }

    public function toggleActive($id)
    {
        $template = QcTemplate::findOrFail($id);
        $template->update(['is_active' => !$template->is_active]);
    }

    public function setAsDefault($id)
    {
        QcTemplate::query()->update(['is_default' => false]);
        QcTemplate::where('id', $id)->update(['is_default' => true]);
        $this->dispatch('toast', title: 'Berhasil', message: 'Template default berhasil diubah', type: 'success');
    }

    public function render()
    {
        $templates = QcTemplate::with('brand')
            ->where('name', 'like', '%' . $this->search . '%')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.admin.settings.qc-template.index', [
            'templates' => $templates,
            'brands' => Brand::orderBy('name')->get()
        ]);
    }
}

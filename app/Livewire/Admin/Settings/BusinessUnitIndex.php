<?php

namespace App\Livewire\Admin\Settings;

use Livewire\Component;
use App\Models\BusinessUnit;

class BusinessUnitIndex extends Component
{
    public $units = [];
    
    // Form fields
    public $unitId;
    public $name;
    public $code;
    public $accurate_host;
    public $accurate_db;
    public $accurate_user;
    public $accurate_password;
    public $is_active = true;

    public $showModal = false;

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $this->units = BusinessUnit::all();
    }

    public function resetFields()
    {
        $this->unitId = null;
        $this->name = '';
        $this->code = '';
        $this->accurate_host = '';
        $this->accurate_db = '';
        $this->accurate_user = '';
        $this->accurate_password = '';
        $this->is_active = true;
    }

    public function openModal()
    {
        $this->resetFields();
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetFields();
    }

    public function edit($id)
    {
        $unit = BusinessUnit::findOrFail($id);
        $this->unitId = $unit->id;
        $this->name = $unit->name;
        $this->code = $unit->code;
        $this->accurate_host = $unit->accurate_host;
        $this->accurate_db = $unit->accurate_db;
        $this->accurate_user = $unit->accurate_user;
        $this->accurate_password = $unit->accurate_password;
        $this->is_active = $unit->is_active;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:business_units,code,' . $this->unitId,
        ]);

        BusinessUnit::updateOrCreate(
            ['id' => $this->unitId],
            [
                'name' => $this->name,
                'code' => $this->code,
                'accurate_host' => $this->accurate_host,
                'accurate_db' => $this->accurate_db,
                'accurate_user' => $this->accurate_user,
                'accurate_password' => $this->accurate_password,
                'is_active' => $this->is_active,
            ]
        );

        $this->closeModal();
        $this->loadData();
        session()->flash('message', $this->unitId ? 'Unit Usaha berhasil diupdate.' : 'Unit Usaha berhasil ditambahkan.');
    }

    public function toggleActive($id)
    {
        $unit = BusinessUnit::findOrFail($id);
        $unit->update(['is_active' => !$unit->is_active]);
        $this->loadData();
    }

    public function render()
    {
        return view('livewire.admin.settings.business-unit-index')->layout('layouts.admin');
    }
}

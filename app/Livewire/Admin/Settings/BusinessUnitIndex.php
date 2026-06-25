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
    public $customer_prefix;
    public $order_prefix;
    public $draft_prefix;
    public $store_title;
    public $receipt_show_discount = false;
    public $accurate_host;
    public $accurate_token;
    public $accurate_secret_key;
    public $accurate_webhook_token;
    public $accurate_database_id;
    public $is_taxable = false;
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
        $this->customer_prefix = '';
        $this->order_prefix = '';
        $this->draft_prefix = '';
        $this->store_title = '';
        $this->receipt_show_discount = false;
        $this->accurate_host = '';
        $this->accurate_token = '';
        $this->accurate_secret_key = '';
        $this->accurate_webhook_token = '';
        $this->accurate_database_id = '';
        $this->is_taxable = false;
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
        $this->customer_prefix = $unit->customer_prefix;
        $this->order_prefix = $unit->order_prefix;
        $this->draft_prefix = $unit->draft_prefix;
        $this->store_title = $unit->store_title;
        $this->receipt_show_discount = (bool)$unit->receipt_show_discount;
        $this->accurate_host = $unit->accurate_host;
        $this->accurate_token = $unit->accurate_token;
        $this->accurate_secret_key = $unit->accurate_secret_key;
        $this->accurate_webhook_token = $unit->accurate_webhook_token;
        $this->accurate_database_id = $unit->accurate_database_id;
        $this->is_taxable = (bool)$unit->is_taxable;
        $this->is_active = $unit->is_active;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:business_units,code,' . $this->unitId,
            'customer_prefix' => 'nullable|string|max:10',
            'order_prefix' => 'nullable|string|max:20',
            'draft_prefix' => 'nullable|string|max:20',
            'store_title' => 'nullable|string|max:100',
            'receipt_show_discount' => 'boolean',
        ]);

        BusinessUnit::updateOrCreate(
            ['id' => $this->unitId],
            [
                'name' => $this->name,
                'code' => $this->code,
                'customer_prefix' => $this->customer_prefix ? strtoupper($this->customer_prefix) : null,
                'order_prefix' => $this->order_prefix ? strtoupper($this->order_prefix) : null,
                'draft_prefix' => $this->draft_prefix ? strtoupper($this->draft_prefix) : null,
                'store_title' => $this->store_title ? strtoupper($this->store_title) : null,
                'receipt_show_discount' => $this->receipt_show_discount,
                'accurate_host' => $this->accurate_host,
                'accurate_token' => $this->accurate_token,
                'accurate_secret_key' => $this->accurate_secret_key,
                'accurate_webhook_token' => $this->accurate_webhook_token,
                'accurate_database_id' => $this->accurate_database_id,
                'is_taxable' => $this->is_taxable,
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

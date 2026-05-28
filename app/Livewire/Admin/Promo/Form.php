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
        }
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
        ], [
            'brand_id.required_if' => 'Brand wajib dipilih jika kategori adalah Sponsor Brand.',
        ]);

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
        ];

        if ($this->promo && $this->promo->exists) {
            $this->promo->update($data);
            $message = 'Promo berhasil diperbarui.';
        } else {
            Promo::create($data);
            $message = 'Promo berhasil ditambahkan.';
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

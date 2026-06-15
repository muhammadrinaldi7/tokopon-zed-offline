<?php

namespace App\Livewire\Admin\ManualDiscount;

use App\Models\ManualDiscountPreset;
use App\Models\Brand;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin', ['title' => 'Form Preset Diskon Manual'])]
class Form extends Component
{
    public $presetId;
    public $amount;
    public $brand_id = null;
    public $is_active = true;

    public function mount($id = null)
    {
        if ($id) {
            $preset = ManualDiscountPreset::findOrFail($id);
            $this->presetId = $preset->id;
            $this->amount = $preset->amount;
            $this->brand_id = $preset->brand_id;
            $this->is_active = $preset->is_active;
        }
    }

    public function save()
    {
        $this->validate([
            'amount' => 'required|integer|min:1',
            'brand_id' => 'nullable|exists:brands,id',
            'is_active' => 'boolean'
        ]);

        if ($this->presetId) {
            ManualDiscountPreset::find($this->presetId)->update([
                'amount' => $this->amount,
                'brand_id' => $this->brand_id ?: null,
                'is_active' => $this->is_active
            ]);
            $msg = 'Preset diskon berhasil diperbarui.';
        } else {
            ManualDiscountPreset::create([
                'amount' => $this->amount,
                'brand_id' => $this->brand_id ?: null,
                'is_active' => $this->is_active
            ]);
            $msg = 'Preset diskon berhasil ditambahkan.';
        }

        session()->flash('success', $msg);
        return redirect()->route('admin.manual-discount.index');
    }

    public function render()
    {
        return view('livewire.admin.manual-discount.form', [
            'brands' => Brand::orderBy('name')->get()
        ]);
    }
}

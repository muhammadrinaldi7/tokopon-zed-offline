<?php

namespace App\Livewire\Zoffline\Ecommerce;

use App\Models\BusinessUnit;
use App\Models\PaymentMethod;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.z')]
class PaymentMethodManager extends Component
{
    public string $search = '';
    public string $filterTab = 'all'; // all, mobile_only, non_mobile
    public ?int $selectedBusinessUnitId = null;

    // Modal Form State
    public bool $showModal = false;
    public bool $isEdit = false;
    public ?int $methodId = null;

    public string $name = '';
    public string $bank_name = 'BCA';
    public string $account_number = '';
    public string $account_owner = '';
    public ?int $business_unit_id = null;
    public string $accurate_bank_no = '11.10.001';
    public float $mdr_percentage = 0;
    public bool $is_active = true;
    public bool $is_visible_mobile = true;
    public string $category = 'NON-TUNAI';

    public array $popularBanks = [
        'BCA' => 'Bank Central Asia (BCA)',
        'MANDIRI' => 'Bank Mandiri',
        'BRI' => 'Bank Rakyat Indonesia (BRI)',
        'BNI' => 'Bank Negara Indonesia (BNI)',
        'BSI' => 'Bank Syariah Indonesia (BSI)',
        'CIMB' => 'CIMB Niaga',
        'PERMATA' => 'Bank Permata',
        'JAGO' => 'Bank Jago',
        'QRIS' => 'QRIS Toko (Statis/Dinamis)',
    ];

    protected $rules = [
        'name' => 'required|string|max:255',
        'bank_name' => 'required|string|max:255',
        'account_number' => 'required|string|max:255',
        'account_owner' => 'required|string|max:255',
        'business_unit_id' => 'required|exists:business_units,id',
        'accurate_bank_no' => 'required|string|max:255',
        'is_active' => 'boolean',
        'is_visible_mobile' => 'boolean',
    ];

    public function mount(): void
    {
        $defaultUnit = BusinessUnit::where('is_active', true)->first();
        if ($defaultUnit) {
            $this->business_unit_id = $defaultUnit->id;
        }
    }

    public function openCreateModal(): void
    {
        $this->resetValidation();
        $this->methodId = null;
        $this->name = '';
        $this->bank_name = 'BCA';
        $this->account_number = '';
        $this->account_owner = 'PT TOKOPON GADGET INDONESIA';
        $this->accurate_bank_no = '11.10.001';
        $this->is_active = true;
        $this->is_visible_mobile = true;
        $this->isEdit = false;
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $this->resetValidation();
        $method = PaymentMethod::findOrFail($id);
        $this->methodId = $method->id;
        $this->name = $method->name;
        $this->bank_name = $method->bank_name ?: $method->name;
        $this->account_number = $method->account_number ?: '';
        $this->account_owner = $method->account_owner ?: '';
        $this->business_unit_id = $method->business_unit_id;
        $this->accurate_bank_no = $method->accurate_bank_no ?: '11.10.001';
        $this->is_active = (bool) $method->is_active;
        $this->is_visible_mobile = (bool) $method->is_visible_mobile;

        $this->isEdit = true;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name' => trim($this->name),
            'bank_name' => trim($this->bank_name),
            'account_number' => trim($this->account_number),
            'account_owner' => trim($this->account_owner),
            'business_unit_id' => $this->business_unit_id,
            'accurate_bank_no' => trim($this->accurate_bank_no),
            'accurate_customer_no' => 'UMUM',
            'mdr_percentage' => 0,
            'is_active' => $this->is_active,
            'is_visible_mobile' => $this->is_visible_mobile,
            'category' => 'NON-TUNAI',
        ];

        if ($this->isEdit && $this->methodId) {
            PaymentMethod::findOrFail($this->methodId)->update($data);
            $this->dispatch('toast', title: 'Berhasil', message: "Rekening {$this->name} berhasil diperbarui.", type: 'success');
        } else {
            PaymentMethod::create($data);
            $this->dispatch('toast', title: 'Berhasil', message: "Rekening {$this->name} berhasil ditambahkan.", type: 'success');
        }

        $this->showModal = false;
    }

    public function toggleMobileVisible(int $id): void
    {
        $method = PaymentMethod::findOrFail($id);
        $method->update(['is_visible_mobile' => !$method->is_visible_mobile]);
        $statusMsg = $method->is_visible_mobile ? 'sekarang TAMPIL di Mobile App' : 'DISEMBUNYIKAN dari Mobile App';
        $this->dispatch('toast', title: 'Status Diperbarui', message: "Rekening {$method->name} {$statusMsg}.", type: 'success');
    }

    public function toggleActive(int $id): void
    {
        $method = PaymentMethod::findOrFail($id);
        $method->update(['is_active' => !$method->is_active]);
        $statusMsg = $method->is_active ? 'diaktifkan' : 'dinonaktifkan';
        $this->dispatch('toast', title: 'Status Diperbarui', message: "Rekening {$method->name} {$statusMsg}.", type: 'success');
    }

    public function delete(int $id): void
    {
        $method = PaymentMethod::findOrFail($id);
        $name = $method->name;
        $method->delete();
        $this->dispatch('toast', title: 'Dihapus', message: "Rekening {$name} berhasil dihapus.", type: 'info');
    }

    public function render()
    {
        $businessUnits = BusinessUnit::where('is_active', true)->get();

        $query = PaymentMethod::with('businessUnit')->orderByDesc('is_visible_mobile')->orderBy('name');

        if ($this->selectedBusinessUnitId) {
            $query->where('business_unit_id', $this->selectedBusinessUnitId);
        }

        if ($this->filterTab === 'mobile_only') {
            $query->where('is_visible_mobile', true);
        } elseif ($this->filterTab === 'non_mobile') {
            $query->where('is_visible_mobile', false);
        }

        if (!empty($this->search)) {
            $term = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                  ->orWhere('bank_name', 'like', $term)
                  ->orWhere('account_number', 'like', $term)
                  ->orWhere('account_owner', 'like', $term);
            });
        }

        $methods = $query->get();

        return view('livewire.zoffline.ecommerce.payment-method-manager', [
            'methods' => $methods,
            'businessUnits' => $businessUnits,
            'totalMobileActive' => PaymentMethod::where('is_active', true)->where('is_visible_mobile', true)->count(),
        ]);
    }
}

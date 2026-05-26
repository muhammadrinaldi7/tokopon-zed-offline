<?php

namespace App\Livewire\Admin\Settings;

use App\Models\PaymentMethod;
use App\Models\PaymentMethodRate;
use Livewire\Attributes\Layout;
use Livewire\Component;

class PaymentMethodIndex extends Component
{
    public $paymentMethods;
    public $showModal = false;
    public $isEdit = false;

    public $methodId;
    public $name;
    public $bank_name;
    public $account_number;
    public $account_owner;
    public $accurate_bank_no;
    public $mdr_percentage = 0;
    public $is_active = true;

    // State untuk Tarif MDR
    public $showRatesModal = false;
    public $showRateFormModal = false;
    public $selectedPaymentMethodForRates = null;
    public $rates = [];
    public $isEditRate = false;

    // Form fields untuk Rate
    public $rateId;
    public $rateName;
    public $rateMdrPercentage = 0;
    public $rateAccurateAccountNo;
    public $rateIsActive = true;

    protected $rules = [
        'name' => 'required|string|max:255',
        'bank_name' => 'nullable|string|max:255',
        'account_number' => 'nullable|string|max:255',
        'account_owner' => 'nullable|string|max:255',
        'accurate_bank_no' => 'required|string|max:255',
        'mdr_percentage' => 'required|numeric|min:0|max:100',
        'is_active' => 'boolean',
    ];

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $this->paymentMethods = PaymentMethod::with('rates')->get();
    }

    public function create()
    {
        $this->resetFields();
        $this->isEdit = false;
        $this->showModal = true;
    }

    public function edit($id)
    {
        $this->resetFields();
        $method = PaymentMethod::findOrFail($id);
        $this->methodId = $method->id;
        $this->name = $method->name;
        $this->bank_name = $method->bank_name;
        $this->account_number = $method->account_number;
        $this->account_owner = $method->account_owner;
        $this->accurate_bank_no = $method->accurate_bank_no;
        $this->mdr_percentage = $method->mdr_percentage;
        $this->is_active = $method->is_active;

        $this->isEdit = true;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'bank_name' => $this->bank_name,
            'account_number' => $this->account_number,
            'account_owner' => $this->account_owner,
            'accurate_bank_no' => $this->accurate_bank_no,
            'mdr_percentage' => $this->mdr_percentage,
            'is_active' => $this->is_active,
        ];

        if ($this->isEdit) {
            PaymentMethod::findOrFail($this->methodId)->update($data);
            $this->dispatch('toast', title: 'Berhasil', message: 'Metode Pembayaran diperbarui.', type: 'success');
        } else {
            PaymentMethod::create($data);
            $this->dispatch('toast', title: 'Berhasil', message: 'Metode Pembayaran ditambahkan.', type: 'success');
        }

        $this->showModal = false;
        $this->loadData();
    }

    public function delete($id)
    {
        PaymentMethod::findOrFail($id)->delete();
        $this->dispatch('toast', title: 'Berhasil', message: 'Metode Pembayaran dihapus.', type: 'success');
        $this->loadData();
    }

    public function toggleActive($id)
    {
        $method = PaymentMethod::findOrFail($id);
        $method->update(['is_active' => !$method->is_active]);
        $this->loadData();
    }

    public function resetFields()
    {
        $this->methodId = null;
        $this->name = '';
        $this->bank_name = '';
        $this->account_number = '';
        $this->account_owner = '';
        $this->accurate_bank_no = '';
        $this->mdr_percentage = 0;
        $this->is_active = true;
    }

    // ─── MDR Rates CRUD ─────────────────────────────────────────

    public function manageRates($paymentMethodId)
    {
        $this->selectedPaymentMethodForRates = PaymentMethod::findOrFail($paymentMethodId);
        $this->loadRates();
        $this->showRatesModal = true;
    }

    public function loadRates()
    {
        if ($this->selectedPaymentMethodForRates) {
            $this->rates = PaymentMethodRate::where('payment_method_id', $this->selectedPaymentMethodForRates->id)->get();
        }
    }

    public function createRate()
    {
        $this->resetRateFields();
        $this->isEditRate = false;
        $this->showRateFormModal = true;
    }

    public function editRate($id)
    {
        $this->resetRateFields();
        $rate = PaymentMethodRate::findOrFail($id);
        $this->rateId = $rate->id;
        $this->rateName = $rate->name;
        $this->rateMdrPercentage = $rate->mdr_percentage;
        $this->rateAccurateAccountNo = $rate->accurate_account_no;
        $this->rateIsActive = $rate->is_active;

        $this->isEditRate = true;
        $this->showRateFormModal = true;
    }

    public function saveRate()
    {
        $this->validate([
            'rateName' => 'required|string|max:255',
            'rateMdrPercentage' => 'required|numeric|min:0|max:100',
            'rateAccurateAccountNo' => 'nullable|string|max:255',
            'rateIsActive' => 'boolean',
        ]);

        $data = [
            'payment_method_id' => $this->selectedPaymentMethodForRates->id,
            'name' => $this->rateName,
            'mdr_percentage' => $this->rateMdrPercentage,
            'accurate_account_no' => $this->rateAccurateAccountNo ?: null,
            'is_active' => $this->rateIsActive,
        ];

        if ($this->isEditRate) {
            PaymentMethodRate::findOrFail($this->rateId)->update($data);
            $this->dispatch('toast', title: 'Berhasil', message: 'Tarif MDR diperbarui.', type: 'success');
        } else {
            PaymentMethodRate::create($data);
            $this->dispatch('toast', title: 'Berhasil', message: 'Tarif MDR ditambahkan.', type: 'success');
        }

        $this->showRateFormModal = false;
        $this->loadRates();
        $this->loadData(); // refresh parent count
    }

    public function deleteRate($id)
    {
        PaymentMethodRate::findOrFail($id)->delete();
        $this->dispatch('toast', title: 'Berhasil', message: 'Tarif MDR dihapus.', type: 'success');
        $this->loadRates();
        $this->loadData();
    }

    public function toggleRateActive($id)
    {
        $rate = PaymentMethodRate::findOrFail($id);
        $rate->update(['is_active' => !$rate->is_active]);
        $this->loadRates();
    }

    public function resetRateFields()
    {
        $this->rateId = null;
        $this->rateName = '';
        $this->rateMdrPercentage = 0;
        $this->rateAccurateAccountNo = '';
        $this->rateIsActive = true;
    }

    #[Layout('layouts.admin', ['title' => 'Master Metode Pembayaran (POS)'])]
    public function render()
    {
        return view('livewire.admin.settings.payment-method-index');
    }
}

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
    public $business_unit_id;
    public $bank_name;
    public $account_number;
    public $account_owner;
    public $accurate_bank_no;
    public $mdr_percentage = 0;
    public $is_active = true;

    public $businessUnits = [];
    public $activeTab = 'all';

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
    public $rateAccurateAccountNo = '51.50.005';
    public $rateIsActive = true;

    protected $rules = [
        'name' => 'required|string|max:255',
        'business_unit_id' => 'required|exists:business_units,id',
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
        $units = \App\Models\BusinessUnit::where('is_active', true)->get();
        $this->businessUnits = $units;
        
        if (!$this->business_unit_id && $units->count() > 0) {
            $this->business_unit_id = $units->first()->id;
        }
        
        $query = PaymentMethod::with(['rates', 'businessUnit']);
        if ($this->activeTab !== 'all') {
            $query->where('business_unit_id', $this->activeTab);
        }
        $this->paymentMethods = $query->get();
        
        $this->loadGlAccounts();
    }

    public function updatedActiveTab()
    {
        $this->loadData();
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
        $this->business_unit_id = $method->business_unit_id;
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
            'business_unit_id' => $this->business_unit_id,
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
        if (!empty($this->businessUnits)) {
            // Livewire might cast it to an array, so we use array access or collect()
            $this->business_unit_id = collect($this->businessUnits)->first()['id'] ?? collect($this->businessUnits)->first()->id ?? null;
        } else {
            $this->business_unit_id = null;
        }
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
        $this->rateAccurateAccountNo = '51.50.005';
        $this->rateIsActive = true;
    }

    // State untuk GL Accounts
    public $accurateGlAccounts = [];
    public $isLoadingGl = false;

    #[Layout('layouts.admin', ['title' => 'Master Metode Pembayaran (POS)'])]
    public function render()
    {
        return view('livewire.admin.settings.payment-method-index');
    }

    public function loadGlAccounts()
    {
        $this->accurateGlAccounts = \App\Models\AccurateGlAccount::all();
    }

    public function syncGlAccounts()
    {
        $this->isLoadingGl = true;

        try {
            $service = app(\App\Services\AccurateService::class);
            $businessUnits = \App\Models\BusinessUnit::where('is_active', true)->get();
            $totalSynced = 0;

            foreach ($businessUnits as $unit) {
                $dbSource = $unit->code;
                try {
                    $glData = $service->getGlAccounts($dbSource);

                    if (!empty($glData)) {
                        \App\Models\AccurateGlAccount::where('database_source', $dbSource)->delete();

                        foreach ($glData as $gl) {
                            \App\Models\AccurateGlAccount::create([
                                'account_no' => $gl['no'],
                                'name' => $gl['name'],
                                'account_type' => $gl['accountType'],
                                'database_source' => $dbSource
                            ]);
                        }
                        $totalSynced += count($glData);
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Gagal sync GL Account untuk unit {$unit->name}: " . $e->getMessage());
                }
            }

            if ($totalSynced > 0) {
                $this->dispatch('toast', title: 'Berhasil', message: "$totalSynced GL Account tersinkronisasi dari semua cabang.", type: 'success');
            } else {
                $this->dispatch('toast', title: 'Info', message: "Tidak ada GL Account CASH_BANK ditemukan.", type: 'info');
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Gagal sync GL Account: ' . $e->getMessage());
            $this->dispatch('toast', title: 'Gagal', message: 'Gagal sync GL Account: ' . $e->getMessage(), type: 'error');
        }

        $this->isLoadingGl = false;
        $this->loadGlAccounts();
    }
}

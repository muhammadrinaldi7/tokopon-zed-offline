<?php

namespace App\Livewire\Zoffline\Pos\Traits;

use App\Models\Promo;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Auth;

trait WithPaymentAndPromo
{
    // ─── Payment ───────────────────────────────────────────────
    public $payments = [
        [
            'category' => '', // 'TUNAI' or 'NON-TUNAI'
            'bank_name' => '', // Selected Bank Group (e.g. BCA, QRIS)
            'payment_method_id' => '',
            'payment_method_rate_id' => '',
            'no_kontrak' => '',
            'amount' => 0,
        ]
    ];
    public $notes = '';
    public $selectedPromos = []; // Menyimpan ID promo yang dipilih

    public $paymentMode = null; // 'tunai', 'non-tunai', 'split'
    public $paymentWizardStep = 1; // 1: Mode, 2: Method, 3: MDR & Nominal, 'split_dashboard'
    public $activePaymentIndex = 0;

    public function setPaymentMode($mode)
    {
        $this->paymentMode = $mode;

        if ($mode === 'tunai') {
            $this->payments = [
                [
                    'category' => 'TUNAI',
                    'bank_name' => '',
                    'payment_method_id' => '',
                    'payment_method_rate_id' => '',
                    'no_kontrak' => '',
                    'amount' => max(0, $this->subtotal() - (int)$this->totalDiscount()),
                ]
            ];
            $this->activePaymentIndex = 0;
            $this->paymentWizardStep = 2; // Langsung pilih Kas Tunai
        } elseif ($mode === 'non-tunai') {
            $this->payments = [
                [
                    'category' => 'NON-TUNAI',
                    'bank_name' => '',
                    'payment_method_id' => '',
                    'payment_method_rate_id' => '',
                    'no_kontrak' => '',
                    'amount' => max(0, $this->subtotal() - (int)$this->totalDiscount()),
                ]
            ];
            $this->activePaymentIndex = 0;
            $this->paymentWizardStep = 1.5; // Pilih Bank Group dulu
        } elseif ($mode === 'split') {
            $this->payments = [];
            $this->paymentWizardStep = 'split_dashboard';
        }
    }

    public function addSplitPayment($category) // 'TUNAI' or 'NON-TUNAI'
    {
        $remaining = max(0, ($this->subtotal() - (int)$this->totalDiscount()) - $this->paymentsTotalBase());
        $this->payments[] = [
            'category' => $category,
            'bank_name' => '',
            'payment_method_id' => '',
            'payment_method_rate_id' => '',
            'no_kontrak' => '',
            'amount' => 0,
        ];
        $this->activePaymentIndex = count($this->payments) - 1;

        if ($category === 'NON-TUNAI') {
            $this->paymentWizardStep = 1.5; // Pilih Bank Group dulu
        } else {
            $this->paymentWizardStep = 2; // Langsung pilih metode Tunai
        }
    }

    public function selectBankGroup($bankName)
    {
        $this->payments[$this->activePaymentIndex]['bank_name'] = $bankName;
        $this->paymentWizardStep = 2; // Lanjut ke pemilihan metode
    }

    public function selectPaymentMethod($methodId)
    {
        $this->payments[$this->activePaymentIndex]['payment_method_id'] = $methodId;
        $this->payments[$this->activePaymentIndex]['payment_method_rate_id'] = ''; // reset rate
        $this->paymentWizardStep = 3;
    }

    public function savePaymentLine()
    {
        if ($this->paymentMode === 'split') {
            $this->paymentWizardStep = 'split_dashboard';
        }
    }

    public function prevPaymentWizardStep()
    {
        if ($this->paymentWizardStep === 3) {
            if (isset($this->payments[$this->activePaymentIndex])) {
                $this->payments[$this->activePaymentIndex]['payment_method_id'] = '';
                $this->payments[$this->activePaymentIndex]['payment_method_rate_id'] = '';
            }
            $this->paymentWizardStep = 2;
        } elseif ($this->paymentWizardStep === 2) {
            $cat = $this->payments[$this->activePaymentIndex]['category'] ?? '';
            if ($cat === 'NON-TUNAI') {
                $this->payments[$this->activePaymentIndex]['bank_name'] = '';
                $this->paymentWizardStep = 1.5; // Mundur ke Pilih Bank Group
            } else {
                if ($this->paymentMode === 'split') {
                    if (empty($this->payments[$this->activePaymentIndex]['payment_method_id'])) {
                        unset($this->payments[$this->activePaymentIndex]);
                        $this->payments = array_values($this->payments);
                    }
                    $this->paymentWizardStep = 'split_dashboard';
                } else {
                    $this->paymentMode = null;
                    $this->payments = [
                        [
                            'category' => '',
                            'bank_name' => '',
                            'payment_method_id' => '',
                            'payment_method_rate_id' => '',
                            'no_kontrak' => '',
                            'amount' => 0,
                        ]
                    ];
                    $this->paymentWizardStep = 1;
                }
            }
        } elseif ($this->paymentWizardStep == 1.5) {
            if ($this->paymentMode === 'split') {
                if (empty($this->payments[$this->activePaymentIndex]['payment_method_id'])) {
                    unset($this->payments[$this->activePaymentIndex]);
                    $this->payments = array_values($this->payments);
                }
                $this->paymentWizardStep = 'split_dashboard';
            } else {
                $this->paymentMode = null;
                $this->payments = [
                    [
                        'category' => '',
                        'bank_name' => '',
                        'payment_method_id' => '',
                        'payment_method_rate_id' => '',
                        'no_kontrak' => '',
                        'amount' => 0,
                    ]
                ];
                $this->paymentWizardStep = 1;
            }
        } elseif ($this->paymentWizardStep === 'split_dashboard') {
            $this->paymentMode = null;
            $this->payments = [
                [
                    'category' => '',
                    'bank_name' => '',
                    'payment_method_id' => '',
                    'payment_method_rate_id' => '',
                    'no_kontrak' => '',
                    'amount' => 0,
                ]
            ];
            $this->paymentWizardStep = 1;
        }
    }

    #[Computed]
    public function paymentsTotalBase()
    {
        return collect($this->payments)->sum(fn($p) => (float)($p['amount'] ?? 0));
    }

    #[Computed]
    public function isPaymentsValid()
    {
        $totalPaid = 0;
        $grandTotal = max(0, $this->subtotal() - (int)$this->totalDiscount());

        foreach ($this->payments as $p) {
            // Jika kategori kosong, invalid
            if (empty($p['category'])) {
                return false;
            }

            // Jika ada baris yang belum dipilih payment method-nya
            if (empty($p['payment_method_id'])) {
                return false;
            }

            // Jika Non-Tunai, harus punya rate
            if ($p['category'] === 'NON-TUNAI' && empty($p['payment_method_rate_id'])) {
                // Kecuali Transfer mungkin tidak ada rate, tapi asumsikan harus ada untuk bank
                $pm = \App\Models\PaymentMethod::find($p['payment_method_id']);
                if ($pm && $pm->rates()->where('is_active', true)->count() > 0 && empty($p['payment_method_rate_id'])) {
                    return false;
                }
            }

            $totalPaid += (float)$p['amount'];
        }

        return abs($grandTotal - $totalPaid) < 0.01;
    }

    public function addPaymentRow()
    {
        $remaining = max(0, ($this->subtotal() - (int)$this->totalDiscount()) - $this->paymentsTotalBase());
        $this->payments[] = [
            'category' => '',
            'bank_name' => '',
            'payment_method_id' => '',
            'payment_method_rate_id' => '',
            'no_kontrak' => '',
            'amount' => $remaining,
        ];
    }

    public function removePaymentRow($index)
    {
        if ($this->paymentMode === 'split' || count($this->payments) > 1) {
            unset($this->payments[$index]);
            $this->payments = array_values($this->payments);
            $this->syncSinglePaymentAmount();
        }
    }

    public function autofillRemaining($index)
    {
        $totalOther = 0;
        foreach ($this->payments as $i => $p) {
            if ($i !== $index) {
                $totalOther += (int)$p['amount'];
            }
        }
        $target = max(0, $this->subtotal() - (int)$this->totalDiscount());
        $this->payments[$index]['amount'] = max(0, $target - $totalOther);
    }

    public function syncSinglePaymentAmount()
    {
        if ($this->paymentMode === 'split') {
            return;
        }

        if (count($this->payments) === 1) {
            $this->payments[0]['amount'] = max(0, $this->subtotal() - (int)$this->totalDiscount());
        }
    }

    public function getMdrPercentage($payment)
    {
        $pmId = $payment['payment_method_id'] ?? null;
        $rateId = $payment['payment_method_rate_id'] ?? null;

        if (!$pmId) return 0;

        if ($rateId) {
            $rate = \App\Models\PaymentMethodRate::find($rateId);
            return $rate ? (float) $rate->mdr_percentage : 0;
        }

        $pm = \App\Models\PaymentMethod::find($pmId);
        return $pm ? (float) $pm->mdr_percentage : 0;
    }

    #[Computed]
    public function cashPaymentMethods()
    {
        $user = Auth::user();
        $businessUnitId = method_exists($user, 'getActiveBusinessUnitId') ? $user->getActiveBusinessUnitId() : ($user->business_unit_id ?? 1);
        $warehouseName = $user->warehouse ? trim(strtolower($user->warehouse->name)) : null;

        $methods = \App\Models\PaymentMethod::where('is_active', true)
            ->where('category', 'TUNAI')
            ->where(function ($query) use ($businessUnitId) {
                $query->where('business_unit_id', $businessUnitId)
                    ->orWhereNull('business_unit_id');
            })
            ->get();

        if ($warehouseName) {
            return $methods->filter(function ($method) use ($warehouseName) {
                $methodNameLower = strtolower(trim($method->name));

                // Pengecualian: Selalu tampilkan jika namanya mengandung 'tukar tambah'
                if (str_contains($methodNameLower, 'tukar tambah')) {
                    return true;
                }

                // Ekstrak nama lokasi dari payment method (contoh: "Tunai Banjarbaru" -> "banjarbaru")
                $methodLocation = trim(str_replace('tunai', '', $methodNameLower));
                
                if (empty($methodLocation)) return false;

                // Gunakan str_contains agar "gsk - banjarbaru" bisa cocok dengan "banjarbaru"
                return str_contains($warehouseName, $methodLocation);
            })->values();
        }

        return $methods;
    }

    #[Computed]
    public function nonCashPaymentMethods()
    {
        $user = Auth::user();
        $businessUnitId = method_exists($user, 'getActiveBusinessUnitId') ? $user->getActiveBusinessUnitId() : ($user->business_unit_id ?? 1);

        return \App\Models\PaymentMethod::where('is_active', true)
            ->where('category', 'NON-TUNAI')
            ->where(function ($query) use ($businessUnitId) {
                $query->where('business_unit_id', $businessUnitId)
                    ->orWhereNull('business_unit_id');
            })
            ->get();
    }

    #[Computed]
    public function nonCashBankGroups()
    {
        return $this->nonCashPaymentMethods()
            ->pluck('bank_name')
            ->filter()
            ->unique()
            ->values()
            ->toArray();
    }
}

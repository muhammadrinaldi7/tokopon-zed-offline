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
            'payment_method_id' => '',
            'payment_method_rate_id' => '',
            'no_kontrak' => '',
            'amount' => 0,
        ]
    ];
    public $discount_amount = 0;
    public $notes = '';
    public $selectedPromos = []; // Menyimpan ID promo yang dipilih

    public $paymentMode = null; // 'tunai', 'non-tunai', 'split'

    public function setPaymentMode($mode)
    {
        $this->paymentMode = $mode;
        
        if ($mode === 'tunai') {
            $this->payments = [
                [
                    'category' => 'TUNAI',
                    'payment_method_id' => '',
                    'payment_method_rate_id' => '',
                    'no_kontrak' => '',
                    'amount' => max(0, $this->subtotal - $this->totalDiscount),
                ]
            ];
        } elseif ($mode === 'non-tunai') {
            $this->payments = [
                [
                    'category' => 'NON-TUNAI',
                    'payment_method_id' => '',
                    'payment_method_rate_id' => '',
                    'no_kontrak' => '',
                    'amount' => max(0, $this->subtotal - $this->totalDiscount),
                ]
            ];
        } elseif ($mode === 'split') {
            $this->payments = [
                [
                    'category' => '',
                    'payment_method_id' => '',
                    'payment_method_rate_id' => '',
                    'no_kontrak' => '',
                    'amount' => 0,
                ],
                [
                    'category' => '',
                    'payment_method_id' => '',
                    'payment_method_rate_id' => '',
                    'no_kontrak' => '',
                    'amount' => 0,
                ]
            ];
        }
    }

    #[Computed]
    public function getPaymentsTotalBaseProperty()
    {
        return collect($this->payments)->sum(fn($p) => (float)($p['amount'] ?? 0));
    }

    #[Computed]
    public function getIsPaymentsValidProperty()
    {
        $totalPaid = 0;
        $grandTotal = $this->grandTotal;

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
                if ($pm && count($pm->rates) > 0 && empty($p['payment_method_rate_id'])) {
                    return false;
                }
            }

            $totalPaid += (float)$p['amount'];
        }

        return abs($grandTotal - $totalPaid) < 0.01;
    }

    #[Computed]
    public function getCashPaymentMethodsProperty()
    {
        $user = Auth::user();
        $businessUnitId = method_exists($user, 'getActiveBusinessUnitId') ? $user->getActiveBusinessUnitId() : ($user->business_unit_id ?? 1);

        return \App\Models\PaymentMethod::where('is_active', true)
            ->where('category', 'TUNAI')
            ->where(function ($query) use ($businessUnitId) {
                $query->where('business_unit_id', $businessUnitId)
                    ->orWhereNull('business_unit_id');
            })
            ->get();
    }

    #[Computed]
    public function getNonCashPaymentMethodsProperty()
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
}

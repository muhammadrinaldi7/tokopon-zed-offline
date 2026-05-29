<?php

namespace App\Livewire\Admin\Pos\Traits;

use App\Models\PaymentMethod;
use App\Models\PaymentMethodRate;
use App\Models\Promo;
use Livewire\Attributes\Computed;

/**
 * @property array $cart
 * @property int|float $subtotal
 * @property int|float $totalDiscount
 * @property int|float $totalPromoDiscount
 * @property int|float $paymentsTotalBase
 */
trait WithPayment
{
    // ─── Payment ───────────────────────────────────────────────
    public $payments = [];
    public $discount_amount = 0;
    public $selectedPromos = []; // Menyimpan ID promo yang dipilih
    public $notes = '';

    public function updatedPayments($value, $key)
    {
        // Reset rate when method changes
        if (str_contains($key, '.payment_method_id')) {
            $parts = explode('.', $key);
            $index = $parts[0];
            $this->payments[$index]['payment_method_rate_id'] = '';
        }
        $this->syncSinglePaymentAmount();
    }

    public function updatedDiscountAmount($value)
    {
        if ($value === '' || $value === null) {
            $this->discount_amount = 0;
        } else {
            $this->discount_amount = (int) $value;
        }
        $this->syncSinglePaymentAmount();
    }

    public function updatedSelectedPromos()
    {
        $this->syncSinglePaymentAmount();
    }

    public function addPaymentRow()
    {
        $remaining = max(0, ($this->subtotal - $this->totalDiscount) - $this->paymentsTotalBase);
        $this->payments[] = [
            'payment_method_id' => '',
            'payment_method_rate_id' => '',
            'amount' => $remaining,
        ];
    }

    public function removePaymentRow($index)
    {
        if (count($this->payments) > 1) {
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
        $target = max(0, $this->subtotal - $this->totalDiscount);
        $this->payments[$index]['amount'] = max(0, $target - $totalOther);
    }

    public function syncSinglePaymentAmount()
    {
        if (count($this->payments) === 1) {
            $this->payments[0]['amount'] = max(0, $this->subtotal - $this->totalDiscount);
        }
    }

    public function getMdrPercentage($payment)
    {
        $pmId = $payment['payment_method_id'] ?? null;
        $rateId = $payment['payment_method_rate_id'] ?? null;

        if (!$pmId) return 0;

        if ($rateId) {
            $rate = PaymentMethodRate::find($rateId);
            return $rate ? (float) $rate->mdr_percentage : 0;
        }

        $pm = PaymentMethod::find($pmId);
        return $pm ? (float) $pm->mdr_percentage : 0;
    }

    #[Computed]
    public function paymentsTotalBase()
    {
        return collect($this->payments)->sum(fn($p) => (float)($p['amount'] ?? 0));
    }

    public function calculateEligibleCart($promo, $forBundle = false)
    {
        $eligibleQty = 0;
        $eligibleAmount = 0;

        if ($promo->apply_to_all_items && !$forBundle) {
            $eligibleAmount = $this->subtotal;
            $eligibleQty = array_sum(array_column($this->cart, 'qty'));
        } else {
            // Jika untuk bundle, cari dari bundleSkus. Jika utama, dari skus.
            $promoSkus = $forBundle ? $promo->bundleSkus->pluck('sku')->toArray() : $promo->skus->pluck('sku')->toArray();
            
            foreach ($this->cart as $item) {
                if (in_array($item['sku'], $promoSkus)) {
                    $eligibleQty += (int)$item['qty'];
                    $eligibleAmount += ((int)$item['qty'] * (float)$item['price']);
                }
            }
        }

        // Cap reward qty untuk produk bundle
        if ($forBundle && $promo->bundle_max_qty && $eligibleQty > $promo->bundle_max_qty) {
            $avgPrice = $eligibleQty > 0 ? ($eligibleAmount / $eligibleQty) : 0;
            $eligibleQty = $promo->bundle_max_qty;
            $eligibleAmount = $eligibleQty * $avgPrice;
        }

        return ['qty' => $eligibleQty, 'amount' => $eligibleAmount];
    }

    public function isPromoEligible($promo)
    {
        // Mengecek kelayakan hanya berdasarkan Produk Utama (Main Product)
        $eligible = $this->calculateEligibleCart($promo, false);
        
        if ($promo->min_qty && $eligible['qty'] < $promo->min_qty) return false;
        if ($promo->min_transaction_amount && $eligible['amount'] < $promo->min_transaction_amount) return false;
        
        // If not apply to all items and eligible qty is 0, it's not eligible
        if (!$promo->apply_to_all_items && $eligible['qty'] == 0) return false;

        return true;
    }

    #[Computed]
    public function totalPromoDiscount()
    {
        if (empty($this->selectedPromos)) return 0;
        
        $promos = Promo::with(['skus', 'bundleSkus'])->whereIn('id', $this->selectedPromos)->get();
        $total = 0;

        foreach ($promos as $promo) {
            // 1. Kalkulasi Diskon Utama
            $eligibleMain = $this->calculateEligibleCart($promo, false);
            if ($promo->discount_type === 'fixed') {
                $total += $promo->discount_value; // Fixed amount per transaction for main discount
            } else {
                $calc = $eligibleMain['amount'] * ($promo->discount_value / 100);
                if ($promo->max_discount) $calc = min($calc, $promo->max_discount);
                $total += $calc;
            }

            // 2. Kalkulasi Diskon Tambahan (Bundle)
            if ($promo->is_bundle) {
                $eligibleBundle = $this->calculateEligibleCart($promo, true);
                if ($eligibleBundle['qty'] > 0) {
                    if ($promo->bundle_discount_type === 'fixed') {
                        // Fixed bundle discount multiplied by eligible bundle qty
                        $total += $promo->bundle_discount_value * $eligibleBundle['qty'];
                    } else {
                        $calc = $eligibleBundle['amount'] * ($promo->bundle_discount_value / 100);
                        if ($promo->bundle_max_discount) $calc = min($calc, $promo->bundle_max_discount);
                        $total += $calc;
                    }
                }
            }
        }
        return $total;
    }

    #[Computed]
    public function totalDiscount()
    {
        return (int)$this->discount_amount + $this->totalPromoDiscount;
    }

    #[Computed]
    public function activePromos()
    {
        $promos = Promo::with(['skus', 'bundleSkus'])
            ->where('is_active', true)
            ->where(function($q) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', now());
            })
            ->where(function($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now());
            })
            ->get();
            
        $eligiblePromos = [];
        foreach($promos as $promo) {
            if ($this->isPromoEligible($promo)) {
                $eligiblePromos[] = $promo;
            } else {
                if (in_array($promo->id, $this->selectedPromos)) {
                    $this->selectedPromos = array_values(array_diff($this->selectedPromos, [$promo->id]));
                }
            }
        }
        return collect($eligiblePromos);
    }

    #[Computed]
    public function isPaymentsValid()
    {
        foreach ($this->payments as $p) {
            if (empty($p['payment_method_id'])) return false;

            $pm = PaymentMethod::find($p['payment_method_id']);
            if ($pm && $pm->rates()->where('is_active', true)->count() > 0 && empty($p['payment_method_rate_id'])) {
                return false;
            }
        }

        $target = max(0, $this->subtotal - $this->totalDiscount);
        return (int) $this->paymentsTotalBase === (int) $target;
    }

    #[Computed]
    public function paymentMethods()
    {
        return PaymentMethod::where('is_active', true)->get();
    }
    
    #[Computed]
    public function mdrAmount()
    {
        $totalMdr = 0;
        foreach ($this->payments as $payment) {
            $pct = $this->getMdrPercentage($payment);
            if ($pct > 0) {
                $totalMdr += round((float)$payment['amount'] * $pct / 100, 0);
            }
        }
        return $totalMdr;
    }
}

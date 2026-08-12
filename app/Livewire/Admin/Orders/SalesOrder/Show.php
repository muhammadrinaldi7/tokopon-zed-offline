<?php

namespace App\Livewire\Admin\Orders\SalesOrder;

use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\PaymentMethod;
use App\Services\AccurateService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use App\Livewire\Zoffline\Pos\Traits\WithPaymentAndPromo;

class Show extends Component
{
    use WithPaymentAndPromo;
    public Order $order;

    // DP Form
    public $showDpModal = false;
    public $displayCustomerName = '';
    public $dp_amount;
    public $payment_method_id;
    public $payment_method_rate_id;
    public $dp_date;
    public $dp_notes;
    public $dp_contract_number;

    // Invoice Form
    
    
    public $invoice_payment_method_id;
    public $invoice_payment_method_rate_id;
    
    
    

    public function mount(Order $order)
    {
        $this->order = $order->load(['items.variant', 'user', 'businessUnit', 'payments.paymentMethod']);
        $this->displayCustomerName = $this->order->user->name ?? 'Pelanggan Umum';
        $this->dp_amount = $this->getRemainingBalance();
        $this->dp_date = Carbon::now()->format('Y-m-d');
        // dd($this->order);
    }

    #[On('orderCancellationSubmitted')]
    public function orderCancellationSubmitted()
    {
        $this->order->refresh();
    }

    public function getRemainingBalance()
    {
        $paid = $this->order->payments()->sum('amount');
        return max(0, $this->order->grand_total - $paid);
    }

    #[Computed]
    public function subtotal()
    {
        return $this->getRemainingBalance();
    }

    #[Computed]
    public function totalDiscount()
    {
        return 0; // Diskon sudah masuk ke grand_total
    }

    #[Computed]
    public function itemDiscountTotal()
    {
        return 0;
    }

    #[Computed]
    public function promoDiscountTotal()
    {
        return 0;
    }

    #[Computed]
    public function cart()
    {
        $cart = [];
        foreach ($this->order->items as $item) {
            $name = 'Unknown Product';
            $storage = '-';
            $color = '-';
            $ram = '-';

            if ($item->variant && get_class($item->variant) === \App\Models\ProductAccurate::class) {
                $name = $item->variant->name;
            } elseif ($item->variant) {
                $name = $item->variant->product->name ?? ($item->variant->secondProduct->name ?? 'Unknown');
                $storage = $item->variant->storage ?? '-';
                $color = $item->variant->color ?? '-';
                $ram = $item->variant->ram ?? '-';
            }

            $cart[] = [
                'name' => $name,
                'qty' => $item->qty,
                'price' => $item->price_at_checkout,
                'discount_amount' => $item->discount_amount,
                'subtotal' => $item->subtotal,
                'ram' => $ram,
                'storage' => $storage,
                'color' => $color,
                'serial_numbers' => [],
            ];
        }
        return $cart;
    }


    /**
     * Override dari WithPaymentAndPromo::isPaymentsValid()
     * DP boleh parsial, berbeda dengan POS yang harus exact match.
     */
    #[Computed]
    public function isPaymentsValid()
    {
        $depositToUse = $this->depositToUseAmount();
        $totalPaid = $depositToUse;

        foreach ($this->payments as $p) {
            $amount = (float) preg_replace('/[^0-9]/', '', (string)($p['amount'] ?? 0));
            if ($amount > 0) {
                // Jika kategori kosong, invalid
                if (empty($p['category'])) {
                    $this->dispatch('toast', title: 'Pilih Metode Pembayaran', message: 'Anda memiliki sisa nominal Rp '.number_format($amount,0,',','.').' yang harus dipilih metode pembayarannya. (Atau ubah nominal menjadi 0).', type: 'warning');
                    return false;
                }

                // Jika ada baris yang belum dipilih payment method-nya
                if (empty($p['payment_method_id'])) {
                    return false;
                }

                // Jika Non-Tunai, harus punya rate
                if ($p['category'] === 'NON-TUNAI' && empty($p['payment_method_rate_id'])) {
                    $pm = \App\Models\PaymentMethod::find($p['payment_method_id']);
                    if ($pm && $pm->rates()->where('is_active', true)->count() > 0 && empty($p['payment_method_rate_id'])) {
                        return false;
                    }
                }
            }
            $totalPaid += $amount;
        }

        // Jika tidak ada pembayaran yang diisi
        if ($totalPaid <= 0) {
            return false;
        }

        // Untuk DP (showDpModal), boleh parsial asal tidak melebihi sisa tagihan
        return round($totalPaid, 2) <= round($this->getRemainingBalance(), 2);
    }

    public function openDpModal()
    {
        $this->showDpModal = true;
        $this->dp_date = \Carbon\Carbon::now()->format('Y-m-d');
        $this->dp_notes = '';
        $this->dp_contract_number = '';
        
        $this->availableCustomerDeposits = \App\Models\CustomerDeposit::where('user_id', $this->order->user_id)
            ->where('status', 'AVAILABLE')
            ->where('balance', '>', 0)
            ->where('business_unit_id', $this->order->business_unit_id)
            ->where(function($q) {
                $q->whereNull('origin_order_id')
                  ->orWhere('origin_order_id', '!=', $this->order->id);
            })
            ->get()
            ->toArray();
        $this->availableCustomerDepositTotal = collect($this->availableCustomerDeposits)->sum('balance');
        $this->useCustomerDeposit = false;
        $this->isPartialPaymentAllowed = true;

        // Default nominal cash/transfer ke 0 agar tidak memaksa user membayar sisa tagihan
        $this->payments[0]['amount'] = 0;
    }
    public function saveDp()
    {
        if (!$this->isPaymentsValid()) {
            $this->dispatch('toast', title: 'Validasi Gagal', message: 'Nominal DP tidak valid.', type: 'warning');
            return;
        }

        $paymentData = $this->payments[0];
        $actualAmount = (float) preg_replace('/[^0-9]/', '', (string)($paymentData['amount'] ?? 0));
        $depositToUse = $this->depositToUseAmount();

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            if ($depositToUse > 0 && !empty($this->availableCustomerDeposits)) {
                $depositIds = collect($this->availableCustomerDeposits)->pluck('id')->toArray();
                $lockedDeposits = \App\Models\CustomerDeposit::whereIn('id', $depositIds)
                    ->where('status', 'AVAILABLE')
                    ->lockForUpdate()
                    ->get();
                
                $remainingDpNeeded = $depositToUse;
                foreach ($lockedDeposits as $existingDeposit) {
                    if ($remainingDpNeeded > 0) {
                        $useAmount = min((float)$existingDeposit->balance, $remainingDpNeeded);
                        
                        \App\Models\CustomerDepositUsage::create([
                            'customer_deposit_id' => $existingDeposit->id,
                            'order_id' => $this->order->id,
                            'amount_used' => $useAmount,
                        ]);

                        $existingDeposit->balance -= $useAmount;
                        if ($existingDeposit->balance <= 0) {
                            $existingDeposit->status = 'USED';
                        }
                        $existingDeposit->save();
                        
                        \App\Models\OrderPayment::create([
                            'order_id' => $this->order->id,
                            'xendit_external_id' => 'DP-USE-' . date('YmdHis') . rand(1000, 9999),
                            'amount' => $useAmount,
                            'status' => 'PAID',
                            'no_kontrak' => $this->dp_contract_number,
                            'paid_at' => \Carbon\Carbon::parse($this->dp_date),
                        ]);
                        
                        // Buat OrderAccurateDoc agar muncul di Peta Relasi Tokopon
                        if ($existingDeposit->accurate_invoice_no) {
                            $doc = \App\Models\OrderAccurateDoc::firstOrNew([
                                'order_id' => $this->order->id,
                                'doc_type' => 'DP_INVOICE',
                                'doc_number' => $existingDeposit->accurate_invoice_no,
                            ]);
                            $doc->amount = ($doc->amount ?? 0) + $useAmount;
                            $doc->status = 'SUCCESS';
                            $doc->save();
                        }
                        if ($existingDeposit->accurate_receipt_no) {
                            $doc = \App\Models\OrderAccurateDoc::firstOrNew([
                                'order_id' => $this->order->id,
                                'doc_type' => 'DP_RECEIPT',
                                'doc_number' => $existingDeposit->accurate_receipt_no,
                            ]);
                            $doc->amount = ($doc->amount ?? 0) + $useAmount;
                            $doc->status = 'SUCCESS';
                            $doc->save();
                        }

                        $remainingDpNeeded -= $useAmount;
                    }
                }
            }

            if ($actualAmount > 0) {
                $deposit = \App\Models\CustomerDeposit::create([
                    'user_id' => $this->order->user_id,
                    'origin_order_id' => $this->order->id,
                    'business_unit_id' => $this->order->business_unit_id,
                    'amount' => $actualAmount,
                    'balance' => $actualAmount,
                    'payment_method_id' => $paymentData['payment_method_id'],
                    'status' => 'AVAILABLE',
                    'notes' => 'DP dari SO ' . ($this->order->accurate_so_number ?? $this->order->order_number) . ' - ' . $this->dp_notes,
                    'created_by' => \Illuminate\Support\Facades\Auth::id(),
                ]);

                \App\Models\OrderPayment::create([
                    'order_id' => $this->order->id,
                    'xendit_external_id' => 'DP-SO-' . date('YmdHis') . rand(1000, 9999),
                    'amount' => $actualAmount,
                    'status' => 'PAID',
                    'payment_method_id' => $paymentData['payment_method_id'],
                    'no_kontrak' => $this->dp_contract_number,
                    'paid_at' => \Carbon\Carbon::parse($this->dp_date),
                ]);
            }

            if ($this->order->order_status === 'pending') {
                $this->order->update(['order_status' => 'down_payment']);
            }

            if ($this->getRemainingBalance() == 0) {
                $this->order->update(['order_status' => 'paid']);
            }

            // Memindahkan DB::commit ke akhir agar jika Accurate gagal, DP tidak terbuat di lokal
            try {
                if ($actualAmount > 0) {
                    $accurateService = app(\App\Services\AccurateService::class);
                    $customerUser = $this->order->user;
                    $businessUnit = $this->order->businessUnit;
                    $dbSource = $businessUnit ? $businessUnit->code : 'syihab';

                $handler = $this->order->handledBy ?? \Illuminate\Support\Facades\Auth::user();
                if (!$handler || !$handler->branch) {
                    throw new \Exception('Staf pembuat SO ini belum dialokasikan ke Cabang (Branch) tertentu.');
                }
                $branchName = $handler->branch->name;

                $accurateBranchName = $branchName;
                if ($dbSource === 'second' && !str_contains(strtolower($accurateBranchName), 'gsk')) {
                    $accurateBranchName = 'GSK ' . $accurateBranchName;
                }

                $pmId = $paymentData['payment_method_id'];
                $pm = \App\Models\PaymentMethod::find($pmId);
                $rate = null;
                if ($paymentData['payment_method_rate_id']) {
                    $rate = \App\Models\PaymentMethodRate::find($paymentData['payment_method_rate_id']);
                } elseif ($pm && $pm->rates()->where('is_active', true)->exists()) {
                    $rate = $pm->rates()->where('is_active', true)->first();
                }

                $pct = $rate ? (float) $rate->percentage : 0;
                if ($rate && !isset($rate->percentage) && isset($rate->mdr_percentage)) {
                    $pct = (float) $rate->mdr_percentage;
                }

                $rowMdr = $pct > 0 ? round((float)$paymentData['amount'] * $pct / 100, 0) : 0;
                $netReceiptAmount = (float)$paymentData['amount'] - $rowMdr;

                $detailDiscounts = [];
                if ($rowMdr > 0 && $rate && $rate->accurate_account_no) {
                    $detailDiscounts[] = [
                        'accountNo' => $rate->accurate_account_no,
                        'amount' => (float) $rowMdr,
                        'departmentName' => $accurateBranchName,
                        'discountNotes' => 'MDR DP'
                    ];
                }

                $dpInvData = [
                    'customerNo' => $customerUser->getAccurateCustomerNo($dbSource),
                    'branchName' => $accurateBranchName,
                    'dpAmount'   => (float)$paymentData['amount'],
                    'transDate'  => \Carbon\Carbon::parse($this->dp_date)->format('d/m/Y'),
                    'inclusiveTax' => false,
                    'isTaxable' => false,
                    'description' => 'Uang Muka (DP) Standalone dari SO: ' . ($this->order->accurate_so_number ?? $this->order->order_number) . ($this->dp_contract_number ? '. No Kontrak: ' . $this->dp_contract_number : '') . '. ' . $this->dp_notes,
                ];

                if ($this->dp_contract_number) {
                    $dpInvData['poNumber'] = $this->dp_contract_number;
                }

                \Illuminate\Support\Facades\Log::info('Accurate DP Invoice Payload: ' . json_encode($dpInvData));
                $dpInvResult = $accurateService->postDownPaymentInvoice($dpInvData, $dbSource);

                if (!isset($dpInvResult['r']['number'])) {
                    throw new \Exception('Gagal mendapatkan nomor Faktur Uang Muka dari Accurate.');
                }

                $dpInvoiceNo = $dpInvResult['r']['number'];
                $deposit->update(['accurate_invoice_no' => $dpInvoiceNo]);

                \App\Models\OrderAccurateDoc::create([
                    'order_id' => $this->order->id,
                    'doc_type' => 'DP_INVOICE',
                    'doc_number' => $dpInvoiceNo,
                    'accurate_id' => $dpInvResult['r']['id'] ?? null,
                    'amount' => (float)$paymentData['amount'],
                    'status' => 'SUCCESS',
                ]);

                $srData = [
                    'customerNo' => $customerUser->getAccurateCustomerNo($dbSource),
                    'branchName' => $accurateBranchName,
                    'bankNo' => $pm->accurate_bank_no ?? 'KAS-CASH',
                    'transDate' => \Carbon\Carbon::parse($this->dp_date)->format('d/m/Y'),
                    'receiptAmount' => (float)$netReceiptAmount,
                    'chequeAmount' => (float)$netReceiptAmount,
                    'description' => 'Penerimaan DP Standalone dari SO: ' . ($this->order->accurate_so_number ?? $this->order->order_number) . ($this->dp_contract_number ? '. No Kontrak: ' . $this->dp_contract_number : '') . '. ' . $this->dp_notes,
                    'detailInvoice' => [
                        [
                            'invoiceNo' => $dpInvoiceNo,
                            'paymentAmount' => (float)$paymentData['amount'],
                        ]
                    ]
                ];

                if (!empty($detailDiscounts)) {
                    $srData['detailInvoice'][0]['detailDiscount'] = $detailDiscounts;
                }

                \Illuminate\Support\Facades\Log::info('Accurate DP Receipt Payload: ' . json_encode($srData));
                $srResult = $accurateService->postSalesReceipt($srData, $dbSource);

                if (isset($srResult['r']['number'])) {
                    $deposit->update(['accurate_receipt_no' => $srResult['r']['number']]);
                    \App\Models\OrderAccurateDoc::create([
                        'order_id' => $this->order->id,
                        'doc_type' => 'DP_RECEIPT',
                        'doc_number' => $srResult['r']['number'],
                        'accurate_id' => $srResult['r']['id'] ?? null,
                        'amount' => (float)$paymentData['amount'],
                        'status' => 'SUCCESS',
                    ]);
                }
            } // End of if ($actualAmount > 0)
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Accurate DP Sync Error: ' . $e->getMessage());
            throw new \Exception('DP gagal tersinkron ke Accurate: ' . $e->getMessage());
        }

        \Illuminate\Support\Facades\DB::commit();

        $this->showDpModal = false;
        $this->order->refresh();

        $this->dispatch('toast', title: 'Berhasil', message: 'Uang Muka (DP) berhasil dicatat!', type: 'success');
        
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            $this->dispatch('toast', title: 'Error', message: 'Gagal menyimpan DP: ' . $e->getMessage(), type: 'error');
        }
    }

    public function render()
    {
        return view('livewire.admin.orders.sales-order.show', [
            'paymentMethods' => PaymentMethod::where('is_active', true)
                ->where('business_unit_id', $this->order->business_unit_id)
                ->get()
        ])->layout('layouts.z');
    }
}

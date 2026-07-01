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
    public $showInvoiceModal = false;
    public $invoice_sns = [];
    public $invoice_payment_method_id;
    public $invoice_payment_method_rate_id;
    public $invoice_date;
    public $invoice_notes;
    public $invoice_contract_number;

    public function mount(Order $order)
    {
        $this->order = $order->load(['items.variant', 'user', 'businessUnit', 'payments.paymentMethod']);
        $this->displayCustomerName = $this->order->user->name ?? 'Pelanggan Umum';
        $this->dp_amount = $this->getRemainingBalance();
        $this->dp_date = Carbon::now()->format('Y-m-d');
        // dd($this->order);
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


    #[Computed]
    public function isPaymentsValid()
    {
        $totalPaid = 0;

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
                $pm = \App\Models\PaymentMethod::find($p['payment_method_id']);
                if ($pm && $pm->rates()->where('is_active', true)->count() > 0 && empty($p['payment_method_rate_id'])) {
                    return false;
                }
            }

            $totalPaid += (float)$p['amount'];
        }

        // Jika tidak ada pembayaran yang diisi
        if ($totalPaid <= 0) {
            return false;
        }

        // Untuk pelunasan invoice (showInvoiceModal), harus sama persis
        if ($this->showInvoiceModal) {
            $grandTotal = max(0, $this->subtotal() - (int)$this->totalDiscount());
            return abs($grandTotal - $totalPaid) < 0.01;
        }

        // Untuk DP (showDpModal), boleh parsial asal tidak melebihi sisa tagihan
        return $totalPaid <= $this->getRemainingBalance();
    }

    public function openDpModal()
    {
        $this->showDpModal = true;
        $this->dp_date = \Carbon\Carbon::now()->format('Y-m-d');
        $this->dp_notes = '';
        $this->dp_contract_number = '';

        // $this->setPaymentMode('tunai');
        $this->payments[0]['amount'] = $this->getRemainingBalance();
    }
    public function saveDp()
    {
        if (!$this->isPaymentsValid()) {
            $this->dispatch('toast', title: 'Validasi Gagal', message: 'Harap periksa kembali isian pembayaran Anda.', type: 'warning');
            return;
        }

        $paymentData = $this->payments[0];

        try {
            DB::beginTransaction();

            $payment = OrderPayment::create([
                'order_id' => $this->order->id,
                'payment_method_id' => $paymentData['payment_method_id'],
                'payment_method_rate_id' => $paymentData['payment_method_rate_id'] ?: null,
                'amount' => $paymentData['amount'],
                'status' => 'PAID',
                'xendit_external_id' => 'DP-MANUAL-' . date('YmdHis') . rand(1000, 9999),
                'paid_at' => \Carbon\Carbon::parse($this->dp_date),
                'payment_payload' => [
                    'notes' => $this->dp_notes,
                    'contract_number' => $this->dp_contract_number,
                ],
            ]);

            // Update Order Status if needed
            if ($this->order->order_status === 'pending') {
                $this->order->update(['order_status' => 'down_payment']);
            }

            // Check if fully paid
            if ($this->getRemainingBalance() == 0) {
                // Not automatically completed since delivery/invoice is needed, but we can mark it
                $this->order->update(['order_status' => 'paid']);
            }

            DB::commit();

            // Trigger Sync to Accurate (Down Payment)
            try {
                $accurateService = app(AccurateService::class);
                $customerUser = $this->order->user;
                $businessUnit = $this->order->businessUnit;
                $dbSource = $businessUnit ? $businessUnit->code : 'syihab';

                $handler = $this->order->handledBy ?? Auth::user();
                if (!$handler || !$handler->branch) {
                    throw new \Exception('Staf pembuat SO ini belum dialokasikan ke Cabang (Branch) tertentu.');
                }
                $branchName = $handler->branch->name;

                $accurateBranchName = $branchName;
                if ($dbSource === 'second' && !str_contains(strtolower($accurateBranchName), 'gsk')) {
                    $accurateBranchName = 'GSK ' . $accurateBranchName;
                }

                // Hitung MDR
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

                if (!$this->order->accurate_so_number) {
                    throw new \Exception('Sales Order ini belum memiliki nomor sinkronisasi Accurate. Harap sinkronkan/buat SO di Accurate terlebih dahulu sebelum mencatat DP.');
                }

                // STEP 1: Faktur Uang Muka Penjualan (Down Payment Invoice)
                $dpInvData = [
                    'customerNo' => $customerUser->getAccurateCustomerNo($dbSource),
                    'branchName' => $accurateBranchName,
                    'dpAmount'   => (float)$paymentData['amount'],
                    'soNumber'   => $this->order->accurate_so_number,
                    'transDate'  => Carbon::parse($this->dp_date)->format('d/m/Y'),
                    'inclusiveTax' => false,
                    'isTaxable' => false,
                    'description' => 'Uang Muka (DP) SO: ' . $this->order->accurate_so_number . ($this->dp_contract_number ? '. No Kontrak: ' . $this->dp_contract_number : '') . '. ' . $this->dp_notes,
                ];

                if ($this->dp_contract_number) {
                    $dpInvData['poNumber'] = $this->dp_contract_number;
                }

                Log::info('Accurate DP Invoice Payload: ' . json_encode($dpInvData));
                $dpInvResult = $accurateService->postDownPaymentInvoice($dpInvData, $dbSource);

                if (!isset($dpInvResult['r']['number'])) {
                    throw new \Exception('Gagal mendapatkan nomor Faktur Uang Muka dari Accurate.');
                }

                $dpInvoiceNo = $dpInvResult['r']['number'];

                \App\Models\OrderAccurateDoc::create([
                    'order_id' => $this->order->id,
                    'doc_type' => 'DP_INVOICE',
                    'doc_number' => $dpInvoiceNo,
                    'accurate_id' => $dpInvResult['r']['id'] ?? null,
                    'amount' => (float) $paymentData['amount'],
                    'status' => 'SUCCESS',
                ]);

                // STEP 2: Penerimaan Penjualan (Sales Receipt) untuk Uang Muka
                $srData = [
                    'customerNo' => $customerUser->getAccurateCustomerNo($dbSource),
                    'branchName' => $accurateBranchName,
                    'bankNo' => $pm->accurate_bank_no ?? 'KAS-CASH',
                    'transDate' => Carbon::parse($this->dp_date)->format('d/m/Y'),
                    'receiptAmount' => (float)$netReceiptAmount,
                    'chequeAmount' => (float)$netReceiptAmount,
                    'description' => 'Penerimaan DP SO: ' . $this->order->accurate_so_number . ($this->dp_contract_number ? '. No Kontrak: ' . $this->dp_contract_number : '') . '. ' . $this->dp_notes,
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

                Log::info('Accurate DP Receipt Payload: ' . json_encode($srData));
                $srResult = $accurateService->postSalesReceipt($srData, $dbSource);

                if (isset($srResult['r']['number'])) {
                    \App\Models\OrderAccurateDoc::create([
                        'order_id' => $this->order->id,
                        'doc_type' => 'DP_RECEIPT',
                        'doc_number' => $srResult['r']['number'],
                        'accurate_id' => $srResult['r']['id'] ?? null,
                        'amount' => (float) $netReceiptAmount,
                        'status' => 'SUCCESS',
                    ]);

                    if (!$this->order->accurate_receipt_no) {
                        $this->order->update(['accurate_receipt_no' => $srResult['r']['number']]);
                    } else {
                        $this->order->update(['accurate_receipt_no' => $this->order->accurate_receipt_no . ', ' . $srResult['r']['number']]);
                    }
                }
            } catch (\Exception $e) {
                Log::error('Accurate DP Sync Error: ' . $e->getMessage());
                $this->dispatch('toast', title: 'Sync Accurate Gagal', message: 'DP tersimpan di sistem, namun gagal tersinkron ke Accurate: ' . $e->getMessage(), type: 'warning');
            }

            $this->showDpModal = false;
            $this->order->refresh();

            $this->dispatch('toast', title: 'Berhasil', message: 'Uang Muka (DP) berhasil dicatat!', type: 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', title: 'Error', message: 'Gagal menyimpan DP: ' . $e->getMessage(), type: 'error');
        }
    }

    public function openInvoiceModal()
    {
        $this->invoice_sns = [];
        foreach ($this->order->items as $item) {
            $existing = array_filter(array_map('trim', explode(',', $item->serial_number ?? '')));
            $sns = [];
            for ($i = 0; $i < $item->qty; $i++) {
                $sns[] = $existing[$i] ?? '';
            }
            $this->invoice_sns[$item->id] = $sns;
        }
        $this->invoice_date = \Carbon\Carbon::now()->format('Y-m-d');
        $this->invoice_notes = '';
        $this->invoice_contract_number = '';

        // $this->setPaymentMode('tunai');
        $this->payments[0]['amount'] = $this->getRemainingBalance();

        $this->showInvoiceModal = true;
    }

    public function submitFaktur()
    {
        $rules = [
            'invoice_sns.*.*' => 'nullable|string'
        ];

        $this->validate($rules);

        $remBal = $this->getRemainingBalance();
        if ($remBal > 0) {
            if (!$this->isPaymentsValid()) {
                $this->dispatch('toast', title: 'Validasi Gagal', message: 'Harap periksa kembali isian pembayaran pelunasan Anda.', type: 'warning');
                return;
            }
        }

        try {
            DB::beginTransaction();

            // Trigger Sync to Accurate (Sales Invoice from SO)
            $accurateService = app(AccurateService::class);
            $businessUnit = $this->order->businessUnit;
            $dbSource = $businessUnit ? $businessUnit->code : 'syihab';

            $handler = $this->order->handledBy ?? Auth::user();
            if (!$handler || !$handler->branch) {
                throw new \Exception('Staf pembuat SO ini belum dialokasikan ke Cabang (Branch) tertentu.');
            }
            $branchName = $handler->branch->name;

            $accurateBranchName = $branchName;
            if ($dbSource === 'second' && !str_contains(strtolower($accurateBranchName), 'gsk')) {
                $accurateBranchName = 'GSK ' . $accurateBranchName;
            }

            $detailSalesman = [];
            if ($this->order->salesBy && !empty($this->order->salesBy->employee_no)) {
                $detailSalesman[] = (string) $this->order->salesBy->employee_no;
            }

            $detailItems = [];
            foreach ($this->order->items as $item) {
                // Update local serial numbers first
                $snArray = $this->invoice_sns[$item->id] ?? [];
                if (is_array($snArray)) {
                    $snInput = implode(', ', array_filter(array_map('trim', $snArray)));
                } else {
                    $snInput = trim($snArray);
                }

                if ($snInput !== ($item->serial_number ?? '')) {
                    $item->update(['serial_number' => $snInput]);
                }

                $variant = $item->variant;
                if ($variant && get_class($variant) === \App\Models\ProductAccurate::class) {
                    $itemName = $variant->name;
                    $sku = $variant->item_no ?? null;
                    $detailName = trim($itemName);
                } else {
                    $isNew = $item->product_variant_type === \App\Models\ProductVariant::class;
                    $itemName = $isNew ? ($variant->product->name ?? 'Unknown') : ($variant->secondProduct->name ?? 'Unknown');
                    $sku = $variant->sku ?? null;
                    $detailName = trim($itemName . ' ' . ($variant->color ?? '') . ' ' . ($variant->storage ?? ''));
                }

                if (empty($sku)) {
                    throw new \Exception("Gagal: Produk '{$itemName}' belum memiliki SKU (Item No). Harap lengkapi SKU produk di database agar bisa dikirim ke Accurate.");
                }

                $detailItemData = [
                    'itemNo' => $sku,
                    'unitPrice' => (float)$item->price_at_checkout,
                    'quantity' => (float)$item->qty,
                    'detailName' => $detailName,
                    'itemCashDiscount' => (float)$item->discount_amount,
                ];

                if (!empty($detailSalesman)) {
                    $detailItemData['salesmanListNumber'] = $detailSalesman;
                }

                // Attach SN payload
                $cleanSNs = array_filter(array_map('trim', explode(',', $snInput)));
                if (count($cleanSNs) > 0) {
                    $detailSNs = [];
                    foreach ($cleanSNs as $sn) {
                        $detailSNs[] = ['serialNumberNo' => $sn, 'quantity' => 1];
                    }
                    $detailItemData['detailSerialNumber'] = $detailSNs;
                }

                $detailItems[] = $detailItemData;
            }

            $siData = [
                'customerNo' => $this->order->user->getAccurateCustomerNo($dbSource),
                'branchName' => $accurateBranchName,
                'transDate' => now()->format('d/m/Y'),
                'detailItem' => $detailItems,
                'inclusiveTax' => true,
                'taxable' => true,
                'description' => 'Pelunasan SO: ' . ($this->order->accurate_so_number ?? $this->order->order_number) . ($this->invoice_contract_number ? '. No Kontrak: ' . $this->invoice_contract_number : '')
            ];

            if ($this->invoice_contract_number) {
                $siData['poNumber'] = $this->invoice_contract_number;
            }

            $doDoc = $this->order->accurateDocs()
                ->where('doc_type', 'DELIVERY_ORDER')
                ->where('status', 'SUCCESS')
                ->first();

            if ($doDoc) {
                foreach ($siData['detailItem'] as &$i) {
                    $i['deliveryOrderNumber'] = $doDoc->doc_number;
                }
            } elseif ($this->order->accurate_so_number) {
                foreach ($siData['detailItem'] as &$i) {
                    $i['salesOrderNumber'] = $this->order->accurate_so_number;
                }
            }

            // Apply DP only if the DP was successfully paid (DP_RECEIPT exists)
            $dpInvoices = $this->order->accurateDocs()
                ->where('doc_type', 'DP_INVOICE')
                ->where('status', 'SUCCESS')
                ->get();

            $validDpInvoices = [];
            foreach ($dpInvoices as $dpInv) {
                // Check if this DP Invoice was actually paid via DP_RECEIPT
                // We link them by checking if there's any successful DP_RECEIPT for this order
                // Actually, a better way is to verify if there's a DP_RECEIPT created after this DP_INVOICE.
                // In Accurate, if it's unpaid, it errors out.
                $hasReceipt = $this->order->accurateDocs()
                    ->where('doc_type', 'DP_RECEIPT')
                    ->where('status', 'SUCCESS')
                    ->where('created_at', '>=', $dpInv->created_at)
                    ->exists();

                if ($hasReceipt) {
                    $validDpInvoices[] = [
                        'invoiceNumber' => $dpInv->doc_number,
                        'paymentAmount' => (float) $dpInv->amount,
                    ];
                }
            }

            if (count($validDpInvoices) > 0) {
                $siData['detailDownPayment'] = $validDpInvoices;
            }

            if ($remBal > 0) {
                $mdrExpenses = [];
                foreach ($this->payments as $payment) {
                    $rate = $payment['payment_method_rate_id'] ? \App\Models\PaymentMethodRate::find($payment['payment_method_rate_id']) : null;
                    $pct = $this->getMdrPercentage($payment);
                    $rowMdr = $pct > 0 ? round((float)$payment['amount'] * $pct / 100, 0) : 0;

                    if ($rowMdr > 0 && $rate && $rate->accurate_account_no) {
                        $mdrExpenses[] = [
                            'accountNo' => $rate->accurate_account_no,
                            'expenseAmount' => -abs((float)$rowMdr),
                            'expenseNotes' => 'MDR ' . ($rate->name ?? ' ')
                        ];
                    }
                }

                if (!empty($mdrExpenses)) {
                    $siData['detailExpense'] = $mdrExpenses;
                }
            }

            Log::info('Accurate SI Sync Payload: ' . json_encode($siData));
            $siResult = $accurateService->postSalesInvoice($siData, $dbSource);

            if (isset($siResult['r']['number'])) {
                $this->order->update([
                    'accurate_invoice_no' => $siResult['r']['number'],
                    'order_status' => 'COMPLETED'
                ]);

                \App\Models\OrderAccurateDoc::create([
                    'order_id' => $this->order->id,
                    'doc_type' => 'SALES_INVOICE',
                    'doc_number' => $siResult['r']['number'],
                    'accurate_id' => $siResult['r']['id'] ?? null,
                    'amount' => $this->order->grand_total,
                    'status' => 'SUCCESS',
                ]);

                // Settlement if balance > 0
                if ($remBal > 0) {
                    foreach ($this->payments as $paymentData) {
                        $feeAmount = 0;
                        $netReceiptAmount = (float)$paymentData['amount'];
                        $rate = null;

                        $pmId = $paymentData['payment_method_id'];
                        $pm = \App\Models\PaymentMethod::find($pmId);

                        if ($pm && $paymentData['payment_method_rate_id']) {
                            $rate = \App\Models\PaymentMethodRate::find($paymentData['payment_method_rate_id']);
                        } elseif ($pm && $pm->rates()->where('is_active', true)->exists()) {
                            $rate = $pm->rates()->where('is_active', true)->first();
                        }

                        if ($rate) {
                            $feePercentage = $rate->percentage ?? $rate->mdr_percentage;
                            $feeAmount = ((float)$paymentData['amount'] * $feePercentage) / 100;
                            $netReceiptAmount = (float)$paymentData['amount'] - $feeAmount;
                        }

                        \App\Models\OrderPayment::create([
                            'order_id' => $this->order->id,
                            'payment_method_id' => $paymentData['payment_method_id'],
                            'payment_method_rate_id' => $paymentData['payment_method_rate_id'] ?: null,
                            'amount' => $paymentData['amount'],
                            'fee_amount' => $feeAmount,
                            'payment_date' => $this->invoice_date,
                            'notes' => $this->invoice_notes,
                            'status' => 'PAID',
                            'xendit_external_id' => 'PELUNASAN-' . date('YmdHis') . rand(1000, 9999),
                            'paid_at' => \Carbon\Carbon::parse($this->invoice_date),
                            'payment_payload' => [
                                'notes' => $this->invoice_notes,
                                'contract_number' => $this->invoice_contract_number,
                            ],
                        ]);

                        $srData = [
                            'customerNo' => $this->order->user->getAccurateCustomerNo($dbSource),
                            'branchName' => $accurateBranchName,
                            'bankNo' => $pm->accurate_bank_no ?? 'KAS-CASH',
                            'transDate' => \Carbon\Carbon::parse($this->invoice_date)->format('d/m/Y'),
                            'receiptAmount' => (float)$netReceiptAmount,
                            'chequeAmount' => (float)$netReceiptAmount,
                            'description' => 'Pelunasan Faktur SO: ' . ($this->order->accurate_so_number ?? $this->order->order_number) . ($this->invoice_contract_number ? '. No Kontrak: ' . $this->invoice_contract_number : '') . '. ' . $this->invoice_notes,
                            'detailInvoice' => [
                                [
                                    'invoiceNo' => $siResult['r']['number'],
                                    'paymentAmount' => (float)$paymentData['amount'],
                                ]
                            ]
                        ];

                        if ($feeAmount > 0) {
                            $srData['detailInvoice'][0]['detailDiscount'] = [
                                [
                                    'accountNo' => $rate->accurate_account_no ?? '7100.04',
                                    'amount' => (float)$feeAmount,
                                    'departmentName' => $accurateBranchName,
                                    'discountNotes' => 'Potongan MDR ' . ($rate->name ?? 'Payment Gateway'),
                                ]
                            ];
                        }

                        Log::info('Accurate SR Invoice Settlement Payload: ' . json_encode($srData));
                        $srResult = $accurateService->postSalesReceipt($srData, $dbSource);

                        if (isset($srResult['r']['number'])) {
                            \App\Models\OrderAccurateDoc::create([
                                'order_id' => $this->order->id,
                                'doc_type' => 'SALES_RECEIPT',
                                'doc_number' => $srResult['r']['number'],
                                'accurate_id' => $srResult['r']['id'] ?? null,
                                'amount' => $paymentData['amount'],
                                'status' => 'SUCCESS',
                            ]);
                        }
                    }
                }
            }

            DB::commit();
            $this->showInvoiceModal = false;
            $this->dispatch('toast', title: 'Berhasil', message: 'Faktur Penjualan diterbitkan dan pelunasan selesai!', type: 'success');
            $this->order->refresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Accurate SI Sync Error: ' . $e->getMessage());
            $this->dispatch('toast', title: 'Error', message: 'Gagal membuat faktur: ' . $e->getMessage(), type: 'error');
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

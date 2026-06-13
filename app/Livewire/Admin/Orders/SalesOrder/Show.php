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

class Show extends Component
{
    public Order $order;

    // DP Form
    public $showDpModal = false;
    public $dp_amount;
    public $payment_method_id;
    public $payment_method_rate_id;
    public $dp_date;
    public $dp_notes;

    // Invoice Form
    public $showInvoiceModal = false;
    public $invoice_sns = [];
    public $invoice_payment_method_id;
    public $invoice_payment_method_rate_id;
    public $invoice_date;
    public $invoice_notes;

    public function mount(Order $order)
    {
        $this->order = $order->load(['items.variant', 'user', 'businessUnit', 'payments.paymentMethod']);
        $this->dp_amount = $this->getRemainingBalance();
        $this->dp_date = Carbon::now()->format('Y-m-d');
    }

    public function getRemainingBalance()
    {
        $paid = $this->order->payments()->sum('amount');
        return max(0, $this->order->grand_total - $paid);
    }

    public function updatedPaymentMethodId($val)
    {
        $this->payment_method_rate_id = null;
    }

    #[Computed]
    public function getSelectedPaymentMethodProperty()
    {
        if (!$this->payment_method_id) return null;
        return \App\Models\PaymentMethod::with(['rates' => function ($q) {
            $q->where('is_active', true);
        }])->find($this->payment_method_id);
    }

    public function updatedInvoicePaymentMethodId($val)
    {
        $this->invoice_payment_method_rate_id = null;
    }

    #[Computed]
    public function getSelectedInvoicePaymentMethodProperty()
    {
        if (!$this->invoice_payment_method_id) return null;
        return \App\Models\PaymentMethod::with(['rates' => function ($q) {
            $q->where('is_active', true);
        }])->find($this->invoice_payment_method_id);
    }

    public function saveDp()
    {
        $rules = [
            'dp_amount' => 'required|numeric|min:1|max:' . $this->getRemainingBalance(),
            'payment_method_id' => 'required',
            'dp_date' => 'required|date',
        ];

        $pm = $this->selectedPaymentMethod;
        if ($pm && $pm->rates->count() > 0) {
            $rules['payment_method_rate_id'] = 'required';
        }

        $this->validate($rules);

        try {
            DB::beginTransaction();

            $payment = OrderPayment::create([
                'order_id' => $this->order->id,
                'payment_method_id' => $this->payment_method_id,
                'payment_method_rate_id' => $this->payment_method_rate_id ?: null,
                'amount' => $this->dp_amount,
                'status' => 'PAID',
                'xendit_external_id' => 'DP-MANUAL-' . date('YmdHis') . rand(1000, 9999),
                'paid_at' => \Carbon\Carbon::parse($this->dp_date),
                'payment_payload' => [
                    'notes' => $this->dp_notes,
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
                $rate = null;
                if ($this->payment_method_rate_id) {
                    $rate = \App\Models\PaymentMethodRate::find($this->payment_method_rate_id);
                } elseif ($pm->rates()->where('is_active', true)->exists()) {
                    $rate = $pm->rates()->where('is_active', true)->first();
                }

                $pct = $rate ? (float) $rate->percentage : 0;
                // fallback to mdr_percentage column if percentage not found
                if ($rate && !isset($rate->percentage) && isset($rate->mdr_percentage)) {
                    $pct = (float) $rate->mdr_percentage;
                }

                $rowMdr = $pct > 0 ? round((float)$this->dp_amount * $pct / 100, 0) : 0;
                $netReceiptAmount = (float)$this->dp_amount - $rowMdr;

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
                    'dpAmount'   => (float)$this->dp_amount,
                    'soNumber'   => $this->order->accurate_so_number,
                    'transDate'  => Carbon::parse($this->dp_date)->format('d/m/Y'),
                    'description' => 'Uang Muka (DP) SO: ' . $this->order->accurate_so_number . '. ' . $this->dp_notes,
                ];

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
                    'amount' => (float) $this->dp_amount,
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
                    'description' => 'Penerimaan DP SO: ' . $this->order->accurate_so_number . '. ' . $this->dp_notes,
                    'detailInvoice' => [
                        [
                            'invoiceNo' => $dpInvoiceNo,
                            'paymentAmount' => (float)$this->dp_amount,
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
            $this->invoice_sns[$item->id] = $item->serial_number ?? '';
        }
        $this->invoice_payment_method_id = null;
        $this->invoice_payment_method_rate_id = null;
        $this->invoice_date = \Carbon\Carbon::now()->format('Y-m-d');
        $this->invoice_notes = '';
        $this->showInvoiceModal = true;
    }

    public function submitFaktur()
    {
        $rules = [
            'invoice_sns.*' => 'nullable|string'
        ];

        $remBal = $this->getRemainingBalance();
        if ($remBal > 0) {
            $rules['invoice_payment_method_id'] = 'required';
            $rules['invoice_date'] = 'required|date';
            
            if ($this->selectedInvoicePaymentMethod && $this->selectedInvoicePaymentMethod->rates->count() > 0) {
                $rules['invoice_payment_method_rate_id'] = 'required';
            }
        }

        $this->validate($rules);

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

            $detailItems = [];
            foreach ($this->order->items as $item) {
                // Update local serial numbers first
                $snInput = $this->invoice_sns[$item->id] ?? '';
                if ($snInput !== ($item->serial_number ?? '')) {
                    $item->update(['serial_number' => $snInput]);
                }

                $variant = $item->variant;
                $isNew = $item->product_variant_type === \App\Models\ProductVariant::class;
                $itemName = $isNew ? ($variant->product->name ?? 'Unknown') : ($variant->secondProduct->name ?? 'Unknown');

                $sku = $variant->sku ?? null;
                if (empty($sku)) {
                    throw new \Exception("Gagal: Produk '{$itemName}' belum memiliki SKU (Item No). Harap lengkapi SKU produk di database agar bisa dikirim ke Accurate.");
                }

                $detailItemData = [
                    'itemNo' => $sku,
                    'unitPrice' => (float)$item->price_at_checkout,
                    'quantity' => (float)$item->qty,
                    'detailName' => $itemName . ' ' . ($variant->color ?? '') . ' ' . ($variant->storage ?? ''),
                    'itemCashDiscount' => (float)$item->discount_amount,
                ];

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
                'description' => 'Pelunasan SO: ' . ($this->order->accurate_so_number ?? $this->order->order_number)
            ];

            if ($this->order->accurate_so_number) {
                // To link to SO, in Accurate usually we pass salesOrderId per item, but if the API supports it at header level we use it.
                // Alternatively, we map salesOrderNo to each detailItem
                foreach ($siData['detailItem'] as &$i) {
                    $i['salesOrderNo'] = $this->order->accurate_so_number;
                }
            }

            $dpDocs = $this->order->accurateDocs()->where('doc_type', 'DP_INVOICE')->where('status', 'SUCCESS')->get();
            if ($dpDocs->count() > 0) {
                $siData['detailDownPayment'] = [];
                foreach ($dpDocs as $dpDoc) {
                    $siData['detailDownPayment'][] = [
                        'invoiceNumber' => $dpDoc->doc_number,
                        'paymentAmount' => (float) $dpDoc->amount,
                    ];
                }
            }

            Log::info('Accurate SI Sync Payload: ' . json_encode($siData));
            $siResult = $accurateService->postSalesInvoice($siData, $dbSource);

            if (isset($siResult['r']['number'])) {
                $this->order->update([
                    'accurate_invoice_no' => $siResult['r']['number'],
                    'order_status' => 'completed'
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
                    $feeAmount = 0;
                    $netReceiptAmount = $remBal;
                    $rate = null;
                    $pm = $this->selectedInvoicePaymentMethod;

                    if ($pm && $pm->rates->count() > 0 && $this->invoice_payment_method_rate_id) {
                        $rate = $pm->rates->where('id', $this->invoice_payment_method_rate_id)->first();
                        if ($rate) {
                            $feePercentage = $rate->percentage ?? $rate->mdr_percentage;
                            $feeAmount = ($remBal * $feePercentage) / 100;
                            $netReceiptAmount = $remBal - $feeAmount;
                        }
                    }

                    \App\Models\OrderPayment::create([
                        'order_id' => $this->order->id,
                        'payment_method_id' => $this->invoice_payment_method_id,
                        'payment_method_rate_id' => $this->invoice_payment_method_rate_id,
                        'amount' => $remBal,
                        'fee_amount' => $feeAmount,
                        'payment_date' => $this->invoice_date,
                        'notes' => $this->invoice_notes,
                        'status' => 'paid',
                    ]);

                    $srData = [
                        'customerNo' => $this->order->user->getAccurateCustomerNo($dbSource),
                        'branchName' => $accurateBranchName,
                        'bankNo' => $pm->accurate_bank_no ?? 'KAS-CASH',
                        'transDate' => \Carbon\Carbon::parse($this->invoice_date)->format('d/m/Y'),
                        'receiptAmount' => (float)$netReceiptAmount,
                        'chequeAmount' => (float)$netReceiptAmount,
                        'description' => 'Pelunasan Faktur SO: ' . ($this->order->accurate_so_number ?? $this->order->order_number) . '. ' . $this->invoice_notes,
                        'detailInvoice' => [
                            [
                                'invoiceNo' => $siResult['r']['number'],
                                'paymentAmount' => (float)$remBal,
                            ]
                        ]
                    ];

                    if ($feeAmount > 0) {
                        $srData['detailOtherDeposit'] = [
                            [
                                'accountNo' => '7100.04',
                                'amount' => (float)$feeAmount,
                                'departmentName' => $accurateBranchName,
                                'notes' => 'Potongan MDR ' . ($rate->name ?? 'Payment Gateway'),
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
                            'amount' => $remBal,
                            'status' => 'SUCCESS',
                        ]);
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
        ])->layout('layouts.admin');
    }
}

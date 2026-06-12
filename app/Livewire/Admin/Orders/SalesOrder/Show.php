<?php

namespace App\Livewire\Admin\Orders\SalesOrder;

use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\PaymentMethod;
use App\Services\AccurateService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class Show extends Component
{
    public Order $order;
    
    // DP Form
    public $showDpModal = false;
    public $dp_amount;
    public $payment_method_id;
    public $dp_date;
    public $dp_notes;

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

    public function saveDp()
    {
        $this->validate([
            'dp_amount' => 'required|numeric|min:1|max:' . $this->getRemainingBalance(),
            'payment_method_id' => 'required',
            'dp_date' => 'required|date',
        ]);

        try {
            DB::beginTransaction();

            $payment = OrderPayment::create([
                'order_id' => $this->order->id,
                'payment_method_id' => $this->payment_method_id,
                'amount' => $this->dp_amount,
                'payment_date' => $this->dp_date,
                'status' => 'paid',
                'notes' => $this->dp_notes,
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
                $branchName = $businessUnit->name ?? 'Banjarbaru';
                $dbSource = $businessUnit ? $businessUnit->code : 'syihab';
                
                $accurateBranchName = $branchName;
                if ($dbSource === 'second' && !str_contains(strtolower($accurateBranchName), 'gsk')) {
                    $accurateBranchName = 'GSK ' . $accurateBranchName;
                }

                $pm = PaymentMethod::find($this->payment_method_id);
                // Hitung MDR
                $rate = $pm->rates()->where('is_active', true)->first(); // Atau ambil dari input jika ada
                $pct = $rate ? (float) $rate->percentage : 0;
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

                $srData = [
                    'customerNo' => $customerUser->getAccurateCustomerNo($dbSource),
                    'branchName' => $accurateBranchName,
                    'bankNo' => $pm->accurate_bank_no ?? 'KAS-CASH',
                    'transDate' => Carbon::parse($this->dp_date)->format('d/m/Y'),
                    'receiptAmount' => (float)$netReceiptAmount,
                    'chequeAmount' => (float)$netReceiptAmount,
                    'description' => 'Down Payment (DP) SO: ' . ($this->order->accurate_so_number ?? $this->order->order_number) . '. ' . $this->dp_notes
                ];

                if ($this->order->accurate_so_number) {
                    $srData['detailDownPayment'] = [
                        [
                            'salesOrderNo' => $this->order->accurate_so_number,
                            'paymentAmount' => (float)$this->dp_amount,
                        ]
                    ];
                }
                if (!empty($detailDiscounts)) {
                    $srData['detailDownPayment'][0]['detailDiscount'] = $detailDiscounts;
                }

                Log::info('Accurate DP Sync Payload: ' . json_encode($srData));
                $srResult = $accurateService->postSalesReceipt($srData, $dbSource);
                
                if (isset($srResult['r']['number'])) {
                    // $payment->update(['accurate_receipt_no' => $srResult['r']['number']]); // If we had it on payment
                    
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
            session()->flash('error', 'Gagal menyimpan DP: ' . $e->getMessage());
        }
    }

    public function prosesFakturLunas()
    {
        if ($this->getRemainingBalance() > 0) {
            $this->dispatch('toast', title: 'Belum Lunas', message: 'Tidak dapat membuat faktur karena tagihan belum lunas.', type: 'warning');
            return;
        }

        try {
            DB::beginTransaction();

            // Trigger Sync to Accurate (Sales Invoice from SO)
            $accurateService = app(AccurateService::class);
            $businessUnit = $this->order->businessUnit;
            $branchName = $businessUnit->name ?? 'Banjarbaru';
            $dbSource = $businessUnit ? $businessUnit->code : 'syihab';
            
            $accurateBranchName = $branchName;
            if ($dbSource === 'second' && !str_contains(strtolower($accurateBranchName), 'gsk')) {
                $accurateBranchName = 'GSK ' . $accurateBranchName;
            }

            $detailItems = [];
            foreach ($this->order->items as $item) {
                $variant = $item->type === 'new' ? \App\Models\ProductVariant::find($item->variant_id) : \App\Models\SecondProductVariant::find($item->variant_id);
                $itemName = $item->type === 'new' ? ($variant->product->name ?? 'Unknown') : ($variant->secondProduct->name ?? 'Unknown');
                
                $detailItems[] = [
                    'itemNo' => $variant->sku ?? 'ITEM-UNKNOWN',
                    'unitPrice' => (float)$item->unit_price,
                    'quantity' => (float)$item->qty,
                    'detailName' => $itemName . ' ' . ($variant->color ?? '') . ' ' . ($variant->storage ?? ''),
                    'itemCashDiscount' => (float)$item->discount_amount,
                ];
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
            }

            DB::commit();
            $this->dispatch('toast', title: 'Berhasil', message: 'Pesanan telah dilunaskan dan Faktur Accurate terbit!', type: 'success');
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

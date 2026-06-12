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
                $dbSource = 'syihab'; // default
                
                if (str_contains(strtolower($branchName), 'gsk') || str_contains(strtolower($branchName), 'gadget')) {
                    $dbSource = 'second';
                }

                $pm = PaymentMethod::find($this->payment_method_id);
                
                $srData = [
                    'customerNo' => $customerUser->accurate_customer_no ?? 'CASH',
                    'branchName' => $branchName,
                    'bankNo' => $pm->accurate_bank_no ?? 'KAS-CASH',
                    'transDate' => Carbon::parse($this->dp_date)->format('d/m/Y'),
                    'receiptAmount' => (float)$this->dp_amount,
                    'chequeAmount' => (float)$this->dp_amount,
                    'description' => 'Down Payment (DP) SO: ' . ($this->order->accurate_so_number ?? $this->order->order_number) . '. ' . $this->dp_notes
                ];

                $srResult = $accurateService->postSalesReceipt($srData, $dbSource);
                if (isset($srResult['r']['number'])) {
                    $payment->update(['accurate_receipt_no' => $srResult['r']['number']]);
                    if (!$this->order->accurate_receipt_no) {
                        $this->order->update(['accurate_receipt_no' => $srResult['r']['number']]);
                    } else {
                        $this->order->update(['accurate_receipt_no' => $this->order->accurate_receipt_no . ', ' . $srResult['r']['number']]);
                    }
                }
            } catch (\Exception $e) {
                Log::error('Accurate DP Sync Error: ' . $e->getMessage());
                session()->flash('warning', 'DP tersimpan di sistem, namun gagal tersinkron ke Accurate: ' . $e->getMessage());
            }
            
            $this->showDpModal = false;
            $this->order->refresh();
            
            session()->flash('success', 'Uang Muka (DP) berhasil dicatat!');

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Gagal menyimpan DP: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.admin.orders.sales-order.show', [
            'paymentMethods' => PaymentMethod::where('is_active', true)->get()
        ])->layout('layouts.admin');
    }
}

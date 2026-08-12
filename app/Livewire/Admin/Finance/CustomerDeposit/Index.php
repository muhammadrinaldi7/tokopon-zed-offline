<?php

namespace App\Livewire\Admin\Finance\CustomerDeposit;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\CustomerDeposit;
use App\Models\User;
use App\Models\PaymentMethod;
use App\Services\AccurateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use App\Livewire\Zoffline\Pos\Traits\WithPaymentAndPromo;

class Index extends Component
{
    use WithPagination, WithPaymentAndPromo;

    public $search = '';
    public $status_filter = '';

    // Form Modal
    public $showCreateModal = false;
    public $customer_id;
    public $searchCustomer = '';
    public $customerSearchResults = [];
    public $selectedCustomerName = '';
    public $payment_date;
    public $contract_number;

    protected $listeners = ['refreshDeposits' => '$refresh'];

    public function mount()
    {
        $this->payment_date = date('Y-m-d');
        $this->payments[0]['amount'] = 0;
    }

    #[Computed]
    public function subtotal()
    {
        return 0;
    }

    #[Computed]
    public function totalDiscount()
    {
        return 0;
    }

    public function updatedSearchCustomer($value)
    {
        if (strlen($value) >= 3) {
            $this->customerSearchResults = User::whereHas('roles', function ($q) {
                $q->where('name', 'user');
            })
                ->where(function ($q) use ($value) {
                    $q->where('name', 'like', '%' . $value . '%')
                        ->orWhere('email', 'like', '%' . $value . '%')
                        ->orWhereHas('profile', function ($q2) use ($value) {
                            $q2->where('phone_number', 'like', '%' . $value . '%');
                        });
                })
                ->with('profile')
                ->take(10)
                ->get()
                ->map(function ($u) {
                    return [
                        'id' => $u->id,
                        'name' => $u->name,
                        'phone' => $u->profile->phone_number ?? '-'
                    ];
                })->toArray();
        } else {
            $this->customerSearchResults = [];
        }
    }

    public function selectCustomer($id, $name, $phone)
    {
        $this->customer_id = $id;
        $this->searchCustomer = '';
        $this->selectedCustomerName = $name . ' (' . $phone . ')';
        $this->customerSearchResults = [];
    }

    public function clearCustomer()
    {
        $this->customer_id = null;
        $this->searchCustomer = '';
        $this->selectedCustomerName = '';
    }

    public function openCreateModal()
    {
        $this->reset(['customer_id', 'searchCustomer', 'selectedCustomerName', 'notes', 'contract_number']);
        $this->payment_date = date('Y-m-d');
        $this->setPaymentMode('tunai');
        $this->payments[0]['amount'] = 0;
        $this->showCreateModal = true;
    }

    #[\Livewire\Attributes\Computed]
    public function currentPaymentRates()
    {
        if (empty($this->payments[0]['payment_method_id'])) return collect();
        return \App\Models\PaymentMethodRate::where('payment_method_id', $this->payments[0]['payment_method_id'])
            ->where('is_active', true)
            ->get();
    }

    #[\Livewire\Attributes\Computed]
    public function calculatedMdr()
    {
        $paymentData = $this->payments[0] ?? null;
        if (!$paymentData || empty($paymentData['payment_method_rate_id']) || empty($paymentData['amount'])) return 0;
        $rate = \App\Models\PaymentMethodRate::find($paymentData['payment_method_rate_id']);
        if (!$rate) return 0;

        $pct = (float) ($rate->percentage ?? $rate->mdr_percentage);
        return $pct > 0 ? round((float)$paymentData['amount'] * $pct / 100, 0) : 0;
    }

    public function getCustomersProperty()
    {
        return User::whereHas('roles', function ($q) {
            $q->where('name', 'user');
        })->get();
    }

    public function getPaymentMethodsProperty()
    {
        return PaymentMethod::where('is_active', true)->get();
    }

    public function createDeposit()
    {
        $this->validate([
            'customer_id' => 'required|exists:users,id',
        ]);

        $paymentData = $this->payments[0] ?? null;

        if (!$paymentData || empty($paymentData['payment_method_id']) || empty($paymentData['amount']) || $paymentData['amount'] <= 0) {
            $this->dispatch('toast', title: 'Validasi Gagal', message: 'Metode pembayaran dan nominal wajib diisi.', type: 'error');
            return;
        }

        try {
            DB::beginTransaction();

            $user = Auth::user();
            $businessUnitId = $user->getActiveBusinessUnitId() ?? 1;

            $deposit = CustomerDeposit::create([
                'user_id' => $this->customer_id,
                'business_unit_id' => $businessUnitId,
                'amount' => (float)$paymentData['amount'],
                'balance' => (float)$paymentData['amount'],
                'payment_method_id' => $paymentData['payment_method_id'],
                'status' => 'AVAILABLE',
                'notes' => $this->notes,
                'created_by' => $user->id,
            ]);
            $accurateService = app(AccurateService::class);
            $customerUser = User::find($this->customer_id);
            $dbSource = $user->businessUnit->code; // Defaulting to syihab or pick from branch logic

            $handler = $user;
            $branchName = $handler->branch->name ?? 'Pusat';

            $accurateBranchName = $branchName;
            // if ($deposit->businessUnit && $deposit->businessUnit->code === 'second' && !str_contains(strtolower($accurateBranchName), 'gsk')) {
            //     $accurateBranchName = 'GSK ' . $accurateBranchName;
            // }

            $pm = PaymentMethod::find($paymentData['payment_method_id']);
            $rate = null;
            if (!empty($paymentData['payment_method_rate_id'])) {
                $rate = \App\Models\PaymentMethodRate::find($paymentData['payment_method_rate_id']);
            } elseif ($pm && $pm->rates()->where('is_active', true)->exists()) {
                $rate = $pm->rates()->where('is_active', true)->first();
            }

            $pct = $rate ? (float) ($rate->percentage ?? $rate->mdr_percentage) : 0;

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

            // STEP 1: DP Invoice without SO
            $dpInvData = [
                'customerNo' => $customerUser->getAccurateCustomerNo($dbSource),
                'branchName' => $accurateBranchName,
                'dpAmount'   => (float)$paymentData['amount'],
                'transDate'  => \Carbon\Carbon::parse($this->payment_date)->format('d/m/Y'),
                'inclusiveTax' => false,
                'isTaxable' => false,
                'description' => 'Deposit Pelanggan: ' . $this->notes,
            ];

            if (!empty($paymentData['no_kontrak'])) {
                $dpInvData['poNumber'] = $paymentData['no_kontrak'];
            }

            $dpInvResult = $accurateService->postDownPaymentInvoice($dpInvData, $dbSource);

            if (!isset($dpInvResult['r']['number'])) {
                throw new \Exception('Gagal mendapatkan nomor Faktur Uang Muka dari Accurate.');
            }

            $dpInvoiceNo = $dpInvResult['r']['number'];
            $deposit->update(['accurate_invoice_no' => $dpInvoiceNo]);

            // STEP 2: Sales Receipt
            $srData = [
                'customerNo' => $customerUser->getAccurateCustomerNo($dbSource),
                'branchName' => $accurateBranchName,
                'bankNo' => $pm->accurate_bank_no ?? 'KAS-CASH',
                'transDate' => \Carbon\Carbon::parse($this->payment_date)->format('d/m/Y'),
                'receiptAmount' => (float)$netReceiptAmount,
                'chequeAmount' => (float)$netReceiptAmount,
                'description' => 'Penerimaan Deposit: ' . $this->notes,
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

            $srResult = $accurateService->postSalesReceipt($srData, $dbSource);

            if (isset($srResult['r']['number'])) {
                $deposit->update(['accurate_receipt_no' => $srResult['r']['number']]);
            }

            DB::commit();

            $this->showCreateModal = false;
            $this->reset(['customer_id', 'notes', 'contract_number']);
            $this->setPaymentMode('tunai');
            $this->payments[0]['amount'] = 0;
            $this->payment_date = date('Y-m-d');
            $this->dispatch('refreshDeposits');

            $this->dispatch('toast', title: 'Berhasil', message: 'Deposit berhasil dicatat!', type: 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Create Deposit Error: ' . $e->getMessage());
            $this->dispatch('toast', title: 'Error', message: 'Gagal membuat deposit: ' . $e->getMessage(), type: 'error');
        }
    }

    public function render()
    {
        $query = CustomerDeposit::with(['user', 'paymentMethod', 'createdBy'])
            ->when($this->search, function ($q) {
                $q->whereHas('user', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('phone', 'like', '%' . $this->search . '%');
                })->orWhere('notes', 'like', '%' . $this->search . '%');
            })
            ->when($this->status_filter, function ($q) {
                $q->where('status', $this->status_filter);
            })
            ->orderBy('id', 'desc');

        return view('livewire.admin.finance.customer-deposit.index', [
            'deposits' => $query->paginate(20)
        ])->layout('layouts.admin', ['title' => 'Deposit Pelanggan']);
    }
}

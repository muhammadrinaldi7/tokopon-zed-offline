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
use Livewire\WithFileUploads;
use Livewire\Attributes\Computed;
use App\Livewire\Zoffline\Pos\Traits\WithPaymentAndPromo;

class Index extends Component
{
    use WithPagination, WithPaymentAndPromo, WithFileUploads;

    public $search = '';
    public $status_filter = '';

    // Form Modal Terima Deposit
    public $showCreateModal = false;
    public $customer_id;
    public $searchCustomer = '';
    public $customerSearchResults = [];
    public $selectedCustomerName = '';
    public $payment_date;
    public $contract_number;
    
    public $isLocalOnly = false;
    public $manual_invoice_no = '';
    public $manual_receipt_no = '';

    // Refund Modal Form
    public $showRefundModal = false;
    public $refundDepositId = null;
    public $refundAmount = 0;
    public $refundMaxAmount = 0;
    public $refundBankNo = '';
    public $refundCustomerBankName = '';
    public $refundCustomerAccountNo = '';
    public $refundCustomerAccountName = '';
    public $refundNotes = '';
    public $refundReceipt = null;

    // Void / Pembatalan Modal Form
    public $showVoidModal = false;
    public $voidDepositId = null;
    public $voidReason = '';

    // Detail Modal
    public $showDetailModal = false;
    public $selectedDepositId = null;

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
        $this->reset(['customer_id', 'searchCustomer', 'selectedCustomerName', 'notes', 'contract_number', 'isLocalOnly', 'manual_invoice_no', 'manual_receipt_no']);
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
            'manual_invoice_no' => 'required_if:isLocalOnly,true',
            'manual_receipt_no' => 'required_if:isLocalOnly,true',
        ], [
            'manual_invoice_no.required_if' => 'Nomor Faktur Accurate wajib diisi jika Rekam Lokal.',
            'manual_receipt_no.required_if' => 'Nomor Penerimaan Accurate wajib diisi jika Rekam Lokal.',
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

            if ($this->isLocalOnly) {
                // HANYA REKAM LOKAL - Bypass API Accurate
                $deposit->update([
                    'accurate_invoice_no' => $this->manual_invoice_no,
                    'accurate_receipt_no' => $this->manual_receipt_no,
                ]);
            } else {
                // STEP 1: DP Invoice without SO (Via API)
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

    #[Computed]
    public function selectedDeposit()
    {
        if (!$this->selectedDepositId) return null;
        return CustomerDeposit::with(['user.profile', 'paymentMethod', 'createdBy', 'businessUnit', 'originOrder', 'usages.order'])->find($this->selectedDepositId);
    }

    public function openRefundModal($depositId)
    {
        $deposit = CustomerDeposit::with('user')->find($depositId);
        if (!$deposit || $deposit->status !== 'AVAILABLE' || (float)$deposit->balance <= 0) {
            $this->dispatch('toast', title: 'Tidak Valid', message: 'Saldo deposit tidak tersedia untuk dicairkan.', type: 'error');
            return;
        }

        $this->refundDepositId = $deposit->id;
        $this->refundAmount = (float) $deposit->balance;
        $this->refundMaxAmount = (float) $deposit->balance;
        $this->refundBankNo = '';
        $this->refundCustomerBankName = '';
        $this->refundCustomerAccountNo = '';
        $this->refundCustomerAccountName = $deposit->user->name ?? '';
        $this->refundNotes = 'Refund Saldo Uang Muka Deposit #' . $deposit->id;
        $this->refundReceipt = null;
        $this->showRefundModal = true;
    }

    public function closeRefundModal()
    {
        $this->showRefundModal = false;
        $this->reset(['refundDepositId', 'refundAmount', 'refundMaxAmount', 'refundBankNo', 'refundCustomerBankName', 'refundCustomerAccountNo', 'refundCustomerAccountName', 'refundNotes', 'refundReceipt']);
    }

    public function processRefund()
    {
        $this->validate([
            'refundDepositId' => 'required|exists:customer_deposits,id',
            'refundBankNo' => 'required',
            'refundAmount' => 'required|numeric|min:1',
            'refundReceipt' => 'nullable|image|max:5120',
        ], [
            'refundBankNo.required' => 'Pilih akun Kas/Bank toko terlebih dahulu.',
            'refundAmount.required' => 'Nominal refund wajib diisi.',
            'refundAmount.min' => 'Nominal refund minimal Rp 1.',
            'refundReceipt.image' => 'File bukti transfer/struk harus berupa gambar (JPG, PNG, dll).',
            'refundReceipt.max' => 'Ukuran gambar bukti maksimal 5MB.',
        ]);

        $deposit = CustomerDeposit::with(['user', 'originOrder'])->find($this->refundDepositId);
        if (!$deposit || $deposit->status !== 'AVAILABLE' || (float)$deposit->balance <= 0) {
            $this->dispatch('toast', title: 'Error', message: 'Data deposit tidak valid atau sudah tidak memiliki saldo.', type: 'error');
            return;
        }

        if ((float)$this->refundAmount > (float)$deposit->balance) {
            $this->dispatch('toast', title: 'Error', message: 'Nominal refund melebihi sisa saldo deposit yang tersedia.', type: 'error');
            return;
        }

        $accurateService = app(AccurateService::class);

        DB::beginTransaction();
        try {
            // 1. Simpan bukti transfer jika diunggah
            $receiptPath = null;
            if ($this->refundReceipt) {
                $receiptPath = $this->refundReceipt->store('finance/dp_refunds', 'public');
            }

            // 2. Sinkronkan ke Accurate jika memiliki nomor faktur DP Accurate
            if (!empty($deposit->accurate_invoice_no)) {
                $customNotes = "Refund DP: " . ($deposit->user->name ?? 'Pelanggan') .
                    ($this->refundNotes ? " - " . $this->refundNotes : "");

                $accurateService->refundCustomerDeposit(
                    $deposit,
                    $this->refundBankNo,
                    $this->refundAmount,
                    $customNotes
                );
            }

            // 3. Update status deposit lokal
            $newBalance = max(0, (float)$deposit->balance - (float)$this->refundAmount);
            $newStatus = ($newBalance <= 0) ? 'REFUNDED' : 'AVAILABLE';

            $noteDetails = "\n[" . now()->format('Y-m-d H:i') . "] REFUND DANA: Rp " . number_format($this->refundAmount, 0, ',', '.') .
                " via Kas/Bank: " . $this->refundBankNo .
                ($this->refundCustomerBankName ? " ke " . $this->refundCustomerBankName . " No: " . $this->refundCustomerAccountNo . " a/n " . $this->refundCustomerAccountName : '') .
                ($this->refundNotes ? " (" . $this->refundNotes . ")" : "");

            $deposit->update([
                'balance' => $newBalance,
                'status' => $newStatus,
                'refund_bank_no' => $this->refundBankNo,
                'refund_customer_bank_name' => $this->refundCustomerBankName,
                'refund_customer_account_no' => $this->refundCustomerAccountNo,
                'refund_customer_account_name' => $this->refundCustomerAccountName,
                'refund_receipt_path' => $receiptPath,
                'refunded_at' => now(),
                'notes' => ($deposit->notes ?? '') . $noteDetails,
            ]);

            DB::commit();

            $this->closeRefundModal();
            $this->dispatch('refreshDeposits');
            $this->dispatch('toast', title: 'Berhasil', message: 'Refund DP sebesar Rp ' . number_format($this->refundAmount, 0, ',', '.') . ' berhasil diproses!', type: 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal proses refund deposit #{$deposit->id}: " . $e->getMessage());
            $this->dispatch('toast', title: 'Gagal Refund', message: 'Gagal memproses refund: ' . $e->getMessage(), type: 'error');
        }
    }

    public function openVoidModal($depositId)
    {
        $deposit = CustomerDeposit::with(['originOrder', 'usages'])->find($depositId);
        if (!$deposit) {
            $this->dispatch('toast', title: 'Error', message: 'Deposit tidak ditemukan.', type: 'error');
            return;
        }

        if ($deposit->status !== 'AVAILABLE') {
            $this->dispatch('toast', title: 'Tidak Dapat Dibatalkan', message: 'Hanya deposit berstatus AVAILABLE yang dapat dibatalkan.', type: 'warning');
            return;
        }

        if ((float)$deposit->balance < (float)$deposit->amount || $deposit->usages->count() > 0) {
            $this->dispatch('toast', title: 'Tidak Dapat Dibatalkan', message: 'Deposit ini sudah pernah terpakai di transaksi penjualan. Gunakan fitur Refund jika ingin mengembalikan sisa saldo.', type: 'warning');
            return;
        }

        if ($deposit->origin_order_id && $deposit->originOrder) {
            $soStatus = strtoupper($deposit->originOrder->order_status ?? '');
            if (!in_array($soStatus, ['CANCELLED', 'DRAFT'])) {
                $this->dispatch('toast', title: 'Tidak Dapat Dibatalkan', message: "Deposit ini terikat dengan Sales Order aktif (#{$deposit->originOrder->order_number}). Silakan batalkan Sales Order terlebih dahulu.", type: 'warning');
                return;
            }
        }

        $this->voidDepositId = $deposit->id;
        $this->voidReason = '';
        $this->showVoidModal = true;
    }

    public function closeVoidModal()
    {
        $this->showVoidModal = false;
        $this->reset(['voidDepositId', 'voidReason']);
    }

    public function processVoid()
    {
        $this->validate([
            'voidDepositId' => 'required|exists:customer_deposits,id',
            'voidReason' => 'required|min:5',
        ], [
            'voidReason.required' => 'Alasan pembatalan deposit wajib diisi.',
            'voidReason.min' => 'Alasan pembatalan minimal 5 karakter.',
        ]);

        $deposit = CustomerDeposit::with(['originOrder', 'usages', 'businessUnit'])->find($this->voidDepositId);
        if (!$deposit || $deposit->status !== 'AVAILABLE' || (float)$deposit->balance < (float)$deposit->amount || $deposit->usages->count() > 0) {
            $this->dispatch('toast', title: 'Error', message: 'Deposit tidak dapat dibatalkan karena saldo sudah terpakai.', type: 'error');
            return;
        }

        $accurateService = app(AccurateService::class);
        $businessUnit = $deposit->businessUnit;
        $dbSource = strtolower($businessUnit ? $businessUnit->code : 'syihab');

        DB::beginTransaction();
        try {
            // 1. Hapus Sales Receipt di Accurate jika ada
            if (!empty($deposit->accurate_receipt_no)) {
                try {
                    $accurateService->deleteSalesReceipt($deposit->accurate_receipt_no, $dbSource);
                } catch (\Exception $e) {
                    if (!str_contains(strtolower($e->getMessage()), 'tidak ditemukan') && !str_contains(strtolower($e->getMessage()), 'sudah dihapus')) {
                        throw new \Exception("Gagal menghapus Penerimaan Accurate ({$deposit->accurate_receipt_no}): " . $e->getMessage());
                    }
                    Log::warning("Void Deposit: Receipt {$deposit->accurate_receipt_no} sudah tidak ada di Accurate.");
                }
            }

            // 2. Hapus DP Invoice di Accurate jika ada
            if (!empty($deposit->accurate_invoice_no)) {
                try {
                    $accurateService->deleteSalesInvoice($deposit->accurate_invoice_no, $dbSource);
                } catch (\Exception $e) {
                    if (!str_contains(strtolower($e->getMessage()), 'tidak ditemukan') && !str_contains(strtolower($e->getMessage()), 'sudah dihapus')) {
                        throw new \Exception("Gagal menghapus Faktur DP Accurate ({$deposit->accurate_invoice_no}): " . $e->getMessage());
                    }
                    Log::warning("Void Deposit: Invoice {$deposit->accurate_invoice_no} sudah tidak ada di Accurate.");
                }
            }

            // 3. Update status deposit lokal ke CANCELLED (Soft-Void untuk audit trail)
            $userName = Auth::user()->name ?? 'Admin';
            $deposit->update([
                'status' => 'CANCELLED',
                'balance' => 0,
                'notes' => ($deposit->notes ?? '') . "\n[" . now()->format('Y-m-d H:i') . "] DIBATALKAN/VOID oleh {$userName}. Alasan: {$this->voidReason}",
            ]);

            DB::commit();

            $this->closeVoidModal();
            $this->dispatch('refreshDeposits');
            $this->dispatch('toast', title: 'Berhasil', message: 'Deposit berhasil dibatalkan dan dihapus dari Accurate.', type: 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal batalkan deposit #{$deposit->id}: " . $e->getMessage());
            $this->dispatch('toast', title: 'Gagal Membatalkan', message: $e->getMessage(), type: 'error');
        }
    }

    public function openDetailModal($depositId)
    {
        $this->selectedDepositId = $depositId;
        $this->showDetailModal = true;
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedDepositId = null;
    }

    public function render()
    {
        $query = CustomerDeposit::with(['user.profile', 'paymentMethod', 'createdBy', 'originOrder', 'usages'])
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->whereHas('user', function ($u) {
                        $u->where('name', 'like', '%' . $this->search . '%')
                            ->orWhere('email', 'like', '%' . $this->search . '%')
                            ->orWhereHas('profile', function ($p) {
                                $p->where('phone_number', 'like', '%' . $this->search . '%');
                            });
                    })
                    ->orWhere('notes', 'like', '%' . $this->search . '%')
                    ->orWhere('accurate_invoice_no', 'like', '%' . $this->search . '%')
                    ->orWhere('accurate_receipt_no', 'like', '%' . $this->search . '%')
                    ->orWhereHas('originOrder', function ($o) {
                        $o->where('order_number', 'like', '%' . $this->search . '%');
                    });
                });
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

<?php

namespace App\Livewire\Admin\Orders;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\Order;
use App\Models\OrderResetLog;
use App\Models\Branch;
use App\Models\Employe;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

#[Layout('layouts.admin', ['title' => 'Reset ke Draft'])]
class ResetToDraft extends Component
{
    use WithPagination;

    public $activeTab = 'orders'; // 'orders' | 'history'

    // Filters
    public $search = '';
    public $filterBranch = '';
    public $filterCashier = '';
    public $filterStartDate = '';
    public $filterEndDate = '';
    public $filterSyncStatus = '';
    public $filterPaymentMethod = '';

    // Direct cancellation modal
    public $showDirectCancelModal = false;
    public $directCancelOrderId = null;
    public $directCancelReason = '';
    public $reassignCashierId = ''; // Optional new cashier when resetting
    public $selectedOrder = null;

    // Change Cashier modal (Standalone)
    public $showChangeCashierModal = false;
    public $changeCashierOrderId = null;
    public $selectedOrderForCashier = null;
    public $newHandledById = '';

    // History detail modal
    public $showHistoryDetailModal = false;
    public $selectedLog = null;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterBranch()
    {
        $this->resetPage();
    }

    public function updatedFilterCashier()
    {
        $this->resetPage();
    }

    public function updatedFilterSyncStatus()
    {
        $this->resetPage();
    }

    public function updatedFilterPaymentMethod()
    {
        $this->resetPage();
    }

    public function updatedFilterStartDate()
    {
        $this->resetPage();
    }

    public function updatedFilterEndDate()
    {
        $this->resetPage();
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->reset(['search', 'filterBranch', 'filterCashier', 'filterStartDate', 'filterEndDate', 'filterSyncStatus', 'filterPaymentMethod']);
        $this->resetPage();
    }

    // ─── Direct Cancel / Reset to Draft Modal ───
    public function requestDirectCancellation($orderId)
    {
        $this->directCancelOrderId = $orderId;
        $this->selectedOrder = Order::with(['user', 'items', 'payments.paymentMethod', 'branch', 'accurateDocs', 'handledBy'])->find($orderId);
        $this->directCancelReason = '';
        $this->reassignCashierId = $this->selectedOrder->handled_by ?? '';
        $this->showDirectCancelModal = true;
    }

    public function closeDirectCancelModal()
    {
        $this->showDirectCancelModal = false;
        $this->directCancelOrderId = null;
        $this->directCancelReason = '';
        $this->reassignCashierId = '';
        $this->selectedOrder = null;
    }

    public function directCancellation()
    {
        $this->validate([
            'directCancelReason' => 'required|min:5'
        ], [
            'directCancelReason.required' => 'Alasan pembatalan wajib diisi.',
            'directCancelReason.min' => 'Alasan pembatalan minimal 5 karakter.'
        ]);

        $order = Order::with(['payments.paymentMethod', 'accurateDocs', 'user', 'branch', 'handledBy'])->find($this->directCancelOrderId);
        if (!$order) {
            $this->dispatch('toast', title: 'Error', message: 'Transaksi tidak ditemukan.', type: 'error');
            return;
        }

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            $oldHandledBy = $order->handled_by;
            $newHandledBy = !empty($this->reassignCashierId) ? (int) $this->reassignCashierId : $oldHandledBy;

            // 1. Simpan Snapshot Dokumen Sebelumnya ke Log History
            $paymentsSnapshot = $order->payments->map(function ($p) {
                return [
                    'id' => $p->id,
                    'payment_method_id' => $p->payment_method_id,
                    'payment_method_name' => $p->paymentMethod->name ?? 'Metode Lain',
                    'amount' => (float) $p->amount,
                    'no_kontrak' => $p->no_kontrak ?? null,
                    'paid_at' => $p->paid_at ? $p->paid_at->format('Y-m-d H:i:s') : null,
                    'created_at' => $p->created_at ? $p->created_at->format('Y-m-d H:i:s') : null,
                ];
            })->toArray();

            $accurateDocsSnapshot = $order->accurateDocs->map(function ($d) {
                return [
                    'id' => $d->id,
                    'doc_type' => $d->doc_type,
                    'doc_number' => $d->doc_number,
                    'accurate_id' => $d->accurate_id,
                    'amount' => (float) $d->amount,
                    'status' => $d->status,
                ];
            })->toArray();

            OrderResetLog::create([
                'order_id' => $order->id,
                'reset_by' => Auth::id(),
                'reason' => $this->directCancelReason,
                'previous_status' => $order->order_status,
                'previous_handled_by' => $oldHandledBy,
                'new_handled_by' => $newHandledBy,
                'previous_grand_total' => $order->grand_total,
                'previous_accurate_invoice_no' => $order->accurate_invoice_no,
                'previous_accurate_receipt_no' => $order->accurate_receipt_no,
                'previous_accurate_so_number' => $order->accurate_so_number ?? null,
                'previous_payments_snapshot' => $paymentsSnapshot,
                'previous_accurate_docs_snapshot' => $accurateDocsSnapshot,
            ]);

            // 2. Hapus dokumen di Accurate (Sales Receipt, Sales Invoice, DO, SO)
            $accurateService = app(\App\Services\AccurateService::class);
            $accurateService->rollbackOrderDocuments($order);

            // 3. Update order status ke DRAFT, null-kan accurate, dan update handled_by jika dialihkan
            $order->update([
                'order_status' => 'DRAFT',
                'accurate_invoice_no' => null,
                'accurate_receipt_no' => null,
                'handled_by' => $newHandledBy,
            ]);

            // Kembalikan saldo deposit yang terpakai
            $usages = \App\Models\CustomerDepositUsage::where('order_id', $order->id)->get();
            foreach ($usages as $usage) {
                $deposit = $usage->customerDeposit;
                if ($deposit) {
                    $deposit->balance += (float) $usage->amount_used;
                    $deposit->status = 'AVAILABLE';
                    $deposit->save();
                }
                $usage->delete();
            }

            // Kembalikan deposit SO (yang berasal dari DP SO ini) ke AVAILABLE
            \App\Models\CustomerDeposit::where('origin_order_id', $order->id)
                ->where('status', 'USED')
                ->update(['status' => 'AVAILABLE']);

            // 4. Hapus semua payment terkait order ini
            $order->payments()->delete();

            \Illuminate\Support\Facades\DB::commit();

            Log::info("Admin Reset to Draft: Order {$order->order_number} reset by " . Auth::user()->name . ". Alasan: {$this->directCancelReason}. Kasir: {$oldHandledBy} -> {$newHandledBy}");

            $this->dispatch('toast', title: 'Berhasil', message: 'Dokumen Accurate dihapus, history dicatat, & transaksi dikembalikan ke Draft.', type: 'success');
            $this->closeDirectCancelModal();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            Log::error('Admin Reset to Draft Error: ' . $e->getMessage());
            $this->dispatch('toast', title: 'Gagal', message: 'Terjadi kesalahan: ' . $e->getMessage(), type: 'error');
        }
    }

    // ─── Standalone Change Cashier Modal ───
    public function openChangeCashierModal($orderId)
    {
        $this->changeCashierOrderId = $orderId;
        $this->selectedOrderForCashier = Order::with(['user', 'branch', 'handledBy'])->find($orderId);
        if ($this->selectedOrderForCashier) {
            $this->newHandledById = $this->selectedOrderForCashier->handled_by ?? '';
            $this->showChangeCashierModal = true;
        }
    }

    public function closeChangeCashierModal()
    {
        $this->showChangeCashierModal = false;
        $this->changeCashierOrderId = null;
        $this->selectedOrderForCashier = null;
        $this->newHandledById = '';
    }

    public function updateCashier()
    {
        $this->validate([
            'newHandledById' => 'required|exists:users,id'
        ], [
            'newHandledById.required' => 'Silakan pilih kasir penanggung jawab.',
            'newHandledById.exists' => 'Kasir yang dipilih tidak valid.'
        ]);

        $order = Order::find($this->changeCashierOrderId);
        if (!$order) {
            $this->dispatch('toast', title: 'Error', message: 'Transaksi tidak ditemukan.', type: 'error');
            return;
        }

        $oldCashierName = $order->handledBy->name ?? 'Belum ditentukan';
        $newCashier = User::find($this->newHandledById);

        $order->update([
            'handled_by' => $this->newHandledById
        ]);

        Log::info("Admin Change Cashier: Order {$order->order_number} cashier changed from '{$oldCashierName}' to '{$newCashier->name}' by " . Auth::user()->name);

        $this->dispatch('toast', title: 'Berhasil', message: "Kasir penanggung jawab berhasil diubah ke {$newCashier->name}.", type: 'success');
        $this->closeChangeCashierModal();
    }

    // ─── History Snapshot Viewer ───
    public function viewLogDetail($logId)
    {
        $this->selectedLog = OrderResetLog::with(['order.user', 'order.branch', 'order.items', 'resetBy', 'previousHandledBy', 'newHandledBy'])->find($logId);
        if ($this->selectedLog) {
            $this->showHistoryDetailModal = true;
        }
    }

    public function closeHistoryDetailModal()
    {
        $this->showHistoryDetailModal = false;
        $this->selectedLog = null;
    }

    // ─── Manual Accurate Retry ───
    public function retryAccuratePush($orderId)
    {
        $order = Order::with(['items', 'payments.paymentMethod', 'user', 'branch', 'handledBy.warehouse', 'handledBy.branch'])->find($orderId);

        if (!$order || $order->order_status !== 'COMPLETED') {
            $this->dispatch('toast', title: 'Error', message: 'Hanya transaksi COMPLETED yang dapat disinkronkan ulang.', type: 'error');
            return;
        }

        if ($order->accurate_invoice_no && $order->accurate_receipt_no) {
            $this->dispatch('toast', title: 'Info', message: 'Transaksi ini sudah tersinkronisasi sepenuhnya ke Accurate.', type: 'warning');
            return;
        }

        try {
            $accurateService = app(\App\Services\AccurateService::class);
            $bu = \App\Models\BusinessUnit::find($order->business_unit_id);
            $dbSource = $bu ? $bu->code : 'syihab';

            $customerUser = $order->user;
            if ($customerUser) {
                $accurateService->syncCustomer($customerUser, $dbSource);
                $customerUser->refresh();
            }

            $financePayment = null;
            foreach ($order->payments as $payment) {
                if ($payment->paymentMethod && !empty($payment->paymentMethod->accurate_customer_no)) {
                    $financePayment = $payment->paymentMethod;
                    break;
                }
            }
            $invoiceCustomerNo = $financePayment ? $financePayment->accurate_customer_no : ($customerUser ? $customerUser->getAccurateCustomerNo($dbSource) : null);

            if (!$invoiceCustomerNo) {
                throw new \Exception("Pelanggan belum sinkron atau tidak memiliki nomor Accurate.");
            }

            $accurateBranchName = $order->branch->name ?? 'Banjarbaru';
            $accurateWarehouseName = $order->handledBy->warehouse->name ?? 'Head Office';

            // 1. SALES INVOICE
            if (!$order->accurate_invoice_no) {
                $detailItems = [];
                foreach ($order->items as $item) {
                    $sku = 'ITEM-UNKNOWN';
                    $projectNo = '';
                    $condition = '';

                    if ($item->product_variant_type == \App\Models\ProductAccurate::class) {
                        $product = \App\Models\ProductAccurate::find($item->product_variant_id);
                        if ($product) {
                            $sku = $product->item_no;
                            $projectNo = match (trim(strtoupper($product->proyek ?? ''))) {
                                'SJU' => 'P.00003',
                                'SAB' => 'P.00004',
                                'RESMI' => 'P.00008',
                                'INTER' => 'P.00009',
                                'BEACUKAI' => 'P.00010',
                                default => $product->proyek ?? ''
                            };
                        }
                    } else if ($item->product_variant_type == \App\Models\SecondProduct::class) {
                        $product = \App\Models\SecondProduct::find($item->product_variant_id);
                        if ($product) {
                            $sku = $product->sku;
                            $condition = $product->condition ?? '';
                        }
                    }

                    $rawSns = array_values(array_filter(array_map('trim', explode(',', $item->serial_number ?? ''))));
                    $detailSN = [];
                    foreach ($rawSns as $sn) {
                        if (!empty($sn)) {
                            $detailSN[] = ['serialNumberNo' => $sn, 'quantity' => 1];
                        }
                    }

                    $detailSalesman = [];
                    if ($order->sales_id) {
                        $sales = Employe::find($order->sales_id);
                        if ($sales && $sales->employee_no) {
                            $detailSalesman[] = (string) $sales->employee_no;
                        }
                    }

                    $itemData = [
                        'itemNo' => $sku,
                        'warehouseName' => $accurateWarehouseName,
                        'unitPrice' => (float) $item->price_at_checkout,
                        'quantity' => (float) $item->qty,
                        'itemCashDiscount' => (float) $item->discount_amount + (float) $item->promo_discount_amount,
                        'salesmanListNumber' => $detailSalesman,
                        'projectNo' => $projectNo
                    ];

                    if (in_array($condition, ['Inter', 'Resmi'])) {
                        $city = trim(str_replace(['GSK -', 'GSK '], '', $accurateWarehouseName));
                        $departmentPrefix = ($condition === 'Inter') ? 'Distri' : 'Retail';
                        $itemData['departmentName'] = $departmentPrefix . ' ' . $city;
                    }

                    if (!empty($detailSN)) {
                        $itemData['detailSerialNumber'] = $detailSN;
                    }

                    $detailItems[] = $itemData;
                }

                $isTaxable = $bu ? (bool) $bu->is_taxable : false;

                $siData = [
                    'customerNo' => $invoiceCustomerNo,
                    'branchName' => $accurateBranchName,
                    'detailItem' => $detailItems,
                    'transDate' => \Carbon\Carbon::parse($order->order_date)->format('d/m/Y'),
                    'inclusiveTax' => $isTaxable,
                    'taxable' => $isTaxable,
                    'useTax1' => $isTaxable,
                    'description' => $order->notes
                ];

                $validDpInvoices = [];
                $usages = \App\Models\CustomerDepositUsage::with('customerDeposit')->where('order_id', $order->id)->get();
                foreach ($usages as $usage) {
                    if ($usage->customerDeposit && $usage->customerDeposit->accurate_invoice_no) {
                        $validDpInvoices[] = [
                            'invoiceNumber' => $usage->customerDeposit->accurate_invoice_no,
                            'paymentAmount' => (float) $usage->amount_used,
                        ];
                    }
                }
                if (count($validDpInvoices) > 0) {
                    $siData['detailDownPayment'] = $validDpInvoices;
                }

                $mdrExpenses = $order->getMdrExpenseDetails();
                if (!empty($mdrExpenses)) {
                    $siData['detailExpense'] = $mdrExpenses;
                }

                Log::info("Admin Retry Accurate SI Payload: " . json_encode($siData));
                $siResult = $accurateService->postSalesInvoice($siData, $dbSource);
                if (isset($siResult['r']['number'])) {
                    $order->update(['accurate_invoice_no' => $siResult['r']['number']]);
                    \App\Models\OrderAccurateDoc::create([
                        'order_id' => $order->id,
                        'doc_type' => 'SALES_INVOICE',
                        'doc_number' => $siResult['r']['number'],
                        'accurate_id' => $siResult['r']['id'] ?? null,
                        'amount' => $order->grand_total,
                        'status' => 'SUCCESS',
                    ]);
                }
            }

            // 2. SALES RECEIPT
            if (!$order->accurate_receipt_no && $order->accurate_invoice_no) {
                $srNumbers = [];
                foreach ($order->payments as $payment) {
                    $rowTotal = (float)$payment->amount;
                    if ($rowTotal <= 0) continue;

                    $pm = $payment->paymentMethod;
                    if (!$pm || !empty($pm->accurate_customer_no)) continue;

                    $pct = 0;
                    if ($payment->payment_method_rate_id) {
                        $rate = \App\Models\PaymentMethodRate::find($payment->payment_method_rate_id);
                        if ($rate) $pct = (float) $rate->mdr_percentage;
                    } elseif ($pm) {
                        $pct = (float) $pm->mdr_percentage;
                    }
                    $rowMdr = $pct > 0 ? round($rowTotal * $pct / 100, 0) : 0;
                    $netReceiptAmount = $rowTotal - $rowMdr;

                    $srData = [
                        'customerNo' => $invoiceCustomerNo,
                        'branchName' => $accurateBranchName,
                        'bankNo' => $pm->accurate_bank_no ?? 'KAS-CASH',
                        'receiptAmount' => (float) $netReceiptAmount,
                        'chequeAmount' => (float) $netReceiptAmount,
                        'transDate' => \Carbon\Carbon::parse($order->order_date)->format('d/m/Y'),
                        'detailInvoice' => [
                            [
                                'invoiceNo' => $order->accurate_invoice_no,
                                'paymentAmount' => $netReceiptAmount,
                            ]
                        ],
                        'description' => $order->notes
                    ];

                    Log::info("Admin Retry Accurate SR Payload: " . json_encode($srData));
                    $srResult = $accurateService->postSalesReceipt($srData, $dbSource);
                    if (isset($srResult['r']['number'])) {
                        $srNumbers[] = $srResult['r']['number'];
                        \App\Models\OrderAccurateDoc::create([
                            'order_id' => $order->id,
                            'doc_type' => 'SALES_RECEIPT',
                            'doc_number' => $srResult['r']['number'],
                            'accurate_id' => $srResult['r']['id'] ?? null,
                            'amount' => $netReceiptAmount,
                            'status' => 'SUCCESS',
                        ]);
                    }
                }

                if (!empty($srNumbers)) {
                    $order->update(['accurate_receipt_no' => implode(', ', $srNumbers)]);
                }
            }

            $this->dispatch('toast', title: 'Berhasil', message: 'Tugas sinkronisasi ulang berhasil diproses.', type: 'success');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Admin Retry Sync Error: ' . $e->getMessage());
            $this->dispatch('toast', title: 'Gagal', message: 'Sinkronisasi gagal: ' . $e->getMessage(), type: 'error');
        }
    }

    public function render()
    {
        $user = Auth::user();
        $businessUnitId = $user->getActiveBusinessUnitId();

        $branches = Branch::where('business_unit_id', $businessUnitId)
            ->orderBy('name')
            ->get();

        // Ambil daftar seluruh staf/kasir aktif untuk dropdown
        $cashiers = User::where('business_unit_id', $businessUnitId)
            ->whereHas('roles', function ($q) {
                $q->whereNotIn('name', ['customer', 'user']);
            })
            ->orderBy('name')
            ->get();

        if ($cashiers->isEmpty()) {
            $cashiers = User::where('business_unit_id', $businessUnitId)->orderBy('name')->get();
        }

        $orders = collect();
        $logs = collect();

        if ($this->activeTab === 'orders') {
            $orders = Order::with(['user', 'items', 'payments.paymentMethod', 'handledBy', 'salesBy', 'branch', 'accurateDocs'])
                ->whereIn('order_channel', ['POS', 'SO'])
                ->whereNotIn('order_status', ['DRAFT', 'DRAFT_LOADED'])
                ->where('business_unit_id', $businessUnitId)
                ->when($this->search, function ($query) {
                    $query->where(function ($q) {
                        $q->where('order_number', 'like', '%' . $this->search . '%')
                            ->orWhereHas('user', function ($uq) {
                                $uq->where('name', 'like', '%' . $this->search . '%')
                                    ->orWhere('identity', 'like', '%' . $this->search . '%');
                            })
                            ->orWhereHas('items', function ($iq) {
                                $iq->where('serial_number', 'like', '%' . $this->search . '%');
                            });
                    });
                })
                ->when($this->filterBranch, function ($query) {
                    $query->where('branch_id', $this->filterBranch);
                })
                ->when($this->filterCashier, function ($query) {
                    $query->where('handled_by', $this->filterCashier);
                })
                ->when($this->filterStartDate, function ($query) {
                    $query->whereDate('created_at', '>=', $this->filterStartDate);
                })
                ->when($this->filterEndDate, function ($query) {
                    $query->whereDate('created_at', '<=', $this->filterEndDate);
                })
                ->when($this->filterSyncStatus === 'unsynced', function ($query) {
                    $query->whereNull('accurate_invoice_no')
                        ->whereNull('accurate_receipt_no');
                })
                ->when($this->filterPaymentMethod, function ($query) {
                    $query->whereHas('payments', function ($q) {
                        $q->where('payment_method_id', $this->filterPaymentMethod);
                    });
                })
                ->orderBy('created_at', 'desc')
                ->paginate(15);
        } else {
            $logs = OrderResetLog::with(['order.user', 'order.branch', 'resetBy', 'previousHandledBy', 'newHandledBy'])
                ->whereHas('order', function ($query) use ($businessUnitId) {
                    $query->where('business_unit_id', $businessUnitId)
                        ->when($this->filterBranch, function ($bq) {
                            $bq->where('branch_id', $this->filterBranch);
                        });
                })
                ->when($this->filterCashier, function ($query) {
                    $query->where(function ($q) {
                        $q->where('previous_handled_by', $this->filterCashier)
                            ->orWhere('new_handled_by', $this->filterCashier);
                    });
                })
                ->when($this->search, function ($query) {
                    $query->where(function ($q) {
                        $q->where('previous_accurate_invoice_no', 'like', '%' . $this->search . '%')
                            ->orWhere('previous_accurate_receipt_no', 'like', '%' . $this->search . '%')
                            ->orWhere('reason', 'like', '%' . $this->search . '%')
                            ->orWhereHas('order', function ($oq) {
                                $oq->where('order_number', 'like', '%' . $this->search . '%')
                                    ->orWhereHas('user', function ($uq) {
                                        $uq->where('name', 'like', '%' . $this->search . '%');
                                    });
                            })
                            ->orWhereHas('resetBy', function ($rq) {
                                $rq->where('name', 'like', '%' . $this->search . '%');
                            })
                            ->orWhereHas('previousHandledBy', function ($hq) {
                                $hq->where('name', 'like', '%' . $this->search . '%');
                            });
                    });
                })
                ->when($this->filterStartDate, function ($query) {
                    $query->whereDate('created_at', '>=', $this->filterStartDate);
                })
                ->when($this->filterEndDate, function ($query) {
                    $query->whereDate('created_at', '<=', $this->filterEndDate);
                })
                ->orderBy('created_at', 'desc')
                ->paginate(15);
        }

        $paymentMethods = \App\Models\PaymentMethod::where('is_active', true)->orderBy('name')->get();

        return view('livewire.admin.orders.reset-to-draft', [
            'orders' => $orders,
            'logs' => $logs,
            'branches' => $branches,
            'cashiers' => $cashiers,
            'paymentMethods' => $paymentMethods,
        ]);
    }
}

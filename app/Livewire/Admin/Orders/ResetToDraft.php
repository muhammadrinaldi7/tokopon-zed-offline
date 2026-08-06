<?php

namespace App\Livewire\Admin\Orders;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\Order;
use App\Models\OrderResetLog;
use App\Models\Branch;
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
        $this->reset(['search', 'filterBranch', 'filterCashier', 'filterStartDate', 'filterEndDate']);
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

            // 4. Hapus semua payment terkait order ini
            $order->payments()->delete();

            Log::info("Admin Reset to Draft: Order {$order->order_number} reset by " . Auth::user()->name . ". Alasan: {$this->directCancelReason}. Kasir: {$oldHandledBy} -> {$newHandledBy}");

            $this->dispatch('toast', title: 'Berhasil', message: 'Dokumen Accurate dihapus, history dicatat, & transaksi dikembalikan ke Draft.', type: 'success');
            $this->closeDirectCancelModal();
        } catch (\Exception $e) {
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

        return view('livewire.admin.orders.reset-to-draft', [
            'orders' => $orders,
            'logs' => $logs,
            'branches' => $branches,
            'cashiers' => $cashiers,
        ]);
    }
}

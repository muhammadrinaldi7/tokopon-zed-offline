<?php

namespace App\Livewire\Admin\Accurate;

use App\Models\AccurateDocumentAuditLog;
use App\Models\Branch;
use App\Models\BusinessUnit;
use App\Models\Order;
use App\Services\AccurateDocumentControlService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin', ['title' => 'Pusat Kendali Dokumen Accurate'])]
class DocumentControlIndex extends Component
{
    use WithPagination;

    // Filters
    public string $search = '';
    public string $statusFilter = ''; // '', 'SYNCED', 'PENDING', 'FAILED'
    public string $branchFilter = '';
    public string $businessUnitFilter = '';
    public string $datePreset = 'this_month';
    public string $startDate = '';
    public string $endDate = '';
    public int $perPage = 15;

    // Modals
    public bool $showDiffModal = false;
    public bool $showEditDateModal = false;
    public bool $showRelinkModal = false;
    public bool $showUnlinkModal = false;
    public bool $showRollbackModal = false;
    public bool $showAuditModal = false;

    // Selection & Payloads
    public ?int $selectedOrderId = null;
    public ?Order $selectedOrder = null;
    public array $diffData = [];
    public bool $isLoadingAction = false;

    // Form inputs
    public string $newTransactionDate = '';
    public string $changeReason = '';
    public string $relinkDocType = 'SALES_INVOICE';
    public string $newDocNumber = '';
    public string $unlinkDocType = 'SALES_INVOICE';
    public string $rollbackReason = '';

    protected $queryString = [
        'search'             => ['except' => ''],
        'statusFilter'       => ['except' => ''],
        'branchFilter'       => ['except' => ''],
        'businessUnitFilter' => ['except' => ''],
        'datePreset'         => ['except' => 'this_month'],
    ];

    public function mount()
    {
        $this->businessUnitFilter = (string)(Auth::user()?->getActiveBusinessUnitId() ?? '');
        $this->setDatePreset($this->datePreset);
    }

    public function setDatePreset(string $preset)
    {
        $this->datePreset = $preset;
        $now = Carbon::now();

        match ($preset) {
            'today' => [
                $this->startDate = $now->copy()->startOfDay()->toDateString(),
                $this->endDate   => $now->copy()->endOfDay()->toDateString(),
            ],
            'yesterday' => [
                $this->startDate = $now->copy()->subDay()->startOfDay()->toDateString(),
                $this->endDate   => $now->copy()->subDay()->endOfDay()->toDateString(),
            ],
            'this_week' => [
                $this->startDate = $now->copy()->startOfWeek()->toDateString(),
                $this->endDate   => $now->copy()->endOfWeek()->toDateString(),
            ],
            'this_month' => [
                $this->startDate = $now->copy()->startOfMonth()->toDateString(),
                $this->endDate   => $now->copy()->endOfMonth()->toDateString(),
            ],
            'last_month' => [
                $this->startDate = $now->copy()->subMonth()->startOfMonth()->toDateString(),
                $this->endDate   => $now->copy()->subMonth()->endOfMonth()->toDateString(),
            ],
            'all' => [
                $this->startDate = '',
                $this->endDate   => '',
            ],
            default => null,
        };

        $this->resetPage();
    }

    public function updatedSearch() { $this->resetPage(); }
    public function updatedStatusFilter() { $this->resetPage(); }
    public function updatedBranchFilter() { $this->resetPage(); }
    public function updatedBusinessUnitFilter() { $this->resetPage(); }
    public function updatedStartDate() { $this->datePreset = 'custom'; $this->resetPage(); }
    public function updatedEndDate() { $this->datePreset = 'custom'; $this->resetPage(); }

    public function resetFilters()
    {
        $this->reset(['search', 'statusFilter', 'branchFilter']);
        $this->businessUnitFilter = (string)(Auth::user()?->getActiveBusinessUnitId() ?? '');
        $this->setDatePreset('this_month');
    }

    // --- 1. INSPEKSI & DIFF DOKUMEN ---
    public function inspectDocument(int $orderId)
    {
        $this->selectedOrderId = $orderId;
        $this->selectedOrder = Order::with(['user', 'branch', 'businessUnit', 'accurateDocs', 'items'])->find($orderId);

        if (!$this->selectedOrder) {
            $this->dispatch('toast', title: 'Error', message: 'Order tidak ditemukan.', type: 'error');
            return;
        }

        $controlService = app(AccurateDocumentControlService::class);
        $this->diffData = $controlService->getLiveDocumentDiff($this->selectedOrder);
        $this->showDiffModal = true;
    }

    public function closeDiffModal()
    {
        $this->showDiffModal = false;
        $this->diffData = [];
    }

    // --- 2. UBAH TANGGAL TRANSAKSI ---
    public function openEditDateModal(int $orderId)
    {
        $this->selectedOrderId = $orderId;
        $this->selectedOrder = Order::find($orderId);
        $this->newTransactionDate = $this->selectedOrder->order_date
            ? Carbon::parse($this->selectedOrder->order_date)->format('Y-m-d')
            : Carbon::parse($this->selectedOrder->created_at)->format('Y-m-d');
        $this->changeReason = '';
        $this->showEditDateModal = true;
    }

    public function executeDateChange()
    {
        $this->validate([
            'newTransactionDate' => 'required|date',
            'changeReason'       => 'required|min:5',
        ], [
            'changeReason.required' => 'Mohon isi alasan perubahan tanggal untuk audit.',
            'changeReason.min'      => 'Alasan perubahan minimal 5 karakter.',
        ]);

        $order = Order::find($this->selectedOrderId);
        if (!$order) {
            $this->dispatch('toast', title: 'Error', message: 'Order tidak ditemukan.', type: 'error');
            return;
        }

        $controlService = app(AccurateDocumentControlService::class);
        $result = $controlService->updateTransactionDate($order, $this->newTransactionDate, $this->changeReason, Auth::id());

        if ($result['success']) {
            $this->dispatch('toast', title: 'Berhasil', message: $result['message'], type: 'success');
            $this->showEditDateModal = false;
            $this->reset(['selectedOrderId', 'selectedOrder', 'newTransactionDate', 'changeReason']);
        } else {
            $this->dispatch('toast', title: 'Gagal Update', message: $result['error'] ?? 'Terjadi kesalahan.', type: 'error');
        }
    }

    // --- 3. RE-LINK DOKUMEN ---
    public function openRelinkModal(int $orderId)
    {
        $this->selectedOrderId = $orderId;
        $this->selectedOrder = Order::find($orderId);
        $this->relinkDocType = 'SALES_INVOICE';
        $this->newDocNumber = $this->selectedOrder->accurate_invoice_no ?? '';
        $this->changeReason = '';
        $this->showRelinkModal = true;
    }

    public function executeRelink()
    {
        $this->validate([
            'newDocNumber' => 'required|string|min:3',
            'changeReason' => 'required|min:5',
        ], [
            'newDocNumber.required' => 'Nomor dokumen Accurate wajib diisi.',
            'changeReason.required' => 'Mohon isi alasan penautan ulang dokumen.',
        ]);

        $order = Order::find($this->selectedOrderId);
        if (!$order) {
            $this->dispatch('toast', title: 'Error', message: 'Order tidak ditemukan.', type: 'error');
            return;
        }

        $controlService = app(AccurateDocumentControlService::class);
        $result = $controlService->relinkDocument($order, $this->relinkDocType, $this->newDocNumber, $this->changeReason, Auth::id());

        if ($result['success']) {
            $this->dispatch('toast', title: 'Berhasil Re-Link', message: $result['message'], type: 'success');
            $this->showRelinkModal = false;
            $this->reset(['selectedOrderId', 'selectedOrder', 'newDocNumber', 'changeReason']);
        } else {
            $this->dispatch('toast', title: 'Gagal Re-Link', message: $result['error'] ?? 'Gagal memverifikasi dokumen di Accurate.', type: 'error');
        }
    }

    // --- 4. UNLINK DOKUMEN ---
    public function openUnlinkModal(int $orderId, string $docType = 'SALES_INVOICE')
    {
        $this->selectedOrderId = $orderId;
        $this->selectedOrder = Order::find($orderId);
        $this->unlinkDocType = $docType;
        $this->changeReason = '';
        $this->showUnlinkModal = true;
    }

    public function executeUnlink()
    {
        $this->validate([
            'changeReason' => 'required|min:5',
        ], [
            'changeReason.required' => 'Alasan pemutusan tautan wajib diisi.',
        ]);

        $order = Order::find($this->selectedOrderId);
        if (!$order) {
            $this->dispatch('toast', title: 'Error', message: 'Order tidak ditemukan.', type: 'error');
            return;
        }

        $controlService = app(AccurateDocumentControlService::class);
        $result = $controlService->unlinkDocument($order, $this->unlinkDocType, $this->changeReason, Auth::id());

        if ($result['success']) {
            $this->dispatch('toast', title: 'Berhasil', message: $result['message'], type: 'success');
            $this->showUnlinkModal = false;
            $this->reset(['selectedOrderId', 'selectedOrder', 'changeReason']);
        } else {
            $this->dispatch('toast', title: 'Gagal', message: $result['error'] ?? 'Gagal memutus tautan dokumen.', type: 'error');
        }
    }

    // --- 5. ROLLBACK & RE-SYNC ---
    public function openRollbackModal(int $orderId)
    {
        $this->selectedOrderId = $orderId;
        $this->selectedOrder = Order::find($orderId);
        $this->rollbackReason = '';
        $this->showRollbackModal = true;
    }

    public function executeRollbackAndResync()
    {
        $this->validate([
            'rollbackReason' => 'required|min:5',
        ], [
            'rollbackReason.required' => 'Alasan pembatalan dan sinkronisasi ulang wajib diisi.',
        ]);

        $order = Order::find($this->selectedOrderId);
        if (!$order) {
            $this->dispatch('toast', title: 'Error', message: 'Order tidak ditemukan.', type: 'error');
            return;
        }

        $controlService = app(AccurateDocumentControlService::class);
        $result = $controlService->rollbackAndResync($order, $this->rollbackReason, Auth::id());

        if ($result['success']) {
            $this->dispatch('toast', title: 'Berhasil', message: $result['message'], type: 'success');
            $this->showRollbackModal = false;
            $this->reset(['selectedOrderId', 'selectedOrder', 'rollbackReason']);
        } else {
            $this->dispatch('toast', title: 'Peringatan', message: $result['error'] ?? 'Gagal memproses rollback.', type: 'error');
        }
    }

    // --- 6. AUDIT LOGS MODAL ---
    public function openAuditModal(int $orderId)
    {
        $this->selectedOrderId = $orderId;
        $this->selectedOrder = Order::find($orderId);
        $this->showAuditModal = true;
    }

    public function render()
    {
        $query = Order::with(['user', 'branch', 'businessUnit', 'accurateDocs'])
            ->whereIn('order_status', ['COMPLETED', 'piutang', 'down_payment', 'paid'])
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('order_number', 'like', "%{$this->search}%")
                        ->orWhere('accurate_invoice_no', 'like', "%{$this->search}%")
                        ->orWhere('accurate_receipt_no', 'like', "%{$this->search}%")
                        ->orWhereHas('user', function ($qu) {
                            $qu->where('name', 'like', "%{$this->search}%");
                        });
                });
            })
            ->when($this->statusFilter, function ($q) {
                if ($this->statusFilter === 'SYNCED') {
                    $q->where('accurate_sync_status', 'SYNCED');
                } elseif ($this->statusFilter === 'PENDING') {
                    $q->where(function ($sub) {
                        $sub->where('accurate_sync_status', 'PENDING')
                            ->orWhere(function ($n) {
                                $n->whereNull('accurate_sync_status')
                                  ->whereNull('accurate_invoice_no');
                            });
                    });
                } elseif ($this->statusFilter === 'FAILED') {
                    $q->where('accurate_sync_status', 'FAILED');
                } elseif ($this->statusFilter === 'PARTIAL') {
                    $q->whereNotNull('accurate_invoice_no')
                      ->where(function ($sub) {
                          $sub->whereNull('accurate_receipt_no')->orWhere('accurate_receipt_no', '');
                      });
                }
            })
            ->when($this->branchFilter, function ($q) {
                $q->where('branch_id', $this->branchFilter);
            })
            ->when($this->businessUnitFilter && $this->businessUnitFilter !== 'all', function ($q) {
                $q->where('business_unit_id', $this->businessUnitFilter);
            })
            ->when($this->startDate, function ($q) {
                $q->whereDate('created_at', '>=', $this->startDate);
            })
            ->when($this->endDate, function ($q) {
                $q->whereDate('created_at', '<=', $this->endDate);
            })
            ->latest('created_at');

        $orders = $query->paginate($this->perPage);

        $branches = Branch::orderBy('name')->get();
        $businessUnits = BusinessUnit::all();

        $auditLogs = $this->selectedOrderId
            ? AccurateDocumentAuditLog::with('performedBy')
                ->where('order_id', $this->selectedOrderId)
                ->latest()
                ->get()
            : collect();

        return view('livewire.admin.accurate.document-control-index', [
            'orders'        => $orders,
            'branches'      => $branches,
            'businessUnits' => $businessUnits,
            'auditLogs'     => $auditLogs,
        ]);
    }
}


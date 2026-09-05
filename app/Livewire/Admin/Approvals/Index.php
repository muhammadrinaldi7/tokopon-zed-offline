<?php

namespace App\Livewire\Admin\Approvals;

use App\Http\Controllers\ApprovalController;
use App\Models\ApprovalRequest;
use App\Models\BusinessUnit;
use App\Models\Order;
use App\Services\ApprovalService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $filterStatus = 'PENDING';
    public $filterBusinessUnitId = null; // null = ikuti active BU atau semua

    // Final Level Confirmations
    public $confirmingApprovalId = null;
    public $confirmingRequestType = null;
    public $extensionDays = 7;

    // Detail & Struk Modal
    public $showDetailModal = false;
    public $detailRequest = null;
    public $detailOrder = null;

    // Rejection Modal
    public $rejectingApprovalId = null;
    public $rejectionReason = '';

    // SellPhone Edit Price Modal
    public $editingPriceId = null;
    public $adjustedPrice = 0;
    public $priceAdjustmentReason = '';

    public function updatedAdjustedPrice($value)
    {
        if (is_numeric($value)) {
            $this->adjustedPrice = (int) round((float) $value);
        } elseif (is_string($value)) {
            $integerPart = explode('.', $value)[0];
            $cleaned = preg_replace('/[^0-9]/', '', $integerPart);
            $this->adjustedPrice = $cleaned !== '' ? (int) $cleaned : 0;
        }
    }

    public function mount()
    {
        $user = Auth::user();
        if ($user) {
            // Default ke active business unit jika ada
            $this->filterBusinessUnitId = $user->getActiveBusinessUnitId();
        }
    }

    public function viewDetail($id)
    {
        $this->detailRequest = ApprovalRequest::with([
            'approvable',
            'requestedBy.branch',
            'businessUnit',
            'branch',
            'histories.actedBy'
        ])->find($id);

        if (!$this->detailRequest) return;

        if ($this->detailRequest->approvable_type === Order::class || ($this->detailRequest->approvable instanceof Order)) {
            $this->detailOrder = Order::with([
                'items.variant',
                'user.profile',
                'payments.paymentMethod',
                'payments.paymentMethodRate',
                'handledBy',
                'salesBy',
                'businessUnit'
            ])->find($this->detailRequest->approvable_id);
        } else {
            $this->detailOrder = null;
        }

        $this->showDetailModal = true;
    }

    public function closeDetail()
    {
        $this->showDetailModal = false;
        $this->detailRequest = null;
        $this->detailOrder = null;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    public function updatingFilterBusinessUnitId()
    {
        $this->resetPage();
    }

    public function confirmApprove($id)
    {
        $request = ApprovalRequest::find($id);
        if (!$request) return;

        $user = Auth::user();
        $nextLevel = $request->current_level + 1;

        // Validasi Role menggunakan resolusi dinamis BU
        $rule = app(ApprovalService::class)->getRuleForLevel($request->request_type, $nextLevel, $request->business_unit_id);

        if ($rule && $rule->role) {
            if (!$user->hasRole($rule->role->name) && !$user->hasRole('superadmin')) {
                $this->dispatch('toast', title: 'Akses Ditolak', message: 'Anda tidak memiliki role (' . $rule->role->name . ') untuk menyetujui Level ' . $nextLevel, type: 'error');
                return;
            }
        }

        if ($nextLevel >= $request->required_level) {
            $this->confirmingApprovalId = $id;
            $this->confirmingRequestType = $request->request_type;

            if ($request->request_type === 'SELL_PHONE_APPROVAL') {
                $this->editingPriceId = $id;
                if ($request->approvable && isset($request->approvable->appraised_value)) {
                    $this->adjustedPrice = (int) round((float) $request->approvable->appraised_value);
                }
            }
        } else {
            $this->approve($id); // Langsung setujui kalau belum tahap akhir
        }
    }

    public function executeApprove()
    {
        if ($this->confirmingApprovalId) {
            if ($this->confirmingRequestType === 'SELL_PHONE_APPROVAL' && $this->editingPriceId) {
                $request = ApprovalRequest::with('approvable')->find($this->confirmingApprovalId);
                $originalPrice = (float) ($request?->approvable?->appraised_value ?? 0);
                $newPrice = (float) (is_numeric($this->adjustedPrice) ? $this->adjustedPrice : preg_replace('/[^0-9]/', '', explode('.', (string)$this->adjustedPrice)[0]));
                $isPriceChanged = $newPrice > 0 && abs($newPrice - $originalPrice) > 0.01;

                if ($isPriceChanged && empty(trim($this->priceAdjustmentReason))) {
                    $this->addError('priceAdjustmentReason', 'Alasan ubah harga wajib diisi jika harga diubah!');
                    return;
                }
            }

            $this->approve($this->confirmingApprovalId);
            $this->cancelApprove();
        }
    }

    public function cancelApprove()
    {
        $this->confirmingApprovalId = null;
        $this->confirmingRequestType = null;
        $this->extensionDays = 7;
        $this->editingPriceId = null;
        $this->adjustedPrice = 0;
        $this->priceAdjustmentReason = '';
    }

    public function approve($id)
    {
        $request = ApprovalRequest::find($id);
        if (!$request) return;

        $user = Auth::user();
        $nextLevel = $request->current_level + 1;

        // Validasi Role
        $rule = app(ApprovalService::class)->getRuleForLevel($request->request_type, $nextLevel, $request->business_unit_id);

        if ($rule && $rule->role) {
            if (!$user->hasRole($rule->role->name) && !$user->hasRole('superadmin')) {
                $this->dispatch('toast', title: 'Akses Ditolak', message: 'Anda tidak memiliki role yang diizinkan untuk menyetujui Level ' . $nextLevel, type: 'error');
                return;
            }
        }

        $originalPrice = (float) ($request->approvable?->appraised_value ?? 0);
        $newPrice = (float) (is_numeric($this->adjustedPrice) ? $this->adjustedPrice : preg_replace('/[^0-9]/', '', explode('.', (string)$this->adjustedPrice)[0]));
        $isPriceChanged = $request->request_type === 'SELL_PHONE_APPROVAL'
            && $this->editingPriceId
            && $newPrice > 0
            && abs($newPrice - $originalPrice) > 0.01;

        $historyNote = 'Approved by ' . $user->name;
        if ($isPriceChanged && $this->priceAdjustmentReason) {
            $historyNote .= " (Adjusted Price: Rp " . number_format($newPrice, 0, ',', '.') . " - {$this->priceAdjustmentReason})";
        }

        // Add history with role snapshot
        $request->histories()->create([
            'acted_by'      => $user->id,
            'role_snapshot' => $user->roles->pluck('name')->join(', '),
            'action'        => 'APPROVED',
            'level'         => $nextLevel,
            'notes'         => $historyNote
        ]);

        $request->current_level += 1;

        if ($request->current_level >= $request->required_level) {
            $request->status = 'APPROVED';
            $request->save();

            try {
                $request->executeAction([
                    'extension_days'          => $this->extensionDays,
                    'adjusted_price'          => $isPriceChanged ? $newPrice : null,
                    'price_adjusted_by'       => $isPriceChanged ? $user->id : null,
                    'price_adjustment_reason' => $isPriceChanged ? $this->priceAdjustmentReason : null,
                ]);

                // --- KIRIM NOTIFIKASI GRUP TELEGRAM ---
                $kasirName = $request->requestedBy->name ?? 'Kasir';
                $tipe = str_replace('_', ' ', $request->request_type) . " (Level {$request->required_level})";
                $orderInfo = '-';
                if ($request->approvable_type === Order::class && $request->approvable) {
                    $orderInfo = $request->approvable->order_number;
                } elseif ($request->approvable_type === \App\Models\SellPhone::class && $request->approvable) {
                    $orderInfo = $request->approvable->phone_brand . ' ' . $request->approvable->phone_model;
                    if ($isPriceChanged) {
                        $orderInfo .= " (Harga Disesuaikan: Rp " . number_format($newPrice, 0, ',', '.') . ")";
                    }
                }
                $cabang = $request->branch?->name ?? ($request->requestedBy?->branch?->name ?? '-');
                $waktu = $request->created_at->format('d M Y H:i');
                $alasan = $request->reason ?? '-';

                $teksGrup = "✅ *APPROVAL SUKSES*\n\n"
                    . "Pengajuan: {$tipe} untuk {$orderInfo}\n"
                    . "Kasir: {$kasirName}\n"
                    . "Waktu: {$waktu}\n"
                    . "Cabang: {$cabang}\n"
                    . "Keterangan: \"{$alasan}\"\n\n"
                    . "Telah disetujui sepenuhnya oleh *{$user->name}* (via Web).";

                ApprovalController::sendGroupNotification($teksGrup, $request->business_unit_id);

                $msg = $request->request_type === 'ORDER_CANCELLATION'
                    ? 'Persetujuan berhasil dan transaksi dibatalkan di Accurate.'
                    : 'Persetujuan berhasil dieksekusi.';

                $this->dispatch('toast', title: 'Berhasil', message: $msg, type: 'success');
            } catch (Exception $e) {
                $this->dispatch('toast', title: 'Error Eksekusi', message: 'Gagal mengeksekusi persetujuan: ' . $e->getMessage(), type: 'error');
            }
        } else {
            $request->save();

            // Trigger notifikasi untuk level selanjutnya
            ApprovalController::sendTelegramNotification($request);

            $this->dispatch('toast', title: 'Berhasil', message: 'Disetujui. Menunggu persetujuan level selanjutnya.', type: 'success');
        }
    }

    public function confirmReject($id)
    {
        $request = ApprovalRequest::find($id);
        if (!$request) return;

        $user = Auth::user();
        $nextLevel = $request->current_level + 1;

        $rule = app(ApprovalService::class)->getRuleForLevel($request->request_type, $nextLevel, $request->business_unit_id);

        if ($rule && $rule->role) {
            if (!$user->hasRole($rule->role->name) && !$user->hasRole('superadmin')) {
                $this->dispatch('toast', title: 'Akses Ditolak', message: 'Anda tidak memiliki role yang diizinkan untuk menolak di Level ' . $nextLevel, type: 'error');
                return;
            }
        }

        $this->rejectingApprovalId = $id;
        $this->rejectionReason = '';
    }

    public function cancelReject()
    {
        $this->rejectingApprovalId = null;
        $this->rejectionReason = '';
        $this->resetValidation('rejectionReason');
    }

    public function executeReject()
    {
        $this->validate([
            'rejectionReason' => 'required|min:5',
        ], [
            'rejectionReason.required' => 'Alasan penolakan wajib diisi.',
            'rejectionReason.min'      => 'Alasan penolakan minimal 5 karakter.',
        ]);

        if ($this->rejectingApprovalId) {
            $this->reject($this->rejectingApprovalId, $this->rejectionReason);
            $this->cancelReject();
        }
    }

    public function reject($id, $reason = null)
    {
        $request = ApprovalRequest::find($id);
        if (!$request) return;

        $user = Auth::user();
        $nextLevel = $request->current_level + 1;

        $rule = app(ApprovalService::class)->getRuleForLevel($request->request_type, $nextLevel, $request->business_unit_id);

        if ($rule && $rule->role) {
            if (!$user->hasRole($rule->role->name) && !$user->hasRole('superadmin')) {
                $this->dispatch('toast', title: 'Akses Ditolak', message: 'Anda tidak memiliki role yang diizinkan untuk menolak di Level ' . $nextLevel, type: 'error');
                return;
            }
        }

        $notes = $reason ? $reason . ' (Ditolak oleh ' . $user->name . ')' : 'Rejected by ' . $user->name;

        $request->histories()->create([
            'acted_by'      => $user->id,
            'role_snapshot' => $user->roles->pluck('name')->join(', '),
            'action'        => 'REJECTED',
            'level'         => $nextLevel,
            'notes'         => $notes
        ]);

        $request->update(['status' => 'REJECTED']);

        try {
            $request->executeRejectedAction();
        } catch (Exception $e) {
            Log::error("Failed to execute rejected action for Request ID {$request->id}: " . $e->getMessage());
        }

        $teksGrup = "❌ *APPROVAL DITOLAK*\nPengajuan {$request->request_type} (ID: {$request->id}) telah DITOLAK oleh {$user->name}.";
        ApprovalController::sendGroupNotification($teksGrup, $request->business_unit_id);

        $this->dispatch('toast', title: 'Berhasil', message: 'Pengajuan telah ditolak.', type: 'info');
    }

    public function render()
    {
        $user = Auth::user();
        $isGlobal = $user->hasAnyRole(['admin', 'direktur', 'superadmin']);

        $requests = ApprovalRequest::with([
            'approvable',
            'requestedBy.branch',
            'businessUnit',
            'branch',
            'histories.actedBy'
        ])
            // Scoping Business Unit
            ->when(!$isGlobal, function ($q) use ($user) {
                // User lokal dikunci ke BU dan Cabangnya
                if ($user->business_unit_id) {
                    $q->where('business_unit_id', $user->business_unit_id);
                }
                if ($user->branch_id && $user->hasAnyRole(['bm', 'bm_gsk', 'supervisor', 'kasir', 'fl', 'fl_gsk'])) {
                    $q->where('branch_id', $user->branch_id);
                }
            })
            ->when($isGlobal && $this->filterBusinessUnitId, function ($q) {
                $q->where('business_unit_id', $this->filterBusinessUnitId);
            })
            ->when($this->search, function ($q) {
                $q->whereHas('requestedBy', function ($uq) {
                    $uq->where('name', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filterStatus !== 'ALL', function ($q) {
                $q->where('status', $this->filterStatus);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $businessUnits = BusinessUnit::all();
        $layout = request()->routeIs('zoffline.*') ? 'layouts.z' : 'layouts.admin';

        return view('livewire.admin.approvals.index', [
            'requests'      => $requests,
            'businessUnits' => $businessUnits,
            'isGlobal'      => $isGlobal,
        ])->layout($layout, ['title' => 'Persetujuan Transaksi']);
    }
}

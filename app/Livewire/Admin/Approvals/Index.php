<?php

namespace App\Livewire\Admin\Approvals;

use App\Models\ApprovalRequest;
use App\Models\Order;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;
use App\Models\ApprovalRule;

class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $filterStatus = 'PENDING';

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

    public function viewDetail($id)
    {
        $this->detailRequest = ApprovalRequest::with([
            'approvable',
            'requestedBy.branch',
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

    public function confirmApprove($id)
    {
        $request = ApprovalRequest::find($id);
        if (!$request) return;

        $user = Auth::user();
        $nextLevel = $request->current_level + 1;

        // Validasi Role (Early Check)
        $rule = ApprovalRule::with('role')->where('module', $request->request_type)->where('level', $nextLevel)->first();

        if ($rule && $rule->role) {
            if (!$user->hasRole($rule->role->name) && !$user->hasRole('superadmin')) {
                $this->dispatch('toast', title: 'Akses Ditolak', message: 'Anda tidak memiliki role yang diizinkan untuk menyetujui Level ' . $nextLevel, type: 'error');
                return;
            }
        }

        if ($nextLevel >= $request->required_level) {
            $this->confirmingApprovalId = $id;
            $this->confirmingRequestType = $request->request_type;
        } else {
            $this->approve($id); // Langsung setujui kalau belum tahap akhir
        }
    }

    public function executeApprove()
    {
        if ($this->confirmingApprovalId) {
            $this->approve($this->confirmingApprovalId);
            $this->cancelApprove();
        }
    }

    public function cancelApprove()
    {
        $this->confirmingApprovalId = null;
        $this->confirmingRequestType = null;
        $this->extensionDays = 7;
    }

    public function approve($id)
    {
        $request = ApprovalRequest::find($id);
        if (!$request) return;

        $user = Auth::user();
        $nextLevel = $request->current_level + 1;

        // Validasi Role
        $rule = ApprovalRule::with('role')->where('module', $request->request_type)->where('level', $nextLevel)->first();

        if ($rule && $rule->role) {
            if (!$user->hasRole($rule->role->name) && !$user->hasRole('superadmin')) {
                $this->dispatch('toast', title: 'Akses Ditolak', message: 'Anda tidak memiliki role yang diizinkan untuk menyetujui Level ' . $nextLevel, type: 'error');
                return;
            }
        }

        // Add history
        $request->histories()->create([
            'acted_by' => $user->id,
            'action' => 'APPROVED',
            'level' => $request->current_level + 1,
            'notes' => 'Approved by ' . $user->name
        ]);

        $request->current_level += 1;

        if ($request->current_level >= $request->required_level) {
            $request->status = 'APPROVED';
            $request->save();

            try {
                $request->executeAction([
                    'extension_days' => $this->extensionDays
                ]);

                // --- KIRIM NOTIFIKASI GRUP TELEGRAM ---
                $kasirName = $request->requestedBy->name ?? 'Kasir';
                $tipe = str_replace('_', ' ', $request->request_type) . " (Level {$request->required_level})";
                $orderInfo = '-';
                if ($request->approvable_type === \App\Models\Order::class && $request->approvable) {
                    $orderInfo = $request->approvable->order_number;
                }
                $cabang = $request->requestedBy->branch->name ?? '-';
                $waktu = $request->created_at->format('d M Y H:i');
                $alasan = $request->reason ?? '-';

                $teksGrup = "✅ *APPROVAL SUKSES*\n\n"
                    . "Pengajuan: {$tipe} untuk {$orderInfo}\n"
                    . "Kasir: {$kasirName}\n"
                    . "Waktu: {$waktu}\n"
                    . "Cabang: {$cabang}\n"
                    . "Keterangan: \"{$alasan}\"\n\n"
                    . "Telah disetujui sepenuhnya oleh *{$user->name}* (via Web).";

                \App\Http\Controllers\ApprovalController::sendGroupNotification($teksGrup);
                // ----------------------------------------

                $msg = $request->request_type === 'ORDER_CANCELLATION'
                    ? 'Persetujuan berhasil dan transaksi dibatalkan di Accurate.'
                    : 'Persetujuan berhasil dieksekusi.';

                $this->dispatch('toast', title: 'Berhasil', message: $msg, type: 'success');
            } catch (\Exception $e) {
                $this->dispatch('toast', title: 'Error Eksekusi', message: 'Gagal mengeksekusi persetujuan: ' . $e->getMessage(), type: 'error');
            }
        } else {
            $request->save();

            // Trigger notifikasi untuk level selanjutnya
            \App\Http\Controllers\ApprovalController::sendTelegramNotification($request);

            $this->dispatch('toast', title: 'Berhasil', message: 'Disetujui. Menunggu persetujuan level selanjutnya.', type: 'success');
        }
    }

    public function confirmReject($id)
    {
        $request = ApprovalRequest::find($id);
        if (!$request) return;

        $user = Auth::user();
        $nextLevel = $request->current_level + 1;

        // Validasi Role (Early Check)
        $rule = ApprovalRule::with('role')->where('module', $request->request_type)->where('level', $nextLevel)->first();

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
            'rejectionReason.min' => 'Alasan penolakan minimal 5 karakter.',
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

        // Validasi Role
        $rule = ApprovalRule::with('role')->where('module', $request->request_type)->where('level', $nextLevel)->first();

        if ($rule && $rule->role) {
            if (!$user->hasRole($rule->role->name) && !$user->hasRole('superadmin')) {
                $this->dispatch('toast', title: 'Akses Ditolak', message: 'Anda tidak memiliki role yang diizinkan untuk menolak di Level ' . $nextLevel, type: 'error');
                return;
            }
        }

        $notes = $reason ? $reason . ' (Ditolak oleh ' . $user->name . ')' : 'Rejected by ' . $user->name;

        $request->histories()->create([
            'acted_by' => $user->id,
            'action' => 'REJECTED',
            'level' => $request->current_level + 1,
            'notes' => $notes
        ]);

        $request->update(['status' => 'REJECTED']);

        try {
            $request->executeRejectedAction();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to execute rejected action for Request ID {$request->id}: " . $e->getMessage());
        }

        $this->dispatch('toast', title: 'Berhasil', message: 'Pengajuan telah ditolak.', type: 'info');
    }

    public function render()
    {
        $user = Auth::user();
        $isGlobal = $user->hasAnyRole(['admin', 'direktur', 'superadmin', 'manager_operasional']);

        $requests = ApprovalRequest::with(['approvable', 'requestedBy.branch', 'histories.actedBy'])
            ->when(!$isGlobal, function ($q) use ($user) {
                $q->whereHas('requestedBy', function ($uq) use ($user) {
                    // Filter berdasarkan Business Unit
                    if ($user->business_unit_id) {
                        $uq->where('business_unit_id', $user->business_unit_id);
                    }
                    // Filter lebih spesifik ke Cabang jika role-nya ada di level cabang
                    if ($user->branch_id && $user->hasAnyRole(['bm', 'supervisor', 'kasir'])) {
                        $uq->where('branch_id', $user->branch_id);
                    }
                });
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

        $layout = request()->routeIs('zoffline.*') ? 'layouts.z' : 'layouts.admin';

        return view('livewire.admin.approvals.index', [
            'requests' => $requests
        ])->layout($layout, ['title' => 'Persetujuan Pembatalan']);
    }
}

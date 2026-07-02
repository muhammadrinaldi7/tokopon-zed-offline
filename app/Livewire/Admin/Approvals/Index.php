<?php

namespace App\Livewire\Admin\Approvals;

use App\Models\ApprovalRequest;
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
                $this->dispatch('toast', title: 'Berhasil', message: 'Persetujuan berhasil dieksekusi.', type: 'success');
            } catch (\Exception $e) {
                $this->dispatch('toast', title: 'Error Eksekusi', message: 'Gagal mengeksekusi persetujuan: ' . $e->getMessage(), type: 'error');
            }
        } else {
            $request->save();
            $this->dispatch('toast', title: 'Berhasil', message: 'Disetujui. Menunggu persetujuan level selanjutnya.', type: 'success');
        }
    }

    public function reject($id)
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

        $request->histories()->create([
            'acted_by' => $user->id,
            'action' => 'REJECTED',
            'level' => $request->current_level + 1,
            'notes' => 'Rejected by ' . $user->name
        ]);

        $request->update(['status' => 'REJECTED']);
        $this->dispatch('toast', title: 'Berhasil', message: 'Pengajuan pembatalan telah ditolak.', type: 'info');
    }

    public function render()
    {
        $requests = ApprovalRequest::with(['approvable', 'requestedBy', 'histories.actedBy'])
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

<?php

namespace App\Livewire\Admin\SellPhone;

use App\Exports\SellPhoneIssuesExport;
use App\Models\SellPhone;
use App\Models\SellPhoneIssue;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class SellPhoneIssuesIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $categoryFilter = '';
    public string $dateFrom = '';
    public string $dateTo = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function resetFilter(): void
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->categoryFilter = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
    }

    public function toggleStatus(int $issueId): void
    {
        $issue = SellPhoneIssue::find($issueId);
        if ($issue) {
            if ($issue->status === 'OPEN') {
                $issue->update([
                    'status' => 'RESOLVED',
                    'resolved_by' => Auth::id(),
                    'resolved_at' => now(),
                    'resolution_notes' => 'Diselesaikan via daftar kendala oleh ' . (Auth::user()->name ?? 'Admin'),
                ]);
                $message = "Kendala pada transaksi " . ($issue->sellPhone ? 'SPL-' . $issue->sellPhone->id : '#' . $issue->sell_phone_id) . " ditandai Selesai.";
            } else {
                $issue->update([
                    'status' => 'OPEN',
                    'resolved_by' => null,
                    'resolved_at' => null,
                    'resolution_notes' => null,
                ]);
                $message = "Kendala pada transaksi " . ($issue->sellPhone ? 'SPL-' . $issue->sellPhone->id : '#' . $issue->sell_phone_id) . " dibuka kembali.";
            }

            $this->dispatch('toast', title: 'Berhasil', message: $message, type: 'info');
        }
    }

    public function deleteIssue(int $issueId): void
    {
        $issue = SellPhoneIssue::find($issueId);
        if ($issue) {
            $splNumber = $issue->sellPhone ? 'SPL-' . $issue->sellPhone->id : '#' . $issue->sell_phone_id;
            $issue->delete();
            $this->dispatch('toast', title: 'Berhasil', message: "Catatan kendala pada {$splNumber} telah dihapus.", type: 'success');
        }
    }

    private function getFilteredQuery()
    {
        $user = Auth::user();
        $activeUnitId = $user ? $user->getActiveBusinessUnitId() : null;

        $query = SellPhoneIssue::with([
            'sellPhone.user.profile',
            'sellPhone.user.bankAccounts',
            'sellPhone.businessUnit',
            'sellPhone.handledBy',
            'user'
        ])->latest();

        if ($activeUnitId) {
            $query->whereHas('sellPhone', function ($q) use ($activeUnitId) {
                $q->where('business_unit_id', $activeUnitId);
            });
        }

        if ($this->search) {
            $term = trim($this->search);
            $cleanTerm = str_ireplace(['#SPL-', 'SPL-', '#'], '', $term);

            $query->where(function ($q) use ($term, $cleanTerm) {
                $q->where('comment', 'like', "%{$term}%")
                    ->orWhereHas('sellPhone', function ($sq) use ($term, $cleanTerm) {
                        $sq->where('id', 'like', "%{$cleanTerm}%")
                            ->orWhere('phone_brand', 'like', "%{$term}%")
                            ->orWhere('phone_model', 'like', "%{$term}%")
                            ->orWhere('bank_name', 'like', "%{$term}%")
                            ->orWhere('bank_account_number', 'like', "%{$term}%")
                            ->orWhere('bank_account_name', 'like', "%{$term}%")
                            ->orWhere('imei', 'like', "%{$term}%")
                            ->orWhereHas('user', function ($uq) use ($term) {
                                $uq->where('name', 'like', "%{$term}%")
                                    ->orWhere('email', 'like', "%{$term}%");
                            });
                    })
                    ->orWhereHas('user', function ($uq) use ($term) {
                        $uq->where('name', 'like', "%{$term}%");
                    });
            });
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        if ($this->categoryFilter) {
            $query->where('category', $this->categoryFilter);
        }

        if ($this->dateFrom) {
            $query->where('created_at', '>=', Carbon::parse($this->dateFrom)->startOfDay());
        }

        if ($this->dateTo) {
            $query->where('created_at', '<=', Carbon::parse($this->dateTo)->endOfDay());
        }

        return $query;
    }

    public function exportExcel()
    {
        $issues = $this->getFilteredQuery()->get();

        if ($issues->isEmpty()) {
            $this->dispatch('toast', title: 'Perhatian', message: 'Tidak ada data kendala penjualan HP untuk diexport sesuai filter yang dipilih.', type: 'warning');
            return null;
        }

        $filename = 'Laporan-Kendala-SellPhone-' . date('Ymd-His') . '.xlsx';

        return Excel::download(new SellPhoneIssuesExport($issues), $filename);
    }

    public function render()
    {
        $user = Auth::user();
        $activeUnitId = $user ? $user->getActiveBusinessUnitId() : null;

        $baseCountQuery = SellPhoneIssue::query();
        if ($activeUnitId) {
            $baseCountQuery->whereHas('sellPhone', function ($q) use ($activeUnitId) {
                $q->where('business_unit_id', $activeUnitId);
            });
        }

        $totalIssues = (clone $baseCountQuery)->count();
        $openIssues = (clone $baseCountQuery)->where('status', 'OPEN')->count();
        $resolvedIssues = (clone $baseCountQuery)->where('status', 'RESOLVED')->count();

        // Cari kategori kesalahan terbanyak
        $topCategory = (clone $baseCountQuery)
            ->select('category', DB::raw('count(*) as total'))
            ->groupBy('category')
            ->orderByDesc('total')
            ->first();

        $categoryLabels = [
            'SALAH_NOREK' => 'Salah No. Rekening / Bank',
            'SALAH_NOMINAL' => 'Salah Input Nominal / Taksiran',
            'SALAH_QC' => 'Salah Hasil Inspeksi / Grade QC',
            'SALAH_IMEI' => 'Salah Input IMEI / SN',
            'GAGAL_TRANSFER' => 'Gagal Transfer / Rekening Pasif',
            'LAINNYA' => 'Kendala Lainnya',
        ];

        $issues = $this->getFilteredQuery()->paginate(15);

        $layout = request()->routeIs('reporting.*') || request()->routeIs('zoffline.*') ? 'layouts.z' : 'layouts.admin';

        return view('livewire.admin.sell-phone.sell-phone-issues-index', [
            'issues' => $issues,
            'totalIssues' => $totalIssues,
            'openIssues' => $openIssues,
            'resolvedIssues' => $resolvedIssues,
            'topCategory' => $topCategory ? ($categoryLabels[$topCategory->category] ?? $topCategory->category) : '-',
            'topCategoryCount' => $topCategory ? $topCategory->total : 0,
            'categoryLabels' => $categoryLabels,
        ])->layout($layout, ['title' => 'Kendala & Kesalahan Pembelian HP']);
    }
}

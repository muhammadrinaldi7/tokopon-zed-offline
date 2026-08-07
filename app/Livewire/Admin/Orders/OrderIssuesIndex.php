<?php

namespace App\Livewire\Admin\Orders;

use App\Exports\OrderIssuesExport;
use App\Models\Order;
use App\Models\OrderIssue;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class OrderIssuesIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $categoryFilter = '';
    public string $dateFrom = '';
    public string $dateTo = '';

    // Receipt Modal state
    public bool $showReceiptModal = false;
    public ?Order $completedOrder = null;

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
        $issue = OrderIssue::find($issueId);
        if ($issue) {
            $newStatus = $issue->status === 'OPEN' ? 'RESOLVED' : 'OPEN';
            $issue->update(['status' => $newStatus]);

            $message = $newStatus === 'RESOLVED'
                ? "Kendala pada pesanan #{$issue->order?->order_number} ditandai Selesai."
                : "Kendala pada pesanan #{$issue->order?->order_number} dibuka kembali.";

            $this->dispatch('toast', title: 'Berhasil', message: $message, type: 'info');
        }
    }

    public function deleteIssue(int $issueId): void
    {
        $issue = OrderIssue::find($issueId);
        if ($issue) {
            $orderNumber = $issue->order?->order_number ?? '';
            $issue->delete();
            $this->dispatch('toast', title: 'Berhasil', message: "Catatan kendala pada #{$orderNumber} telah dihapus.", type: 'success');
        }
    }

    public function viewReceipt(int $orderId): void
    {
        $this->completedOrder = Order::with(['items.variant', 'user', 'payments.paymentMethod', 'handledBy', 'salesBy'])->find($orderId);

        if ($this->completedOrder) {
            $this->showReceiptModal = true;
        }
    }

    public function closeReceipt(): void
    {
        $this->showReceiptModal = false;
        $this->completedOrder = null;
    }

    private function getFilteredQuery()
    {
        $query = OrderIssue::with([
            'order.user.profile',
            'order.branch',
            'order.handledBy',
            'order.salesBy',
            'user'
        ])->latest();

        if ($this->search) {
            $term = trim($this->search);
            $query->where(function ($q) use ($term) {
                $q->where('comment', 'like', "%{$term}%")
                    ->orWhereHas('order', function ($oq) use ($term) {
                        $oq->where('order_number', 'like', "%{$term}%")
                            ->orWhereHas('user', function ($uq) use ($term) {
                                $uq->where('name', 'like', "%{$term}%");
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
            $this->dispatch('toast', title: 'Perhatian', message: 'Tidak ada data kendala untuk diexport sesuai filter yang dipilih.', type: 'warning');
            return null;
        }

        $filename = 'Laporan-Kendala-Order-' . date('Ymd-His') . '.xlsx';

        return Excel::download(new OrderIssuesExport($issues), $filename);
    }

    #[Layout('layouts.admin', ['title' => 'Kendala & Kesalahan Pesanan'])]
    public function render()
    {
        $totalIssues = OrderIssue::count();
        $openIssues = OrderIssue::where('status', 'OPEN')->count();
        $resolvedIssues = OrderIssue::where('status', 'RESOLVED')->count();

        // Cari kategori kesalahan terbanyak
        $topCategory = OrderIssue::select('category', DB::raw('count(*) as total'))
            ->groupBy('category')
            ->orderByDesc('total')
            ->first();

        $categoryLabels = [
            'SALAH_PRODUK' => 'Salah Produk / Varian',
            'SALAH_SN' => 'Salah Serial Number (SN)',
            'SELISIH_BAYAR' => 'Selisih / Salah Bayar',
            'SALAH_CUSTOMER' => 'Salah Customer',
            'SALAH_PROMO' => 'Salah Diskon / Promo',
            'SYNC_ACCURATE' => 'Kendala Accurate',
            'LAINNYA' => 'Lainnya',
        ];

        $issues = $this->getFilteredQuery()->paginate(15);

        return view('livewire.admin.orders.order-issues-index', [
            'issues' => $issues,
            'totalIssues' => $totalIssues,
            'openIssues' => $openIssues,
            'resolvedIssues' => $resolvedIssues,
            'topCategory' => $topCategory ? ($categoryLabels[$topCategory->category] ?? $topCategory->category) : '-',
            'topCategoryCount' => $topCategory ? $topCategory->total : 0,
            'categoryLabels' => $categoryLabels,
        ]);
    }
}

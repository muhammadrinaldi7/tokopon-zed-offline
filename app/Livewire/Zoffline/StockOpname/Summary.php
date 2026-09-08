<?php

namespace App\Livewire\Zoffline\StockOpname;

use App\Models\ApprovalRequest;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Models\StockOpnameSerial;
use App\Services\ApprovalService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.z', ['title' => 'Review & Berita Acara Stock Opname'])]
class Summary extends Component
{
    public StockOpname $opname;
    public $notes = '';

    public function mount(StockOpname $opname)
    {
        $user = Auth::user();
        $buId = $user->getActiveBusinessUnitId();
        $isGlobal = $user->hasAnyRole(['superadmin', 'admin', 'director']);

        if ($opname->business_unit_id !== $buId) {
            abort(403, 'Akses ditolak: Unit bisnis tidak sesuai.');
        }

        if (!$isGlobal && $user->branch_id && $opname->branch_id !== $user->branch_id) {
            abort(403, 'Akses ditolak: Anda hanya dapat mengakses opname cabang Anda sendiri.');
        }

        $this->opname = $opname;
        $this->notes = $opname->notes ?? '';
    }

    /**
     * Submit Laporan Stock Opname
     */
    public function submitOpname()
    {
        $user = Auth::user();
        $this->opname->calculateTotals();

        // Jika terdapat selisih, keterangan berita acara wajib diisi
        if ($this->opname->total_difference_qty != 0) {
            $this->validate([
                'notes' => 'required|min:10|max:1000',
            ], [
                'notes.required' => 'Wajib mengisi keterangan/alasan pada Berita Acara jika terdapat selisih fisik.',
                'notes.min'      => 'Keterangan Berita Acara minimal 10 karakter.',
            ]);
        }

        $this->opname->update([
            'notes'    => $this->notes,
            'end_time' => now(),
        ]);

        // KONDISI A: Tidak Ada Selisih sama sekali (0 Discrepancy)
        if ($this->opname->total_difference_qty == 0 && $this->opname->total_loss_value == 0 && $this->opname->total_surplus_value == 0) {
            $this->opname->update([
                'status' => 'COMPLETED',
            ]);

            $this->dispatch('toast', title: 'Sukses', message: 'Hebat! Stok fisik 100% cocok dengan buku sistem. Sesi opname selesai.', type: 'success');
            return;
        }

        // KONDISI B: Ada Selisih -> Ajukan ke Approval Center
        try {
            $approvalService = app(ApprovalService::class);

            $payload = [
                'opname_number'       => $this->opname->opname_number,
                'branch_name'         => $this->opname->branch->name ?? '-',
                'warehouse_name'      => $this->opname->warehouse->name ?? '-',
                'total_system_qty'    => $this->opname->total_system_qty,
                'total_physical_qty'  => $this->opname->total_physical_qty,
                'total_difference'    => $this->opname->total_difference_qty,
                'total_loss_value'    => (float) $this->opname->total_loss_value,
                'total_surplus_value' => (float) $this->opname->total_surplus_value,
                'notes'               => $this->notes,
            ];

            $approvalService->createRequest([
                'approvable_type'  => StockOpname::class,
                'approvable_id'    => $this->opname->id,
                'request_type'     => 'STOCK_OPNAME_REPORT',
                'requested_by'     => $user->id,
                'business_unit_id' => $this->opname->business_unit_id,
                'branch_id'        => $this->opname->branch_id,
                'amount'           => $this->opname->total_loss_value ?: $this->opname->total_surplus_value,
                'reason'           => $this->notes,
                'payload'          => $payload,
            ]);

            $this->opname->update([
                'status' => 'PENDING_APPROVAL',
            ]);

            $this->dispatch('toast', title: 'Diajukan', message: 'Laporan Berita Acara Stock Opname berhasil diajukan untuk verifikasi manajemen.', type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('toast', title: 'Gagal Mengajukan', message: 'Terjadi kesalahan: ' . $e->getMessage(), type: 'error');
        }
    }

    /**
     * Buka kembali sesi untuk hitung ulang (Re-count) jika ditolak atau masih dalam COUNTING
     */
    public function reopenForCounting()
    {
        $this->opname->update([
            'status' => 'COUNTING',
        ]);

        return $this->redirectRoute('zoffline.stock-opname.count', $this->opname->id, navigate: true);
    }

    public function render()
    {
        $this->opname->refresh();

        // Ambil item yang memiliki selisih fisik
        $discrepancyItems = StockOpnameItem::where('stock_opname_id', $this->opname->id)
            ->where('difference_qty', '!=', 0)
            ->orderBy('difference_qty', 'asc')
            ->get();

        // Ambil serial number yang Missing (hilang)
        $missingSerials = StockOpnameSerial::with('stockOpnameItem')
            ->where('stock_opname_id', $this->opname->id)
            ->where('status', 'MISSING')
            ->get();

        // Ambil serial number yang Unexpected (nyasar)
        $unexpectedSerials = StockOpnameSerial::with('stockOpnameItem')
            ->where('stock_opname_id', $this->opname->id)
            ->where('status', 'UNEXPECTED')
            ->get();

        $latestApproval = $this->opname->latestApprovalRequest;

        return view('livewire.zoffline.stock-opname.summary', [
            'discrepancyItems'  => $discrepancyItems,
            'missingSerials'    => $missingSerials,
            'unexpectedSerials' => $unexpectedSerials,
            'latestApproval'    => $latestApproval,
        ]);
    }
}

<?php

namespace App\Livewire\Admin\Inventory\StockAdjustment;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Jobs\ProcessStockAdjustment;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin', ['title' => 'Penyesuaian Stock'])]
class Index extends Component
{
    use WithFileUploads;

    public $csv_file;
    public $isProcessing = false;
    public $message = '';

    public function updatedCsvFile()
    {
        $this->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        try {
            $filePath = $this->csv_file->store('temp_stock');

            ProcessStockAdjustment::dispatch($filePath);

            $this->isProcessing = true;
            $this->message = 'File CSV berhasil di-upload! Sistem sedang memproses sinkronisasi stok ke Accurate di latar belakang.';

            $this->reset('csv_file');
        } catch (\Exception $e) {
            $this->message = 'Gagal memproses file: ' . $e->getMessage();
            $this->isProcessing = false;
        }
    }

    public function checkQueueStatus()
    {
        if ($this->isProcessing) {
            // Cek apakah masih ada job bernama 'ProcessStockAdjustment' di tabel jobs
            $queueCount = DB::table('jobs')
                ->where('payload', 'like', '%ProcessStockAdjustment%')
                ->count();

            // Jika sudah 0, berarti background job sudah selesai diproses!
            if ($queueCount === 0) {
                $this->isProcessing = false;
                $this->message = 'Selamat! Semua data adjustment dari CSV berhasil disinkronisasikan ke Accurate.';
            }
        }
    }

    public function clearMessage()
    {
        $this->reset('message', 'isProcessing');
    }

    public function render()
    {
        return view('components.admin.inventory.stock-adjustment.index');
    }
}

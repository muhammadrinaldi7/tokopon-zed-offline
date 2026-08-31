<?php

namespace App\Livewire\Zoffline\Qc;

use Livewire\Component;
use App\Models\WarrantyClaim;
use Livewire\Attributes\On;
use App\Services\AccurateService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ReturnInspect extends Component
{
    public WarrantyClaim $claim;

    public function mount(WarrantyClaim $claim)
    {
        $this->claim = $claim->load(['warranty.orderItem.variant', 'customer']);

        // Cek jika sudah di-QC
        if ($this->claim->inspection()->exists()) {
            session()->flash('error', 'Barang retur ini sudah melalui proses QC.');
            return redirect()->route('zoffline.qc-returns');
        }
    }

    #[On('qc-inspection-saved')]
    public function handleQcSaved($verdict)
    {
        try {
            Log::info("QC Return tersimpan untuk klaim ID: {$this->claim->id} dengan hasil: {$verdict}");

            // 1. Dapatkan Business Unit Code
            $businessUnitCode = $this->claim->warranty->policy->businessUnit->code ?? 'syihab';

            // 2. Tentukan SKU Asli dan SKU Tujuan (berdasarkan verdict / manual)
            // Asumsi: SKU lama bisa didapat dari $this->claim->warranty->orderItem->variant->accurateData->item_no
            // Untuk SKU tujuan (Grade B/C), butuh logic lebih lanjut. Sebagai MVP, kita gunakan SKU original tapi dipindah Gudang.
            // Atau cukup buat API hit (Item Adjustment out dari gudang retur, in ke gudang cabang)

            $variant = $this->claim->warranty->orderItem->variant ?? null;
            $originalItemNo = 'UNKNOWN-SKU';
            if ($variant) {
                if (isset($variant->item_no)) {
                    $originalItemNo = $variant->item_no;
                } elseif ($variant->accurateData) {
                    $originalItemNo = $variant->accurateData->item_no;
                }
            }

            $warehouseReturn = $this->claim->warranty->policy->businessUnit->accurate_return_warehouse_name ?? 'GSK - Return';
            $warehouseBranch = Auth::user()->warehouse->name ?? 'Gudang Utama';

            $accurateService = app(AccurateService::class);

            // Item Adjustment Payload:
            // 1 baris Pengurangan (dari Gudang Retur)
            // 1 baris Penambahan (ke Gudang Cabang)
            $payload = [
                'transDate' => now()->format('d/m/Y'),
                'adjustmentAccountNo' => '110301', // Asumsi akun persediaan/penyesuaian
                'notes' => "QC Retur Selesai (Hasil: {$verdict}). Pindah dari Gudang Retur ke Cabang.",
                'detailItem' => [
                    // Keluarkan dari Gudang Retur
                    [
                        'itemNo' => $originalItemNo,
                        'quantity' => 1,
                        'typeAdjust' => 'REDUCTION',
                        'warehouseName' => $warehouseReturn,
                        'detailSerialNumber' => [
                            ['serialNumberNo' => $this->claim->serial_number, 'quantity' => 1]
                        ]
                    ],
                    // Masukkan ke Gudang Cabang
                    [
                        'itemNo' => $originalItemNo, // Idealnya ganti SKU Grade B/C
                        'quantity' => 1,
                        'typeAdjust' => 'ADDITION',
                        'warehouseName' => $warehouseBranch,
                        'detailSerialNumber' => [
                            ['serialNumberNo' => $this->claim->serial_number, 'quantity' => 1]
                        ]
                    ]
                ]
            ];

            Log::info("Mencoba melakukan Item Adjustment di Accurate:", $payload);

            $accurateResponse = $accurateService->postItemAdjustment($payload, $businessUnitCode);
            Log::info("Respon Item Adjustment:", (array)$accurateResponse);

            session()->flash('success', 'Inspeksi QC berhasil disimpan. Stok telah disesuaikan dari Gudang Retur ke Gudang Cabang.');
        } catch (\Exception $e) {
            Log::error("Gagal menyesuaikan stok Accurate setelah QC Retur: " . $e->getMessage());
            session()->flash('error', 'Inspeksi tersimpan, namun gagal menyesuaikan stok di Accurate: ' . $e->getMessage());
        }

        return redirect()->route('zoffline.qc-returns');
    }

    public function render()
    {
        return view('livewire.zoffline.qc.return-inspect')
            ->layout('layouts.z'); // Sesuaikan layout admin jika perlu, misal 'components.layouts.app'
    }
}

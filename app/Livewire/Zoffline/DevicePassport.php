<?php

namespace App\Livewire\Zoffline;

use Livewire\Component;
use App\Models\OrderItem;
use App\Models\SellPhone;
use App\Models\WarrantyClaim;
use App\Models\DeviceInspection;

class DevicePassport extends Component
{
    public $searchImei = '';
    public $deviceHistory = [];
    public $deviceInfo = null;
    public $searched = false;

    public function search()
    {
        $this->validate([
            'searchImei' => 'required|string|min:5'
        ], [
            'searchImei.required' => 'Masukkan IMEI / Serial Number.',
            'searchImei.min' => 'IMEI minimal 5 karakter.'
        ]);

        $imei = trim($this->searchImei);
        $timeline = [];
        $latestStatus = 'Unknown';
        $deviceModel = 'Unknown Device';
        $deviceSpecs = '';

        // 1. Cari di Penjualan (OrderItem melalui OrderItemSerialNumber)
        $orderItemSns = \App\Models\OrderItemSerialNumber::with(['orderItem.order.user', 'orderItem.order.businessUnit', 'orderItem.variant', 'warranty'])
            ->where('serial_number', 'LIKE', '%' . $imei . '%')
            ->get();

        foreach ($orderItemSns as $snRecord) {
            $item = $snRecord->orderItem;
            if (!$item) continue;

            $deviceModel = $item->product_name;
            $deviceSpecs = $item->variant ? $item->variant->name : '';
            
            $status = 'Terjual';
            if ($snRecord->warranty && $snRecord->warranty->status === 'voided') {
                $status = 'Terjual (Garansi Void)';
            }

            $timeline[] = [
                'date' => $item->created_at,
                'type' => 'Penjualan (Sales)',
                'icon' => 'shopping-cart',
                'color' => 'bg-green-500',
                'description' => "Perangkat dijual pada order #" . ($item->order->order_number ?? '-'),
                'status' => $status,
                'meta' => [
                    'Customer' => $item->order->user->name ?? 'Tamu',
                    'Cabang' => $item->order->businessUnit->name ?? '-',
                    'Harga' => 'Rp ' . number_format($item->price, 0, ',', '.')
                ]
            ];
        }

        // 2. Cari di Pembelian Bekas (SellPhone) via DeviceInspection atau detail SellPhone
        $inspections = DeviceInspection::with(['inspectable', 'inspector'])
            ->where('imei', 'LIKE', '%' . $imei . '%')
            ->get();

        foreach ($inspections as $inspection) {
            $typeLabel = 'Inspeksi QC';
            $desc = "Diinspeksi oleh " . ($inspection->inspector ? $inspection->inspector->name : 'Sistem') . ". Hasil: " . strtoupper($inspection->verdict) . ".";
            
            if ($inspection->inspectable_type === SellPhone::class && $inspection->inspectable) {
                $typeLabel = 'Inspeksi Buyback';
                $sellPhone = $inspection->inspectable;
                $deviceModel = $sellPhone->phone_brand . ' ' . $sellPhone->phone_model;
                $deviceSpecs = $sellPhone->phone_ram . ' RAM / ' . $sellPhone->phone_storage;
                
                $desc .= " Dari pelanggan " . ($sellPhone->user ? $sellPhone->user->name : 'Unknown') . ".";
                
                // Tambahkan event SellPhone dibuat (sebagai event masuk barang)
                $timeline[] = [
                    'date' => $sellPhone->created_at,
                    'type' => 'Pembelian Bekas (Buyback)',
                    'icon' => 'arrow-down-tray',
                    'color' => 'bg-blue-500',
                    'description' => "Pengajuan jual HP dari pelanggan.",
                    'status' => $sellPhone->status,
                    'meta' => [
                        'ID' => 'BUYBACK-' . $sellPhone->id,
                        'Penawaran' => 'Rp ' . number_format($sellPhone->appraised_value, 0, ',', '.')
                    ]
                ];
            }

            $checklist = is_string($inspection->checklist_results) ? json_decode($inspection->checklist_results, true) : $inspection->checklist_results;
            $passed = 0;
            $failed = 0;
            $checklistDetails = [];
            
            if (is_array($checklist)) {
                foreach ($checklist as $chk) {
                    if (isset($chk['type']) && $chk['type'] === 'boolean') {
                        if (isset($chk['value']) && ($chk['value'] == 1 || $chk['value'] == '1' || $chk['value'] === true)) {
                            $passed++;
                        } else {
                            $failed++;
                        }
                    }
                    $checklistDetails[] = $chk;
                }
            }

            $timeline[] = [
                'date' => $inspection->created_at,
                'type' => $typeLabel,
                'icon' => 'clipboard-document-check',
                'color' => $inspection->verdict === 'pass' ? 'bg-emerald-500' : 'bg-rose-500',
                'description' => $desc,
                'status' => $inspection->verdict === 'pass' ? 'LULUS QC' : 'TIDAK LULUS',
                'checklist' => $checklistDetails,
                'passed_points' => $passed,
                'failed_points' => $failed,
                'total_points' => $passed + $failed,
                'meta' => [
                    'Skor' => $inspection->score . '/100',
                    'Catatan' => $inspection->notes ?: '-'
                ]
            ];
        }

        // 3. Cari di Klaim Garansi (sebagai barang yang diretur, ATAU barang pengganti)
        // Cari klaim dimana IMEI lama adalah ini
        $claimsAsOld = WarrantyClaim::with(['warranty'])
            ->whereHas('warranty', function ($q) use ($imei) {
                $q->where('serial_number', 'LIKE', '%' . $imei . '%');
            })->get();
            
        foreach ($claimsAsOld as $claim) {
            $timeline[] = [
                'date' => $claim->created_at,
                'type' => 'Klaim Garansi (Masuk)',
                'icon' => 'wrench-screwdriver',
                'color' => 'bg-orange-500',
                'description' => "Pelanggan mengajukan klaim garansi. Masalah: " . $claim->issue_description,
                'status' => $claim->status,
                'meta' => [
                    'Klaim ID' => 'CLM-' . $claim->id,
                    'Resolusi' => $claim->resolution ?: 'Belum selesai',
                    'Catatan' => $claim->resolution_notes
                ]
            ];
        }

        // Cari klaim dimana IMEI ini adalah IMEI Pengganti (lewat tabel WarrantyReplacement)
        $replacementsAsNew = \App\Models\WarrantyReplacement::with('claim')->where('new_imei', 'LIKE', '%' . $imei . '%')->get();
        foreach ($replacementsAsNew as $replacement) {
            $claim = $replacement->claim;
            if (!$claim) continue;
            
            $timeline[] = [
                'date' => $claim->resolved_at ?: $claim->updated_at,
                'type' => 'Klaim Garansi (Keluar sbg Pengganti)',
                'icon' => 'arrows-right-left',
                'color' => 'bg-purple-500',
                'description' => "Perangkat ini diberikan kepada pelanggan sebagai unit pengganti (Replacement).",
                'status' => 'Terjual (Replacement)',
                'meta' => [
                    'Klaim ID' => 'CLM-' . $claim->id,
                    'Catatan' => $claim->resolution_notes,
                    'IMEI Rusak Lama' => $replacement->old_imei
                ]
            ];
        }

        // Sorting timeline
        usort($timeline, function($a, $b) {
            return strtotime($a['date']) - strtotime($b['date']); // Ascending (Lama ke Baru)
        });

        // Tentukan Status Terakhir
        if (count($timeline) > 0) {
            $lastEvent = end($timeline);
            $latestStatus = $lastEvent['status'] ?? 'Unknown';
            if (in_array($lastEvent['type'], ['Penjualan (Sales)', 'Klaim Garansi (Keluar sbg Pengganti)'])) {
                $latestStatus = 'Ada di Tangan Pelanggan';
            } elseif ($lastEvent['type'] === 'Klaim Garansi (Masuk)') {
                $latestStatus = 'Di Service Center / Retur Gudang';
            } elseif ($lastEvent['type'] === 'Inspeksi Buyback' && strtolower($latestStatus) === 'completed') {
                $latestStatus = 'Ready Stock (Gudang)';
            }
        }

        $this->deviceHistory = array_reverse($timeline); // Reverse agar yang terbaru di atas
        $this->deviceInfo = [
            'model' => $deviceModel,
            'specs' => $deviceSpecs,
            'latest_status' => $latestStatus
        ];
        
        $this->searched = true;
    }

    public function render()
    {
        return view('livewire.zoffline.device-passport')->layout('layouts.z');
    }

    public function goBack()
    {
        return $this->redirect(route('zoffline.home'), navigate: true);
    }
}

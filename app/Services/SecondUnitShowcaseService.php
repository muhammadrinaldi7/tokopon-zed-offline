<?php

namespace App\Services;

use App\Models\DeviceInspection;
use App\Models\OnlineStockReservation;
use App\Models\ProductAccurate;
use App\Models\ProductSerialNumber;

class SecondUnitShowcaseService
{
    protected OnlineStockService $onlineStockService;

    public function __construct(OnlineStockService $onlineStockService)
    {
        $this->onlineStockService = $onlineStockService;
    }

    /**
     * Cek apakah produk termasuk unit bekas / GSK Second.
     */
    public function isSecondProduct(ProductAccurate $product): bool
    {
        $bu = $product->businessUnit;
        if ($bu) {
            if (strtoupper((string) $bu->mobile_category) === 'SECOND') {
                return true;
            }
            if (str_contains(strtoupper((string) $bu->name), 'SECOND') || strtoupper((string) $bu->code) === 'SECOND') {
                return true;
            }
        }

        if ((int) $product->business_unit_id === 2) {
            return true;
        }

        $upperName = strtoupper((string) ($product->name ?? ''));
        return str_contains($upperName, 'HP 2ND') || str_contains($upperName, 'SECOND');
    }

    /**
     * Ambil daftar unit fisik tersedia untuk produk GSK Second lengkap dengan
     * profil QC dari tabel `device_inspections` dan foto `qc_photos`.
     * IMEI disamarkan menjadi 4 digit terakhir demi keamanan publik.
     */
    public function getAvailableUnitsForProduct(ProductAccurate $product, ?int $warehouseId = null): array
    {
        if (!$product->has_sn) {
            return [];
        }

        $buId = $product->business_unit_id ? (int) $product->business_unit_id : null;
        $onlineWarehouseIds = $this->onlineStockService
            ->getOnlineWarehouses($buId, 'any')
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->all();

        if (empty($onlineWarehouseIds)) {
            return [];
        }

        // Ambil SN yang sedang terkunci di reservasi aktif agar tidak ditawarkan ke customer lain
        $reservedSnIds = OnlineStockReservation::query()
            ->active()
            ->where('product_accurate_id', $product->id)
            ->whereNotNull('product_serial_number_id')
            ->pluck('product_serial_number_id')
            ->map(fn($id) => (int) $id)
            ->all();

        $snQuery = ProductSerialNumber::with('warehouse')
            ->where('status', 'Available')
            ->where(function ($q) use ($product) {
                $q->where('product_accurate_id', $product->id);
                if (!empty($product->item_no)) {
                    $q->orWhere(function ($sub) use ($product) {
                        $sub->where('item_no', $product->item_no);
                        if ($product->business_unit_id) {
                            $sub->where('business_unit_id', $product->business_unit_id);
                        }
                    });
                }
            });

        if ($warehouseId) {
            if (!in_array($warehouseId, $onlineWarehouseIds, true)) {
                return [];
            }
            $snQuery->where('warehouse_id', $warehouseId);
        } else {
            $snQuery->whereIn('warehouse_id', $onlineWarehouseIds);
        }

        if (!empty($reservedSnIds)) {
            $snQuery->whereNotIn('id', $reservedSnIds);
        }

        $serialNumbers = $snQuery->orderBy('warehouse_id')->orderBy('id')->get();
        if ($serialNumbers->isEmpty()) {
            return [];
        }

        // Batch query DeviceInspection terbaru berdasarkan IMEI (1 query, tanpa N+1)
        $imeis = $serialNumbers->pluck('serial_number')->filter()->unique()->values()->all();
        $inspectionsByImei = DeviceInspection::with('media')
            ->whereIn('imei', $imeis)
            ->orderByDesc('inspected_at')
            ->orderByDesc('id')
            ->get()
            ->groupBy('imei')
            ->map(fn($group) => $group->first());

        return $serialNumbers->map(function (ProductSerialNumber $sn) use ($inspectionsByImei) {
            /** @var DeviceInspection|null $inspection */
            $inspection = $inspectionsByImei->get($sn->serial_number);
            $qcProfile = $this->formatInspectionForCustomer($inspection);

            return [
                'unit_id' => $sn->id,
                'unit_code' => 'UNIT-' . $sn->id,
                'masked_imei' => $this->maskImei((string) $sn->serial_number),
                'warehouse_id' => $sn->warehouse_id,
                'warehouse_name' => $sn->warehouse?->name ?? 'Gudang Online',
                'warehouse_city' => $sn->warehouse?->city,
                'allow_store_pickup' => (bool) ($sn->warehouse?->allow_store_pickup ?? false),
                'allow_online_delivery' => (bool) ($sn->warehouse?->allow_online_delivery ?? false),
                'qc_status' => $sn->qc_status,
                'receipt_date' => $sn->receipt_date,
                'has_qc_report' => $qcProfile['has_qc_report'],
                'battery_health' => $qcProfile['battery_health'],
                'qc_verdict' => $qcProfile['qc_verdict'],
                'qc_summary' => $qcProfile['qc_summary'],
                'passed_count' => $qcProfile['passed_count'],
                'failed_count' => $qcProfile['failed_count'],
                'total_items' => $qcProfile['total_items'],
                'noted_issues' => $qcProfile['noted_issues'],
                'inspector_notes' => $qcProfile['inspector_notes'],
                'inspected_at' => $qcProfile['inspected_at'],
                'qc_photos' => $qcProfile['qc_photos'],
            ];
        })->values()->all();
    }

    /**
     * Ekstrak profil QC ramah customer dari DeviceInspection.
     */
    public function formatInspectionForCustomer(?DeviceInspection $inspection): array
    {
        if (!$inspection) {
            return [
                'has_qc_report' => false,
                'battery_health' => null,
                'qc_verdict' => null,
                'qc_summary' => null,
                'passed_count' => 0,
                'failed_count' => 0,
                'total_items' => 0,
                'noted_issues' => [],
                'inspector_notes' => 'Siap dicekkan kondisi fisik, foto, & Battery Health oleh CS via Chat.',
                'inspected_at' => null,
                'qc_photos' => [],
            ];
        }

        $checklist = is_array($inspection->checklist_results) ? $inspection->checklist_results : [];
        $batteryHealth = null;
        $notedIssues = [];

        foreach ($checklist as $item) {
            if (!is_array($item)) {
                continue;
            }
            $name = trim((string) ($item['name'] ?? ''));
            $type = $item['type'] ?? 'boolean';
            $val = $item['value'] ?? null;

            if ($name !== '' && (str_contains(strtolower($name), 'battery') || str_contains(strtolower($name), 'baterai'))) {
                if ($val !== null && $val !== '' && !is_bool($val)) {
                    $strVal = trim((string) $val);
                    $batteryHealth = str_ends_with($strVal, '%') ? $strVal : ($strVal . '%');
                }
            }

            if ($type === 'boolean' && $val === false && $name !== '') {
                $notedIssues[] = $name;
            }
        }

        $photos = $inspection->getMedia('qc_photos')
            ->map(fn($media) => $media->getFullUrl())
            ->values()
            ->all();

        return [
            'has_qc_report' => true,
            'battery_health' => $batteryHealth,
            'qc_verdict' => $inspection->verdict,
            'qc_summary' => $inspection->summary_label,
            'passed_count' => (int) ($inspection->passed_count ?? 0),
            'failed_count' => (int) ($inspection->failed_count ?? 0),
            'total_items' => (int) ($inspection->total_items ?? 0),
            'noted_issues' => $notedIssues,
            'inspector_notes' => $inspection->inspector_notes,
            'inspected_at' => $inspection->inspected_at?->toIso8601String(),
            'qc_photos' => $photos,
        ];
    }

    /**
     * Samarkan IMEI menjadi 4 digit terakhir (contoh: ****8841).
     */
    public function maskImei(string $imei): string
    {
        $clean = trim($imei);
        if (strlen($clean) <= 4) {
            return '****' . $clean;
        }
        return '****' . substr($clean, -4);
    }
}

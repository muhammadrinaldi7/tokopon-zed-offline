<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\BuybackDevice;
use App\Models\BuybackTier;
use App\Models\ProductAccurate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BuybackSimulatorController extends Controller
{
    /**
     * Cari perangkat HP untuk simulasi taksiran buyback CS
     */
    public function searchDevices(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));

        $query = ProductAccurate::query()
            ->where(function ($query) {
                $query->whereNull('proyek')
                    ->orWhereRaw("UPPER(proyek) NOT LIKE '%ACCESSORIES%'");
            })
            ->where(function ($catQuery) {
                $catQuery->whereNull('categoryName')
                    ->orWhere(function ($c) {
                        $c->where('categoryName', 'not like', '%ANTI GORES%')
                          ->where('categoryName', 'not like', '%AKSESORIS%')
                          ->where('categoryName', 'not like', '%ACCESSORIES%')
                          ->where('categoryName', 'not like', '%SPAREPART%')
                          ->where('categoryName', 'not like', '%TEMPERED%')
                          ->where('categoryName', 'not like', '%CASE%');
                    });
            })
            ->where('name', 'not like', '%ANTI GORES%')
            ->where('name', 'not like', '%3D TPG%')
            ->where('name', 'not like', '%CASING%')
            ->where('name', 'not like', '%CASE %')
            ->where('name', 'not like', '%TEMPERED%')
            ->where('name', 'not like', '%CHARGER%')
            ->where('name', 'not like', '%KABEL%')
            ->where('name', 'not like', '%ADAPTOR%')
            ->where('name', 'not like', '%HEADSET%')
            ->where('name', 'not like', '%EARPHONE%')
            ->where('name', 'not like', '%TWS%')
            ->where(function ($pQuery) {
                $pQuery->where('buy_price', '>', 0)
                    ->orWhere('base_price', '>', 0);
            });

        if (!empty($q)) {
            $words = explode(' ', $q);
            $query->where(function ($sub) use ($words) {
                foreach ($words as $w) {
                    if (trim($w) !== '') {
                        $sub->where('name', 'like', '%' . trim($w) . '%');
                    }
                }
            });
        } else {
            // Jika kosong, prioritaskan HP Second / Handphone populer
            $query->where(function ($catQuery) {
                $catQuery->where('categoryName', 'like', '%Handphone%')
                    ->orWhere('categoryName', 'like', '%HP SECOND%')
                    ->orWhere('name', 'like', '%HP 2ND%');
            });
        }

        $devices = $query
            ->orderByRaw('CASE WHEN buy_price > 0 THEN 0 ELSE 1 END')
            ->orderByRaw('CASE WHEN business_unit_id = 2 THEN 0 ELSE 1 END')
            ->orderBy('name')
            ->limit(20)
            ->get();

        $results = $devices->map(function ($device) {
            $buyPrice = (float) ($device->buy_price > 0 ? $device->buy_price : $device->base_price);
            $isOfficialBuyPrice = (float) ($device->buy_price ?? 0) > 0;

            return [
                'id' => $device->id,
                'name' => $device->name,
                'item_no' => $device->item_no,
                'brand' => $device->brandName ?: '-',
                'category' => $device->categoryName ?: '-',
                'proyek' => $device->proyek ?: '-',
                'business_unit_id' => $device->business_unit_id,
                'buy_price' => $buyPrice,
                'is_official_buy_price' => $isOfficialBuyPrice,
                'formatted_price' => 'Rp ' . number_format($buyPrice, 0, ',', '.'),
            ];
        });

        return response()->json($results);
    }

    /**
     * Ambil detail perangkat beserta aturan minus kualifikasi (Tier Rules)
     */
    public function getDeviceRules(Request $request, int $id): JsonResponse
    {
        $device = ProductAccurate::findOrFail($id);

        $buyPrice = (float) ($device->buy_price > 0 ? $device->buy_price : $device->base_price);
        $isOfficialBuyPrice = (float) ($device->buy_price ?? 0) > 0;

        // Cari mapping tier berdasarkan hierarki BuybackDevice
        $buybackDevice = BuybackDevice::findByProductAccurate($device);
        $tier = $buybackDevice?->tier;

        // Jika tidak ada mapping spesifik, gunakan tier pertama atau default sebagai fallback
        if (!$tier) {
            $tier = BuybackTier::first();
        }

        $categories = [];
        $rawRules = $tier ? $tier->getRulesByCategory() : [];

        // Fallback aturan standar jika belum ada tier di database sama sekali
        if (empty($rawRules)) {
            $rawRules = [
                'Kondisi Fisik / Body' => [
                    'is_multiple' => false,
                    'items' => [
                        ['name' => 'Mulus 98-99% (Grade A)', 'type' => 'fixed', 'value' => 0, 'description' => 'Mulus prima tanpa cacat / lecet'],
                        ['name' => 'Lecet Pemakaian Halus (Grade B)', 'type' => 'fixed', 'value' => 150000, 'description' => 'Baret halus pemakaian wajar'],
                        ['name' => 'Dent / Penyok / Cat Mengelupas (Grade C)', 'type' => 'percentage', 'value' => 10, 'description' => 'Ada bekas jatuh atau dent di sudut'],
                    ]
                ],
                'Layar / LCD' => [
                    'is_multiple' => false,
                    'items' => [
                        ['name' => 'Layar Normal & Bersih', 'type' => 'fixed', 'value' => 0, 'description' => 'Layar responsif, no shadow/garis'],
                        ['name' => 'Shadow / Burn-in Ringan', 'type' => 'percentage', 'value' => 15, 'description' => 'Ada bayangan tipis di layar'],
                        ['name' => 'Kaca Retak (Touch Normal)', 'type' => 'percentage', 'value' => 25, 'description' => 'Kaca retak tapi layar sentuh masih responsif'],
                        ['name' => 'Layar Bergaris / Mati Sebagian', 'type' => 'percentage', 'value' => 45, 'description' => 'Perlu penggantian LCD total'],
                    ]
                ],
                'Fungsi & Baterai' => [
                    'is_multiple' => true,
                    'items' => [
                        ['name' => 'Battery Health < 80% (Perlu Service)', 'type' => 'fixed', 'value' => 200000, 'description' => 'Kapasitas baterai menurun drastis'],
                        ['name' => 'Face ID / Fingerprint Rusak', 'type' => 'percentage', 'value' => 20, 'description' => 'Fitur biometrik tidak dapat membaca'],
                        ['name' => 'Kamera Bintik / Getar', 'type' => 'fixed', 'value' => 300000, 'description' => 'Kamera tidak fokus atau terdapat bercak'],
                    ]
                ],
                'Kelengkapan Unit' => [
                    'is_multiple' => true,
                    'items' => [
                        ['name' => 'Batangan (Tanpa Box Original)', 'type' => 'fixed', 'value' => 150000, 'description' => 'Tidak menyertakan dusbook asli'],
                        ['name' => 'Tanpa Kabel & Kepala Charger', 'type' => 'fixed', 'value' => 100000, 'description' => 'Hanya membawa unit HP'],
                    ]
                ]
            ];
        }

        foreach ($rawRules as $catName => $data) {
            $isMultiple = $data['is_multiple'] ?? false;
            $items = [];
            foreach ($data['items'] ?? [] as $idx => $item) {
                $items[] = [
                    'key' => Str::slug($catName) . '_' . $idx,
                    'category' => $catName,
                    'name' => $item['name'] ?? '',
                    'type' => $item['type'] ?? 'fixed', // 'fixed' | 'percentage'
                    'value' => (float) ($item['value'] ?? 0),
                    'description' => $item['description'] ?? '',
                ];
            }

            $categories[] = [
                'category' => $catName,
                'is_multiple' => (bool) $isMultiple,
                'items' => $items,
            ];
        }

        return response()->json([
            'success' => true,
            'device' => [
                'id' => $device->id,
                'name' => $device->name,
                'brand' => $device->brandName ?: '-',
                'category' => $device->categoryName ?: '-',
                'proyek' => $device->proyek ?: '-',
                'base_price' => $buyPrice,
                'is_official_buy_price' => $isOfficialBuyPrice,
                'formatted_base_price' => 'Rp ' . number_format($buyPrice, 0, ',', '.'),
            ],
            'tier' => [
                'id' => $tier?->id ?? 0,
                'name' => $tier?->name ?? 'Standar Buyback',
            ],
            'categories' => $categories,
        ]);
    }
}

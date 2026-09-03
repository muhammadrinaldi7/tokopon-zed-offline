<?php

namespace App\Imports;

use App\Models\BuybackDevice;
use App\Models\BuybackTier;
use App\Models\ProductAccurate;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;

class BuybackDeviceImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // Headers akan ter-convert menjadi format snake_case otomatis oleh maatwebsite/excel
        // 'ID' -> 'id'
        // 'Tier Name' -> 'tier_name'
        // 'SKU Accurate' -> 'sku_accurate'
        // 'OS' -> 'os'
        // 'Kategori' -> 'kategori'
        // 'Brand' -> 'brand'
        // 'Base Price' -> 'base_price'
        // 'Is Active' -> 'is_active'

        $tierName = trim($row['tier_name'] ?? '');
        if (empty($tierName)) {
            Log::info("BuybackDeviceImport: Skipped row because Tier Name is empty.");
            return null; // Skip jika tier kosong
        }

        $tier = BuybackTier::where('name', $tierName)->first();
        if (!$tier) {
            Log::info("BuybackDeviceImport: Skipped row because Tier Name '{$tierName}' not found.");
            return null; // Skip jika tier tidak ada
        }

        $id = trim($row['id'] ?? '');
        $sku = trim($row['sku_accurate'] ?? '');
        $os = trim($row['os'] ?? '');
        $kategori = trim($row['kategori'] ?? '');
        $brand = trim($row['brand'] ?? '');
        $basePrice = (float) str_replace([',', '.', 'Rp', 'rp', ' '], '', trim($row['base_price'] ?? '0'));
        $isActive = trim($row['is_active'] ?? '1') == '1';

        $updateData = [
            'buyback_tier_id' => $tier->id,
            'base_price' => $basePrice,
            'is_active' => $isActive,
        ];

        // Jika ID ada, langsung update
        if (!empty($id)) {
            $device = BuybackDevice::find($id);
            if ($device) {
                $device->update($updateData);
                return $device;
            }
        }

        // Jika SKU ada, prioritas mapping berdasarkan SKU
        if (!empty($sku)) {
            $productAccurate = ProductAccurate::where('item_no', $sku)->first();
            if ($productAccurate) {
                return BuybackDevice::updateOrCreate(
                    [
                        'product_accurate_id' => $productAccurate->id,
                    ],
                    array_merge($updateData, [
                        'os_name' => null,
                        'category_name' => null,
                        'brand_name' => null,
                    ])
                );
            }
        }

        // Global mapping berdasarkan kombinasi OS, Kategori, Brand
        return BuybackDevice::updateOrCreate(
            [
                'product_accurate_id' => null,
                'os_name' => empty($os) ? null : $os,
                'category_name' => empty($kategori) ? null : $kategori,
                'brand_name' => empty($brand) ? null : $brand,
            ],
            $updateData
        );
    }
}

<?php

namespace App\Traits;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductAccurate;
use App\Models\SecondProduct;
use App\Models\SecondProductVariant;
use Illuminate\Support\Str;

trait GeneratesProductVariant
{
    /**
     * Parse the Accurate item name using Regex to extract Parent Name, RAM, Storage, and Color.
     */
    public function parseItemName($name)
    {
        // 1. Buang kata "HP " di depan jika ada
        $cleanName = preg_replace('/^HP\s+/i', '', trim($name));

        $parentName = $cleanName;
        $ram = null;
        $storage = null;
        $color = '-';

        // 2. Terapkan Regex pencari pola GB
        // Contoh: IPHONE 15 PLUS - 512GB - YELLOW
        if (preg_match('/^(.*?)\s*-?\s*(\d+(?:\/\d+)?\s*GB(?:\/TB)?)\s*-?\s*(.*)$/i', $cleanName, $matches)) {
            $parentName = trim($matches[1]);
            $memory = trim($matches[2]);
            $color = trim($matches[3]);

            // 3. Pisahkan RAM dan Storage jika ada garis miring (Misal: 4/128GB)
            if (strpos($memory, '/') !== false) {
                list($r, $s) = explode('/', str_ireplace(['GB', 'TB', ' '], '', $memory));
                $ram = trim($r) . 'GB';
                $storage = trim($s) . 'GB';
            } else {
                $storage = $memory; // Jika tanpa slash, masuk semua ke Storage
            }
        }

        return [
            'parentName' => $parentName,
            'ram' => $ram,
            'storage' => $storage,
            'color' => $color ?: '-',
        ];
    }

    /**
     * Deteksi kondisi HP (Second Inter / Second Resmi) dari nama item Accurate.
     * Keyword: "INTER" → Second Inter, "RESMI" / default → Second Resmi
     */
    public function parseConditionFromName(string $name): string
    {
        $upperName = strtoupper($name);

        if (str_starts_with($upperName, 'DS -') || str_contains($upperName, 'INTER')) {
            return 'Inter';
        }

        // Default: jika ada kata RESMI atau tidak ada keyword khusus atau RL
        return 'Resmi';
    }

    /**
     * Bersihkan parent name dari keyword kondisi (INTER, RESMI, IBOX, SECOND)
     * agar nama produk induk bersih dan bisa di-group.
     * Contoh: "IPHONE 15 PRO MAX INTER" → "IPHONE 15 PRO MAX"
     */
    public function cleanParentNameForSecond(string $parentName): string
    {
        // Remove DS - or RL - prefixes
        $cleaned = preg_replace('/^(DS|RL)\s*-\s*/i', '', $parentName);

        // Hapus keyword kondisi dari nama (case-insensitive)
        $cleaned = preg_replace('/\b(INTER|RESMI|IBOX|SECOND)\b/i', '', $cleaned);

        // Bersihkan spasi/dash ganda yang tersisa
        $cleaned = preg_replace('/\s*-\s*-\s*/', ' - ', $cleaned);
        $cleaned = preg_replace('/\s{2,}/', ' ', $cleaned);

        return trim($cleaned, ' -');
    }

    /**
     * Auto-generate Product and ProductVariant based on full Accurate Item Data
     */
    public function autoGenerateProductAndVariant($itemNo, $accurateItem, $productAccurateId = null)
    {
        // 1. Validasi Tipe (Opsional: Pastikan ini INVPART atau barang jualan)
        if (($accurateItem['itemTypeName'] ?? '') !== 'Persediaan') {
            return [
                'success' => false,
                'message' => 'Bukan Inventory Part (INVPART)'
            ];
        }

        // 2. Ambil Kategori dari Accurate (Atau default ke Uncategorized)
        $categoryName = $accurateItem['itemCategory']['name'] ?? 'Uncategorized';
        $category = Category::firstOrCreate(
            ['slug' => Str::slug($categoryName)],
            ['name' => $categoryName]
        );

        // BRAND
        $brandName = $accurateItem['itemBrand']['name'] ?? 'Uncategorized';
        $brand = Brand::firstOrCreate(
            ['slug' => Str::slug($brandName)],
            ['name' => $brandName]
        );

        // TODO: Ekstrak Brand dari Accurate jika ada fieldnya, atau ambil dari kata pertama Parent Name

        // 3. Pecah Nama (Regex)
        $parsedData = $this->parseItemName($accurateItem['name'] ?? '');

        // 4. Cari atau Buat Induk (Product)
        $product = Product::firstOrCreate(
            ['name' => $parsedData['parentName']],
            [
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'slug' => Str::slug($parsedData['parentName']),
                'is_active' => true,
                'description' => 'Auto-generated from Accurate',
                'has_active_accurate' => true
            ]
        );

        // 5. Buat atau Update Anak (ProductVariant)
        $variant = ProductVariant::updateOrCreate(
            ['sku' => $itemNo],
            [
                'product_id' => $product->id,
                'condition' => 'Baru', // Default
                'color' => $parsedData['color'],
                'ram' => $parsedData['ram'],
                'storage' => $parsedData['storage'],
                'price' => (float) ($accurateItem['unitPrice'] ?? 0),
                'product_accurate_id' => $productAccurateId,
            ]
        );

        return [
            'success' => true,
            'message' => 'Variant berhasil di-generate',
            'product' => $product,
            'variant' => $variant
        ];
    }

    /**
     * Auto-generate SecondProduct dan SecondProductVariant dari data Accurate GSK.
     * Digunakan saat admin klik "Generate Variant" di tab 'second'.
     */
    public function autoGenerateSecondProductAndVariant($itemNo, $accurateItem, $productAccurateId = null)
    {
        // 1. Validasi Tipe
        if (($accurateItem['itemTypeName'] ?? '') !== 'Persediaan') {
            return [
                'success' => false,
                'message' => 'Bukan Inventory Part (INVPART)'
            ];
        }

        $itemName = $accurateItem['name'] ?? '';

        // 2. Ambil Kategori dari Accurate
        $categoryName = $accurateItem['itemCategory']['name'] ?? 'Uncategorized';
        $category = Category::firstOrCreate(
            ['slug' => Str::slug($categoryName)],
            ['name' => $categoryName]
        );

        // 3. Ambil Brand dari Accurate
        $brandName = $accurateItem['itemBrand']['name'] ?? 'Uncategorized';
        $brand = Brand::firstOrCreate(
            ['slug' => Str::slug($brandName)],
            ['name' => $brandName]
        );

        // 4. Pecah Nama (Regex)
        $parsedData = $this->parseItemName($itemName);

        // 5. Deteksi kondisi (Second Inter / Second Resmi)
        $condition = $this->parseConditionFromName($itemName);

        // 6. Bersihkan nama parent dari keyword kondisi agar grouping produk rapi
        $cleanParentName = $this->cleanParentNameForSecond($parsedData['parentName']);

        // 7. Cari atau Buat Induk (SecondProduct)
        $product = SecondProduct::firstOrCreate(
            ['name' => $cleanParentName],
            [
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'slug' => Str::slug($cleanParentName),
                'is_active' => true,
                'description' => 'Auto-generated from Accurate GSK',
                'has_active_accurate' => true,
            ]
        );

        // 8. Buat atau Update Anak (SecondProductVariant)
        $variant = SecondProductVariant::updateOrCreate(
            ['sku' => $itemNo],
            [
                'second_product_id' => $product->id,
                'condition_desc' => $condition, // Ubah ke kolom condition yang benar
                'color' => $parsedData['color'],
                'ram' => $parsedData['ram'],
                'storage' => $parsedData['storage'],
                'price' => (float) ($accurateItem['unitPrice'] ?? 0),
                'buy_price' => (float) ($accurateItem['balanceUnitCost'] ?? 0),
                'product_accurate_id' => $productAccurateId,
                'has_sn' => true, // HP bekas selalu pakai SN/IMEI
            ]
        );

        return [
            'success' => true,
            'message' => "Variant (SecondProduct) berhasil di-generate — Kondisi: {$condition}",
            'product' => $product,
            'variant' => $variant,
            'condition' => $condition,
        ];
    }
}

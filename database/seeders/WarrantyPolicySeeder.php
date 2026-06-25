<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WarrantyPolicy;
use App\Models\Brand;

class WarrantyPolicySeeder extends Seeder
{
    public function run(): void
    {
        // Bersihkan data lama jika ingin reset (Opsional, tapi aman untuk testing)
        // WarrantyPolicy::truncate();

        $apple = Brand::where('name', 'Apple')->first();
        $samsung = Brand::where('name', 'Samsung')->first();

        // 1. GARANSI DEFAULT TOKO (BERLAKU GLOBAL JIKA BRAND TIDAK DITEMUKAN)
        WarrantyPolicy::updateOrCreate(
            ['name' => 'Garansi Resmi Toko (Global)'],
            [
                'type' => 'store_default',
                'brand_id' => null,
                'duration_days' => 365,
                'max_claims' => 3,
                'is_active' => true,
                'coverage' => [
                    ['name' => 'Kerusakan Mesin (Pabrikan)', 'covered' => '1'],
                    ['name' => 'Baterai Drop (< 80%)', 'covered' => '1'],
                    ['name' => 'Kerusakan LCD (Dead Pixel pabrikan)', 'covered' => '1'],
                    ['name' => 'Kamera Buram/Tidak Fokus', 'covered' => '1'],
                    ['name' => 'Layar Pecah / Retak (Human Error)', 'covered' => '0'],
                    ['name' => 'Masuk Air (Water Damage)', 'covered' => '0'],
                    ['name' => 'Body Penyok / Baret', 'covered' => '0'],
                ],
            ]
        );

        // 2. GARANSI KHUSUS APPLE (Jika ada)
        if ($apple) {
            WarrantyPolicy::updateOrCreate(
                ['name' => 'Garansi Resmi Apple 1 Tahun'],
                [
                    'type' => 'store_default',
                    'brand_id' => $apple->id,
                    'duration_days' => 365,
                    'max_claims' => 3,
                    'is_active' => true,
                    'coverage' => [
                        ['name' => 'Logic Board Mati', 'covered' => '1'],
                        ['name' => 'Face ID / Touch ID Mati', 'covered' => '1'],
                        ['name' => 'Baterai Service (< 80% dalam 1 thn)', 'covered' => '1'],
                        ['name' => 'Layar Bergaris (Pabrikan)', 'covered' => '1'],
                        ['name' => 'Kaca Belakang Pecah', 'covered' => '0'],
                        ['name' => 'LCD Retak / Jatuh', 'covered' => '0'],
                    ],
                ]
            );
        }

        // 3. ASURANSI: PROTEKSI LAYAR PECAH
        WarrantyPolicy::updateOrCreate(
            ['name' => 'Z-Protect: Layar Pecah (Add-On)'],
            [
                'type' => 'insurance',
                'brand_id' => null, // Berlaku untuk semua brand jika dibeli
                'duration_days' => 180, // 6 Bulan
                'max_claims' => 1, // Maksimal klaim ganti layar 1 kali
                'item_category' => 'Asuransi', // Nama Kategori di Accurate / POS
                'is_active' => true,
                'coverage' => [
                    ['name' => 'Layar Retak Rambut', 'covered' => '1'],
                    ['name' => 'Layar Pecah Parah', 'covered' => '1'],
                    ['name' => 'LCD Blackout Akibat Jatuh', 'covered' => '1'],
                    ['name' => 'Kerusakan Mesin', 'covered' => '0'], // Mesin tidak tercover asuransi ini
                    ['name' => 'Kecurian', 'covered' => '0'],
                ],
            ]
        );

        // 4. ASURANSI: TOTAL COVERAGE (Z-CARE+)
        WarrantyPolicy::updateOrCreate(
            ['name' => 'Z-Care+ (Total Protection)'],
            [
                'type' => 'insurance',
                'brand_id' => null,
                'duration_days' => 365,
                'max_claims' => 2,
                'item_category' => 'Asuransi',
                'is_active' => true,
                'coverage' => [
                    ['name' => 'Layar Pecah', 'covered' => '1'],
                    ['name' => 'Masuk Air (Water Damage)', 'covered' => '1'],
                    ['name' => 'Konslet / Mati Total', 'covered' => '1'],
                    ['name' => 'Ganti Baterai Gratis', 'covered' => '1'],
                    ['name' => 'Kaca Kamera Pecah', 'covered' => '1'],
                    ['name' => 'Kehilangan / Kecurian', 'covered' => '0'],
                ],
            ]
        );
    }
}

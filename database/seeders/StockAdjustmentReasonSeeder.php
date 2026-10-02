<?php

namespace Database\Seeders;

use App\Models\BusinessUnit;
use App\Models\StockAdjustmentReason;
use Illuminate\Database\Seeder;

class StockAdjustmentReasonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $baseReasons = [
            [
                'code' => 'PEMELIHARAAN_INVENTARIS',
                'name' => 'Pemeliharaan Inventaris / Unit Display',
                'accurate_account_no' => '50.01.003',
                'accurate_account_name' => 'Beban Pemeliharaan & Inventaris',
                'adjustment_type' => 'OUT',
                'description' => 'Pemakaian unit untuk display toko atau pemeliharaan inventaris operasional',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'SAMPLE_PROMOSI',
                'name' => 'Sample / Display Promosi Toko',
                'accurate_account_no' => '09.09.09',
                'accurate_account_name' => 'Beban Promosi & Pemasaran',
                'adjustment_type' => 'OUT',
                'description' => 'Pemakaian unit untuk kebutuhan promosi, event, atau demo toko',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'code' => 'BARANG_RUSAK_DEFECT',
                'name' => 'Barang Rusak / Defect / Cacat Pabrik',
                'accurate_account_no' => '50.03.001',
                'accurate_account_name' => 'Beban Kerugian Barang Rusak / Cacat',
                'adjustment_type' => 'OUT',
                'description' => 'Pengurangan stok akibat barang mengalami kerusakan fisik atau cacat pabrik',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'code' => 'SELISIH_OPNAME',
                'name' => 'Selisih Hasil Stock Opname',
                'accurate_account_no' => '50.03.002',
                'accurate_account_name' => 'Selisih Stock Opname',
                'adjustment_type' => 'OUT',
                'description' => 'Penyesuaian pengurangan karena selisih hasil fisik saat stock opname',
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'code' => 'KOREKSI_STOK',
                'name' => 'Koreksi Administrasi / Salah Input',
                'accurate_account_no' => '50.03.005',
                'accurate_account_name' => 'Beban Penyesuaian Persediaan',
                'adjustment_type' => 'OUT',
                'description' => 'Penyesuaian koreksi kesalahan input sistem atau administrasi',
                'is_active' => true,
                'sort_order' => 5,
            ],
            [
                'code' => 'LAINNYA',
                'name' => 'Lain-lain',
                'accurate_account_no' => '50.03.005',
                'accurate_account_name' => 'Beban Penyesuaian Persediaan Lainnya',
                'adjustment_type' => 'OUT',
                'description' => 'Alasan penyesuaian stok lainnya',
                'is_active' => true,
                'sort_order' => 6,
            ],
        ];

        $businessUnits = BusinessUnit::all();

        // 1. Seed per Business Unit spesifik
        foreach ($businessUnits as $bu) {
            foreach ($baseReasons as $data) {
                StockAdjustmentReason::updateOrCreate(
                    [
                        'business_unit_id' => $bu->id,
                        'code' => $data['code'],
                    ],
                    array_merge($data, ['business_unit_id' => $bu->id])
                );
            }
        }

        // 2. Seed untuk Global fallback (business_unit_id = null)
        foreach ($baseReasons as $data) {
            StockAdjustmentReason::updateOrCreate(
                [
                    'business_unit_id' => null,
                    'code' => $data['code'],
                ],
                array_merge($data, ['business_unit_id' => null])
            );
        }
    }
}

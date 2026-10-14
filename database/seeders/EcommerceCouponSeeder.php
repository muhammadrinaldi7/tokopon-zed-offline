<?php

namespace Database\Seeders;

use App\Models\EcommerceCoupon;
use Illuminate\Database\Seeder;

class EcommerceCouponSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $coupons = [
            [
                'code' => 'HEMAT20',
                'title' => 'Potongan Langsung 20 Ribu',
                'description' => 'Diskon Rp 20.000 untuk minimal belanja Rp 100.000 di Tokopon Mobile.',
                'type' => EcommerceCoupon::TYPE_PRODUCT_FIXED,
                'discount_value' => 20000,
                'max_discount_amount' => null,
                'min_purchase_amount' => 100000,
                'usage_limit_total' => 100,
                'usage_limit_per_user' => 1,
                'used_count' => 0,
                'start_date' => now(),
                'end_date' => now()->addMonths(3),
                'is_active' => true,
            ],
            [
                'code' => 'DISKON10',
                'title' => 'Diskon 10% Spesial Tokopon',
                'description' => 'Diskon belanja 10% maksimal potongan Rp 50.000 minimal belanja Rp 150.000.',
                'type' => EcommerceCoupon::TYPE_PRODUCT_PERCENT,
                'discount_value' => 10,
                'max_discount_amount' => 50000,
                'min_purchase_amount' => 150000,
                'usage_limit_total' => 200,
                'usage_limit_per_user' => 2,
                'used_count' => 0,
                'start_date' => now(),
                'end_date' => now()->addMonths(3),
                'is_active' => true,
            ],
            [
                'code' => 'GRATISONGKIR',
                'title' => 'Voucher Potongan Ongkos Kirim',
                'description' => 'Potongan ongkos kirim hingga Rp 15.000 tanpa minimal belanja tinggi.',
                'type' => EcommerceCoupon::TYPE_SHIPPING_FIXED,
                'discount_value' => 15000,
                'max_discount_amount' => null,
                'min_purchase_amount' => 50000,
                'usage_limit_total' => 500,
                'usage_limit_per_user' => 3,
                'used_count' => 0,
                'start_date' => now(),
                'end_date' => now()->addMonths(6),
                'is_active' => true,
            ],
            [
                'code' => 'SUPERDEAL50',
                'title' => 'Super Deal Belanja 50 Ribu',
                'description' => 'Diskon besar Rp 50.000 untuk transaksi gadget & aksesori minimal Rp 500.000.',
                'type' => EcommerceCoupon::TYPE_PRODUCT_FIXED,
                'discount_value' => 50000,
                'max_discount_amount' => null,
                'min_purchase_amount' => 500000,
                'usage_limit_total' => 50,
                'usage_limit_per_user' => 1,
                'used_count' => 0,
                'start_date' => now(),
                'end_date' => now()->addMonths(2),
                'is_active' => true,
            ],
        ];

        foreach ($coupons as $data) {
            EcommerceCoupon::updateOrCreate(
                ['code' => $data['code']],
                $data
            );
        }
    }
}

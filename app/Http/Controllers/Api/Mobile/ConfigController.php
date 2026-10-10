<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConfigController extends Controller
{
    /**
     * Mengambil konfigurasi remote, status fitur dinamis (logistik & pembayaran), dan kontak toko.
     */
    public function index(Request $request): JsonResponse
    {
        $dynamicShipping = filter_var(Setting::where('key', 'ecommerce_dynamic_shipping')->value('value') ?? false, FILTER_VALIDATE_BOOLEAN);
        $instantPayment = filter_var(Setting::where('key', 'ecommerce_instant_payment')->value('value') ?? false, FILTER_VALIDATE_BOOLEAN);
        $csPhone = Setting::where('key', 'ecommerce_cs_phone')->value('value') ?? '081298765432';

        return response()->json([
            'success' => true,
            'data' => [
                'app_name' => 'Tokopon Online Store',
                'app_version' => '1.0.0',
                'features' => [
                    // Toggle Logistik: false = Free/Pickup, true = Hitung Kurir Ekspedisi Otomatis
                    'dynamic_shipping_enabled' => $dynamicShipping,
                    // Toggle Pembayaran Instan: false = Manual Transfer Saja, true = Xendit QRIS/VA Otomatis
                    'instant_payment_enabled' => $instantPayment,
                    'manual_transfer_enabled' => true,
                    'guest_checkout_enabled' => true,
                    'trade_in_enabled' => true,
                ],
                'shipping' => [
                    'active_mode' => $dynamicShipping ? 'DYNAMIC_COURIER' : 'MANUAL_OR_PICKUP',
                    'options' => [
                        [
                            'code' => 'STORE_PICKUP',
                            'name' => 'Ambil di Toko (Store Pickup)',
                            'description' => 'Ambil langsung barang Anda di cabang Tokopon terdekat.',
                            'cost' => 0,
                            'formatted_cost' => 'Gratis',
                            'is_default' => true,
                        ],
                        [
                            'code' => 'INTERNAL_DELIVERY',
                            'name' => 'Pengiriman Kurir Toko (Free Ongkir)',
                            'description' => 'Diantar langsung oleh armada Tokopon khusus area dalam kota.',
                            'cost' => 0,
                            'formatted_cost' => 'Gratis',
                            'is_default' => false,
                        ],
                    ],
                    'default_shipping_cost' => 0,
                ],
                'payment' => [
                    'expiry_minutes' => 60,
                    'instructions' => 'Silakan transfer tepat sesuai total nominal dan unggah foto struk bukti bayar Anda.',
                ],
                'support' => [
                    'whatsapp' => $csPhone,
                    'operating_hours' => 'Setiap Hari, 09.00 - 21.00 WIB',
                ],
            ]
        ]);
    }
}

<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ApprovalRequest;
use App\Models\SellPhone;
use App\Models\User;
use Illuminate\Support\Facades\DB;

echo "=== TEST SELLPHONE PRICE EDIT ON FINAL APPROVAL STEP ===\n";

DB::beginTransaction();
try {
    $user = User::first();

    // Buat data SellPhone dummy
    $sellPhone = SellPhone::create([
        'user_id' => $user->id,
        'product_accurate_id' => null,
        'phone_brand' => 'Apple',
        'phone_model' => 'iPhone 13 128GB',
        'imei' => 'TEST-IMEI-123456',
        'appraised_value' => 7000000,
        'original_appraised_value' => 7000000,
        'is_price_adjusted' => false,
        'status' => 'PENDING_APPROVAL',
        'business_unit_id' => 2,
    ]);

    echo "1. SellPhone dummy dibuat: Harga Awal = Rp " . number_format($sellPhone->appraised_value, 0, ',', '.') . "\n";

    // Buat approval request
    $request = ApprovalRequest::create([
        'approvable_type' => SellPhone::class,
        'approvable_id' => $sellPhone->id,
        'request_type' => 'SELL_PHONE_APPROVAL',
        'requested_by' => $user->id,
        'business_unit_id' => 2,
        'status' => 'PENDING',
        'required_level' => 1,
        'current_level' => 0,
    ]);

    // Simulasi Persetujuan Akhir oleh Manager dengan Ubah Harga (Nego ke 6.500.000)
    $adjustedPrice = 6500000;
    $reason = "Kondisi baterai drop 75%";

    $request->status = 'APPROVED';
    $request->save();

    $request->executeAction([
        'adjusted_price' => $adjustedPrice,
        'price_adjusted_by' => $user->id,
        'price_adjustment_reason' => $reason,
    ]);

    $sellPhone->refresh();

    echo "2. Hasil Eksekusi Approval:\n";
    echo "   - Status SellPhone: {$sellPhone->status} (Harus PAYING)\n";
    echo "   - Harga Final (appraised_value): Rp " . number_format($sellPhone->appraised_value, 0, ',', '.') . " (Harus 6.500.000)\n";
    echo "   - is_price_adjusted: " . ($sellPhone->is_price_adjusted ? 'TRUE' : 'FALSE') . " (Harus TRUE)\n";
    echo "   - price_adjustment_reason: '{$sellPhone->price_adjustment_reason}'\n";
    echo "   - Status Request: {$request->status} (Harus COMPLETED)\n";

    if ($sellPhone->status === 'PAYING' && $sellPhone->appraised_value == 6500000 && $sellPhone->is_price_adjusted) {
        echo "\n[SUCCESS] Fitur ubah harga approval SellPhone 100% BERFUNGSI SEMPURNA!\n";
    } else {
        echo "\n[FAIL] Nilai tidak sesuai ekspektasi.\n";
    }

    DB::rollBack();
} catch (Exception $e) {
    DB::rollBack();
    echo "[ERROR] " . $e->getMessage() . "\n";
}

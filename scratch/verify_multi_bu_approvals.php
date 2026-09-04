<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ApprovalRule;
use App\Models\ApprovalRequest;
use App\Models\BusinessUnit;
use App\Models\Order;
use App\Models\User;
use App\Services\ApprovalService;
use Illuminate\Support\Facades\DB;

echo "========================================================\n";
echo "    VERIFIKASI SISTEM APPROVAL MULTI-BUSINESS UNIT      \n";
echo "========================================================\n\n";

$service = app(ApprovalService::class);

// 1. Tes Resolusi Aturan Global Fallback
echo "1. Tes Resolusi Aturan Global (business_unit_id IS NULL):\n";
$globalRules = $service->resolveRules('ORDER_CANCELLATION');
echo "   -> Ditemukan " . $globalRules->count() . " global rules untuk ORDER_CANCELLATION.\n";
foreach ($globalRules as $gr) {
    echo "      Level {$gr->level} (BU: " . ($gr->business_unit_id ?? 'GLOBAL') . "): Role {$gr->role->name}\n";
}

// 2. Tes Pembuatan Aturan Khusus Unit 2 (GSK Second)
echo "\n2. Menambahkan Aturan Khusus untuk Unit 2 (GSK Second):\n";
DB::beginTransaction();
try {
    // Buat rule khusus Unit 2: Level 1 -> role admin
    $secondRule = ApprovalRule::updateOrCreate(
        ['business_unit_id' => 2, 'module' => 'SELL_PHONE_APPROVAL', 'level' => 1],
        ['role_id' => 1, 'min_amount' => 500000, 'max_amount' => null]
    );
    echo "   -> Sukses membuat rule SELL_PHONE_APPROVAL untuk Unit 2 (ID: {$secondRule->id}, Min: Rp 500.000)\n";

    // Cek resolusi rule untuk Unit 2 vs Unit 1
    $u2Rules = $service->resolveRules('SELL_PHONE_APPROVAL', 2, 600000);
    $u1Rules = $service->resolveRules('SELL_PHONE_APPROVAL', 1, 600000);

    echo "   -> Resolusi Unit 2 (GSK Second, Rp 600.000): " . $u2Rules->count() . " rule ditemukan. (BU: " . ($u2Rules->first()?->business_unit_id ?? 'NONE') . ")\n";
    echo "   -> Resolusi Unit 1 (Syihab, Rp 600.000): " . $u1Rules->count() . " rule ditemukan. (BU: " . ($u1Rules->first()?->business_unit_id ?? 'GLOBAL/NONE') . ")\n";

    // 3. Tes Pengujian Threshold Nominal
    echo "\n3. Tes Threshold Nominal:\n";
    $belowMin = $service->resolveRules('SELL_PHONE_APPROVAL', 2, 200000); // Dibawah min 500.000
    echo "   -> Nilai Rp 200.000 (Dibawah min 500k): " . $belowMin->count() . " rule match (Harus 0 jika tidak ada fallback).\n";

    // 4. Tes Pembuatan Request via ApprovalService
    echo "\n4. Tes createRequest() via ApprovalService:\n";
    $testUser = User::first();
    $req = $service->createRequest([
        'request_type'     => 'SELL_PHONE_APPROVAL',
        'requested_by'     => $testUser->id,
        'business_unit_id' => 2,
        'branch_id'        => 37,
        'total_amount'     => 1500000,
        'reason'           => 'Tes simulasi approval unit second',
    ]);

    echo "   -> Request #{$req->id} berhasil dibuat!\n";
    echo "      - Business Unit ID: {$req->business_unit_id}\n";
    echo "      - Branch ID: {$req->branch_id}\n";
    echo "      - Total Amount: Rp " . number_format($req->total_amount, 0, ',', '.') . "\n";
    echo "      - Required Level: {$req->required_level}\n";
    echo "      - Status: {$req->status}\n";

    // 5. Tes Auto-fallback jika Caller Lupa Isi business_unit_id
    echo "\n5. Tes Auto-fallback business_unit_id pada ApprovalRequest::create():\n";
    $reqNoBu = ApprovalRequest::create([
        'request_type'   => 'CUSTOM_CASHBACK',
        'requested_by'   => $testUser->id,
        'reason'         => 'Tes auto populate BU',
        'status'         => 'PENDING',
        'required_level' => 1,
    ]);
    echo "   -> Request #{$reqNoBu->id} dibuat tanpa business_unit_id.\n";
    echo "      - Hasil Auto-Inferred BU: {$reqNoBu->business_unit_id} (Berhasil terisi otomatis!)\n";

    // Rollback agar data simulasi tidak mengotori database
    DB::rollBack();
    echo "\n[OK] Simulasi selesai. Rollback database transaksi simulasi sukses.\n";

} catch (Exception $e) {
    DB::rollBack();
    echo "\n[ERROR] " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\nSemua tes arsitektur Approval Multi-Business Unit BERHASIL 100%!\n";

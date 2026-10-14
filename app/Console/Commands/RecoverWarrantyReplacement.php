<?php

namespace App\Console\Commands;

use App\Models\ApprovalRequest;
use App\Models\Employe;
use App\Models\Order;
use App\Models\OrderAccurateDoc;
use App\Models\OrderItem;
use App\Models\ProductSerialNumber;
use App\Models\ProductVariant;
use App\Models\SecondProductVariant;
use App\Models\Warranty;
use App\Models\WarrantyClaim;
use App\Models\WarrantyReplacement;
use App\Models\WarrantySerialLog;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecoverWarrantyReplacement extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'warranty:recover-replacement 
                            {approval_id : ID dari ApprovalRequest}
                            {--sr= : Nomor Sales Return dari Accurate (contoh: SRT.2026.10.00017)}
                            {--receipt= : Nomor Sales Receipt dari Accurate}
                            {--receipt-id= : ID Sales Receipt dari Accurate (opsional jika nomor belum diketahui)}
                            {--invoice= : Nomor Sales Invoice dari Accurate (contoh: GSK.SI.2026.10.00845)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Memulihkan data transaksi klaim ganti unit yang ter-rollback di POS akibat deadlock atau error DB setelah Accurate sukses';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $approvalId = (int) $this->argument('approval_id');
        $srDocNumber = $this->option('sr');
        $receiptDocNumber = $this->option('receipt');
        $receiptId = $this->option('receipt-id');
        $invoiceDocNumber = $this->option('invoice');

        $this->info("=== MEMULAI RECOVERY KLAIM GANTI UNIT (APPROVAL #{$approvalId}) ===");

        $request = ApprovalRequest::find($approvalId);
        if (!$request) {
            $this->error("ApprovalRequest #{$approvalId} tidak ditemukan!");
            return Command::FAILURE;
        }

        $claim = $request->approvable;
        if (!$claim || !($claim instanceof WarrantyClaim)) {
            $this->error("WarrantyClaim terkait ApprovalRequest #{$approvalId} tidak ditemukan!");
            return Command::FAILURE;
        }

        $warranty = $claim->warranty;
        if (!$warranty) {
            $this->error("Garansi terkait Klaim #{$claim->claim_number} tidak ditemukan!");
            return Command::FAILURE;
        }

        $warranty->load(['orderItem.order', 'orderItem.variant', 'policy']);

        $payload = $request->payload ?? [];
        $replacement_imei = $payload['replacement_imei'] ?? null;
        $replacement_type = $payload['replacement_type'] ?? 'same';
        $replacement_item_no = $payload['replacement_item_no'] ?? null;
        $replacement_price = (float) ($payload['replacement_price'] ?? 0);
        $original_price = (float) ($payload['original_price'] ?? 0);
        $selected_sales_id = $payload['selected_sales_id'] ?? null;
        $replacement_product_name = $payload['replacement_product_name'] ?? null;
        $branch_id = $request->branch_id ?? ($payload['branch_id'] ?? null);
        $branch_name = $payload['branch_name'] ?? 'Toko';
        $business_unit_id = $request->business_unit_id ?? ($payload['business_unit_id'] ?? 1);

        $oldSn = $claim->serial_number ?: $warranty->serial_number;
        $newSn = $replacement_imei;

        if (empty($newSn)) {
            $this->error("IMEI Baru (replacement_imei) tidak ditemukan dalam payload approval!");
            return Command::FAILURE;
        }

        // Auto-lookup Sales Receipt dari Accurate jika receipt-id diberikan tetapi receipt doc number belum ada
        if (empty($receiptDocNumber) && !empty($receiptId)) {
            try {
                $businessUnitCode = $claim->warranty->policy?->businessUnit?->code ?? 'syihab';
                $accurateService = app(\App\Services\AccurateService::class);
                $receiptDetail = $accurateService->getDetailSalesReceipt($receiptId, $businessUnitCode);
                if ($receiptDetail && !empty($receiptDetail['number'])) {
                    $receiptDocNumber = $receiptDetail['number'];
                    $this->info("Berhasil mengambil nomor Sales Receipt dari Accurate: {$receiptDocNumber}");
                }
            } catch (\Exception $e) {
                $this->warn("Tidak dapat mengambil detail Sales Receipt dari Accurate (ID {$receiptId}): " . $e->getMessage());
            }
        }

        $this->table(['Informasi', 'Nilai'], [
            ['Approval ID', $approvalId],
            ['Claim Number', $claim->claim_number],
            ['Warranty ID', $warranty->id],
            ['IMEI Lama', $oldSn],
            ['IMEI Baru', $newSn],
            ['Sales Return Doc', $srDocNumber ?: '-'],
            ['Sales Invoice Doc', $invoiceDocNumber ?: '-'],
            ['Sales Receipt Doc', $receiptDocNumber ?: ($receiptId ? "ID {$receiptId} (Gagal lookup)" : '-')],
        ]);

        $originalPrice = $original_price > 0 ? $original_price : (float) ($warranty->orderItem?->price_at_checkout ?? 0);
        $newPrice = $replacement_type === 'different' ? $replacement_price : $originalPrice;

        $userId = $claim->customer_user_id
            ?? $warranty->customer_user_id
            ?? $warranty->orderItem?->order?->user_id
            ?? $request->requested_by
            ?? 1;

        DB::beginTransaction();
        try {
            // 1. Update data Garansi
            $warranty->serial_number = $newSn;
            if (!$warranty->original_serial_number) {
                $warranty->original_serial_number = $oldSn;
            }
            $warranty->claims_used = ($warranty->claims_used ?? 0) + 1;
            $warranty->replacement_count = ($warranty->replacement_count ?? 0) + 1;
            $warranty->device_inspection_id = null;
            $warranty->status = 'active';

            $policy = $warranty->policy;
            if ($policy && $policy->replacement_type === 'reset') {
                $warranty->activated_at = Carbon::now();
                $warranty->expires_at = Carbon::now()->addDays($policy->duration_days);
            }
            $warranty->save();
            $this->info("[1/7] Garansi #{$warranty->id} berhasil di-update ke IMEI Baru: {$newSn}");

            // 2. Update status WarrantyClaim
            $claim->status = 'completed';
            $claim->resolution = 'replaced';
            $claim->resolution_type = $replacement_type === 'same' ? 'replacement_same' : 'replacement_different';
            $claim->resolved_at = Carbon::now();
            $claim->replacement_item_no = $replacement_item_no;
            $claim->replacement_product_name = $replacement_product_name;
            $claim->save();
            $this->info("[2/7] Klaim #{$claim->claim_number} berhasil diperbarui menjadi COMPLETED");

            // 3. Catat Serial Log & WarrantyReplacement
            WarrantySerialLog::firstOrCreate([
                'warranty_id' => $warranty->id,
                'new_serial_number' => $newSn,
            ], [
                'warranty_claim_id' => $claim->id,
                'old_serial_number' => $oldSn,
                'reason' => $replacement_type === 'same' ? 'replacement_same' : 'replacement_upgrade',
                'changed_by' => $request->requested_by,
                'notes' => "Recovery Ganti Unit Klaim #{$claim->claim_number}",
            ]);

            WarrantyReplacement::firstOrCreate([
                'warranty_claim_id' => $claim->id,
                'new_imei' => $newSn,
            ], [
                'old_imei' => $oldSn,
                'processed_by' => $request->requested_by,
                'system_notes' => "Recovery Ganti Unit Klaim #{$claim->claim_number}",
            ]);
            $this->info("[3/7] Log perpindahan IMEI berhasil dicatat di riwayat garansi");

            // 4. Periksa dan Tautkan Order Retur (RET-ACC atau RET-CLM)
            $retOrder = null;
            if ($srDocNumber) {
                $retOrder = Order::where('accurate_invoice_no', $srDocNumber)
                    ->orWhere('order_number', 'RET-ACC-' . $srDocNumber)
                    ->orWhere('order_number', 'RET-' . $claim->claim_number)
                    ->first();
            }

            if ($retOrder) {
                $this->info("[4/7] Order Retur eksisting ditemukan: {$retOrder->order_number}");
                $retOrder->notes = "[Retur Klaim Garansi #{$claim->claim_number}] " . $retOrder->notes;
                $retOrder->save();

                if ($srDocNumber) {
                    OrderAccurateDoc::firstOrCreate([
                        'order_id' => $retOrder->id,
                        'doc_type' => 'SALES_RETURN',
                        'doc_number' => $srDocNumber,
                    ], [
                        'status' => 'SUCCESS',
                    ]);
                }
            } else {
                $retOrderNumber = 'RET-' . $claim->claim_number;
                $retOrder = Order::create([
                    'business_unit_id' => $warranty->policy?->business_unit_id ?? $business_unit_id,
                    'user_id' => $userId,
                    'order_number' => $retOrderNumber,
                    'accurate_invoice_no' => $srDocNumber,
                    'order_date' => Carbon::now()->format('Y-m-d'),
                    'total_amount' => -$originalPrice,
                    'shipping_cost' => 0,
                    'discount_amount' => 0,
                    'mdr_percentage' => 0,
                    'mdr_amount' => 0,
                    'grand_total' => -$originalPrice,
                    'order_status' => 'COMPLETED',
                    'order_channel' => 'POS',
                    'handled_by' => $request->requested_by,
                    'sales_id' => $warranty->orderItem?->order?->sales_id ?? $request->requested_by,
                    'shipping_address_snapshot' => ['type' => 'POS', 'store' => $branch_name, 'is_warranty_return' => true],
                    'notes' => "Pengembalian Unit Retur Garansi #{$claim->claim_number}. IMEI Lama: {$oldSn}",
                    'branch_id' => $branch_id,
                ]);

                if ($warranty->orderItem) {
                    OrderItem::create([
                        'order_id' => $retOrder->id,
                        'product_id' => $warranty->orderItem->product_id,
                        'product_variant_type' => $warranty->orderItem->product_variant_type,
                        'product_variant_id' => $warranty->orderItem->product_variant_id,
                        'product_name' => $warranty->orderItem->product_name,
                        'serial_number' => $oldSn,
                        'qty' => -1,
                        'price_at_checkout' => $originalPrice,
                        'vendor_name_snapshot' => $warranty->orderItem->vendor_name_snapshot,
                        'discount_amount' => 0,
                        'promo_discount_amount' => 0,
                        'subtotal' => -$originalPrice,
                    ]);
                }

                if ($srDocNumber) {
                    OrderAccurateDoc::firstOrCreate([
                        'order_id' => $retOrder->id,
                        'doc_type' => 'SALES_RETURN',
                        'doc_number' => $srDocNumber,
                    ], [
                        'status' => 'SUCCESS',
                    ]);
                }
                $this->info("[4/7] Order Retur baru berhasil dibuat: {$retOrderNumber}");
            }

            // 5. Buat Order Pengganti (WR-CLM-...)
            $wrOrderNumber = 'WR-' . $claim->claim_number;
            $wrOrder = Order::where('order_number', $wrOrderNumber)->first();

            if (!$wrOrder) {
                $wrOrder = Order::create([
                    'business_unit_id' => $warranty->policy?->business_unit_id ?? $business_unit_id,
                    'user_id' => $userId,
                    'order_number' => $wrOrderNumber,
                    'accurate_invoice_no' => $invoiceDocNumber,
                    'order_date' => Carbon::now()->format('Y-m-d'),
                    'total_amount' => $newPrice,
                    'shipping_cost' => 0,
                    'discount_amount' => 0,
                    'mdr_percentage' => 0,
                    'mdr_amount' => 0,
                    'grand_total' => $newPrice,
                    'order_status' => 'COMPLETED',
                    'order_channel' => 'POS',
                    'handled_by' => $request->requested_by,
                    'sales_id' => $selected_sales_id ?? ($warranty->orderItem?->order?->sales_id ?? $request->requested_by),
                    'shipping_address_snapshot' => ['type' => 'POS', 'store' => $branch_name, 'is_warranty_replacement' => true],
                    'notes' => "Ganti Unit Klaim Garansi #{$claim->claim_number}. Pengganti untuk IMEI: {$oldSn}",
                    'branch_id' => $branch_id,
                ]);

                // Resolusi variant jika beda
                $newVariant = null;
                if ($replacement_type === 'different' && $replacement_item_no) {
                    $newVariant = ProductVariant::whereHas('accurateData', function ($q) use ($replacement_item_no) {
                        $q->where('item_no', $replacement_item_no);
                    })->first() ?? SecondProductVariant::whereHas('accurateData', function ($q) use ($replacement_item_no) {
                        $q->where('item_no', $replacement_item_no);
                    })->first();
                }

                $orderItem = OrderItem::create([
                    'order_id' => $wrOrder->id,
                    'product_id' => $warranty->orderItem?->product_id ?? 1,
                    'product_variant_type' => $replacement_type === 'different' && $replacement_item_no ?
                        ($newVariant ? get_class($newVariant) : $warranty->orderItem?->product_variant_type) :
                        $warranty->orderItem?->product_variant_type,
                    'product_variant_id' => $replacement_type === 'different' && $replacement_item_no ?
                        ($newVariant?->id ?? $warranty->orderItem?->product_variant_id) :
                        $warranty->orderItem?->product_variant_id,
                    'product_name' => $replacement_type === 'different' && $replacement_product_name ?
                        $replacement_product_name :
                        ($warranty->orderItem?->product_name ?? 'Unit Pengganti Garansi'),
                    'serial_number' => $newSn,
                    'qty' => 1,
                    'price_at_checkout' => $newPrice,
                    'vendor_name_snapshot' => $warranty->orderItem?->vendor_name_snapshot,
                    'discount_amount' => 0,
                    'promo_discount_amount' => 0,
                    'subtotal' => $newPrice,
                ]);

                $warranty->order_item_id = $orderItem->id;
                $warranty->save();

                $this->info("[5/7] Order Pengganti {$wrOrderNumber} berhasil dibuat!");
            } else {
                $this->info("[5/7] Order Pengganti {$wrOrderNumber} sudah ada.");
            }

            if ($invoiceDocNumber) {
                if (!$wrOrder->accurate_invoice_no) {
                    $wrOrder->accurate_invoice_no = $invoiceDocNumber;
                    $wrOrder->save();
                }
                OrderAccurateDoc::firstOrCreate([
                    'order_id' => $wrOrder->id,
                    'doc_type' => 'SALES_INVOICE',
                    'doc_number' => $invoiceDocNumber,
                ], [
                    'status' => 'SUCCESS',
                ]);
            }

            if ($receiptDocNumber) {
                OrderAccurateDoc::firstOrCreate([
                    'order_id' => $wrOrder->id,
                    'doc_type' => 'SALES_RECEIPT',
                    'doc_number' => $receiptDocNumber,
                ], [
                    'status' => 'SUCCESS',
                ]);
            }

            // 6. Update Status IMEI Baru di tabel ProductSerialNumber menjadi Sold
            ProductSerialNumber::where('serial_number', $newSn)->update(['status' => 'Sold']);
            $this->info("[6/7] Status IMEI {$newSn} di tabel ProductSerialNumber diperbarui menjadi 'Sold'");

            // 7. Selesaikan status ApprovalRequest
            $request->status = 'COMPLETED';
            $request->save();
            $this->info("[7/7] Status ApprovalRequest #{$approvalId} diubah menjadi COMPLETED");

            DB::commit();
            $this->newLine();
            $this->info("✅ PROSES RECOVERY SUKSES PENUH! Transaksi POS dan Garansi sudah sinkron dan seimbang.");
            return Command::SUCCESS;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Gagal memulihkan transaksi: " . $e->getMessage());
            Log::error("Recovery Warranty Claim Error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return Command::FAILURE;
        }
    }
}

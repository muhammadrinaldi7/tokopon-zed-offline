<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DeviceInspection;
use App\Models\OrderItem;
use App\Models\Warranty;
use App\Services\WarrantyCalculatorService;
use Carbon\Carbon;

class GenerateMissingWarranties extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'warranty:generate-missing {--bu= : Filter berdasarkan Business Unit ID (contoh: 2)} {--sn= : Generate hanya untuk Serial Number / IMEI tertentu}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Otomatis generate kartu garansi untuk IMEI yang sudah diinspeksi QC namun garansinya masih gantung / belum terbentuk';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $buId = $this->option('bu');
        $specificSn = $this->option('sn');

        $this->info("==================================================");
        $this->info("   AUTO-GENERATE KARTU GARANSI UNTUK IMEI GANTUNG   ");
        $this->info("==================================================");

        $query = DeviceInspection::where('inspectable_type', OrderItem::class)
            ->whereNotNull('inspectable_id');

        if (!empty($specificSn)) {
            $query->where('imei', trim($specificSn));
        }

        $inspections = $query->orderBy('id', 'desc')->get();

        $this->info("Memeriksa {$inspections->count()} riwayat inspeksi perangkat...");

        $calculator = new WarrantyCalculatorService();
        $fixedCount = 0;
        $skippedCount = 0;

        foreach ($inspections as $inspection) {
            $sn = trim($inspection->imei);
            if (empty($sn)) continue;

            $orderItem = OrderItem::with(['order', 'variant', 'promos'])->find($inspection->inspectable_id);
            if (!$orderItem || !$orderItem->order) {
                continue;
            }

            $order = $orderItem->order;

            // Filter BU jika diisi
            if ($buId && $order->business_unit_id != $buId) {
                continue;
            }

            // Cek apakah sudah ada garansi aktif untuk transaksi ini
            $hasActiveWarranty = Warranty::where('serial_number', $sn)
                ->where('order_item_id', $orderItem->id)
                ->where('status', 'active')
                ->exists();

            if ($hasActiveWarranty) {
                $skippedCount++;
                continue;
            }

            // Hitung kebijakan garansi menggunakan WarrantyCalculatorService
            $policies = $calculator->calculateWarranties($order, $orderItem);

            if ($policies->isEmpty()) {
                $this->warn("[-] IMEI {$sn} (Item: {$orderItem->product_name}): Tidak ada kebijakan garansi yang cocok.");
                continue;
            }

            $now = Carbon::now();

            foreach ($policies as $policy) {
                Warranty::create([
                    'warranty_policy_id' => $policy->id,
                    'order_item_id' => $orderItem->id,
                    'serial_number' => $sn,
                    'customer_user_id' => $order->user_id,
                    'type' => $policy->coverage_type,
                    'duration_days' => $policy->duration_days,
                    'activated_at' => $inspection->created_at ?? $now,
                    'expires_at' => ($inspection->created_at ?? $now)->copy()->addDays($policy->duration_days),
                    'status' => 'active',
                    'claims_used' => 0,
                    'device_inspection_id' => $inspection->id,
                    'source' => $policy->type === 'addon_warranty' ? 'purchase' : 'activation',
                ]);

                $this->info("[✓] SUKSES: IMEI {$sn} -> Garansi '{$policy->name}' ({$policy->duration_days} Hari) berhasil dibuat!");
                $fixedCount++;
            }
        }

        $this->newLine();
        $this->info("==================================================");
        $this->info("PROSES SELESAI:");
        $this->info("- Berhasil digenerate : {$fixedCount} kartu garansi");
        $this->info("- Sudah punya garansi : {$skippedCount} perangkat");
        $this->info("==================================================");

        return 0;
    }
}

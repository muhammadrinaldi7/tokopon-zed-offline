<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\MobileOrderService;
use Exception;
use Illuminate\Console\Command;

class ExpireMobileOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:expire-mobile {--dry-run : Menampilkan order yang akan di-expire tanpa mengubah data di database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Membatalkan otomatis pesanan Mobile App yang berstatus WAITING_PAYMENT dan telah melewati batas payment_expired_at';

    /**
     * Execute the console command.
     */
    public function handle(MobileOrderService $mobileOrderService): int
    {
        $isDryRun = (bool) $this->option('dry-run');

        $this->info("=================================================");
        $this->info(" AUTO-EXPIRE PESANAN MOBILE APP (WAITING_PAYMENT)");
        $this->info("=================================================");

        if ($isDryRun) {
            $this->warn("[DRY-RUN MODE AKTIF: Data di database tidak akan diubah]");
        }

        // Cari pesanan Mobile App yang sudah lewat tenggat bayar
        $expiredOrders = Order::where('order_channel', 'MOBILE_APP')
            ->where('order_status', 'WAITING_PAYMENT')
            ->whereNotNull('payment_expired_at')
            ->where('payment_expired_at', '<', now())
            ->with(['items', 'warehouse', 'user'])
            ->get();

        if ($expiredOrders->isEmpty()) {
            $this->info("Tidak ada pesanan Mobile App yang kadaluwarsa.");
            return Command::SUCCESS;
        }

        $this->info("Ditemukan {$expiredOrders->count()} pesanan yang telah kadaluwarsa:");

        $tableData = [];
        $successCount = 0;
        $failCount = 0;

        foreach ($expiredOrders as $order) {
            $tableData[] = [
                'ID' => $order->id,
                'Order Number' => $order->order_number,
                'Customer' => $order->user?->name ?? 'Guest/Unknown',
                'Grand Total' => 'Rp ' . number_format($order->grand_total, 0, ',', '.'),
                'Expired At' => $order->payment_expired_at?->format('Y-m-d H:i:s'),
                'Items Count' => $order->items->count(),
            ];

            if (!$isDryRun) {
                try {
                    $mobileOrderService->cancelOrExpireOrder($order, 'PAYMENT_EXPIRED');
                    $this->line(" [OK] Order #{$order->order_number} berhasil dibatalkan dan stok dikembalikan.");
                    $successCount++;
                } catch (Exception $e) {
                    $this->error(" [ERR] Gagal membatalkan Order #{$order->order_number}: " . $e->getMessage());
                    $failCount++;
                }
            }
        }

        $this->table(['ID', 'Order Number', 'Customer', 'Grand Total', 'Expired At', 'Items Count'], $tableData);

        if (!$isDryRun) {
            $this->info("Selesai. Sukses: {$successCount}, Gagal: {$failCount}.");
        }

        return Command::SUCCESS;
    }
}

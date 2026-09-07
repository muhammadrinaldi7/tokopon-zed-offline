<?php

namespace App\Console\Commands;

use App\Models\BusinessUnit;
use App\Services\AccurateReturnSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncAccurateSalesReturns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accurate:sync-sales-returns 
                            {--bu=syihab : Business Unit code (e.g. syihab, second, distri)}
                            {--from= : Start Date filter (Y-m-d)}
                            {--to= : End Date filter (Y-m-d)}
                            {--dry-run : Only show preview without saving any data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch and safely sync Sales Returns from Accurate Online into POS system to balance sales reports';

    /**
     * Execute the console command.
     */
    public function handle(AccurateReturnSyncService $syncService)
    {
        $buCode = $this->option('bu') ?: 'syihab';
        $startDate = $this->option('from');
        $endDate = $this->option('to');
        $isDryRun = $this->option('dry-run');

        $bu = BusinessUnit::where('code', $buCode)->first();
        if (!$bu) {
            $this->error("Business Unit with code '{$buCode}' not found.");
            return Command::FAILURE;
        }

        $this->info("Fetching Sales Returns from Accurate for BU: {$bu->name} ({$buCode})...");
        if ($startDate || $endDate) {
            $this->info("Date Range: " . ($startDate ?: 'All') . " to " . ($endDate ?: 'All'));
        }

        try {
            $preview = $syncService->previewReturns($startDate, $endDate, $buCode);
            $summary = $preview['summary'];
            $items = $preview['items'];

            $this->table(
                ['Summary Metric', 'Value'],
                [
                    ['Business Unit', $summary['business_unit'] . " ({$summary['bu_code']})"],
                    ['Total Returns in Accurate', $summary['total_count']],
                    ['Already Synced in POS', $summary['already_synced_count']],
                    ['Ready to Sync (Legacy/Manual)', $summary['ready_to_sync_count']],
                    ['Total Amount Ready to Sync', 'Rp ' . number_format($summary['ready_to_sync_total_amount'], 0, ',', '.')],
                ]
            );

            if (empty($items)) {
                $this->info("No Sales Returns found in Accurate for the given criteria.");
                return Command::SUCCESS;
            }

            // Show items table
            $rows = [];
            foreach ($items as $item) {
                $rows[] = [
                    $item['number'],
                    $item['trans_date'],
                    $item['customer_name'],
                    $item['branch_name'],
                    'Rp ' . number_format($item['total_amount'], 0, ',', '.'),
                    $item['status'] === 'ALREADY_SYNCED' ? '<info>ALREADY SYNCED</info>' : '<comment>READY TO SYNC</comment>',
                    $item['pos_order_number'] ?? '-',
                ];
            }

            $this->table(
                ['SR Number', 'Date', 'Customer', 'Branch', 'Amount', 'Status', 'POS Order'],
                $rows
            );

            if ($isDryRun) {
                $this->warn("DRY RUN MODE: No changes were written to the database.");
                return Command::SUCCESS;
            }

            if ($summary['ready_to_sync_count'] === 0) {
                $this->info("All returns are already synchronized with POS. Nothing to do.");
                return Command::SUCCESS;
            }

            if (!$this->confirm("Do you want to proceed and sync {$summary['ready_to_sync_count']} pending return(s)?", true)) {
                $this->info("Aborted by user.");
                return Command::SUCCESS;
            }

            $this->info("Starting synchronization...");
            $result = $syncService->syncAllReturns($startDate, $endDate, $buCode);

            $this->info("Synchronization Finished:");
            $this->line("  * Successfully Synced: {$result['synced_count']}");
            $this->line("  * Skipped: {$result['skipped_count']}");
            $this->line("  * Failed: {$result['failed_count']}");
            $this->line("  * Total Amount Offset: Rp " . number_format($result['total_synced_amount'], 0, ',', '.'));

            if (!empty($result['failed_items'])) {
                $this->error("Failed Items:");
                foreach ($result['failed_items'] as $fail) {
                    $this->error("  - SR: {$fail['number']} | Error: {$fail['error']}");
                }
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Error during sync: " . $e->getMessage());
            Log::error("CLI SyncAccurateSalesReturns Error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return Command::FAILURE;
        }
    }
}

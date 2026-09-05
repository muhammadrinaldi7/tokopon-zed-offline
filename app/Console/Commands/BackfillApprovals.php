<?php

namespace App\Console\Commands;

use App\Models\ApprovalRequest;
use App\Models\Order;
use App\Models\SellPhone;
use App\Models\WarrantyClaim;
use App\Models\Warranty;
use Illuminate\Console\Command;

class BackfillApprovals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backfill-approvals {--force : Update semua data meskipun business_unit_id sudah terisi}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill business_unit_id, branch_id, dan total_amount pada approval_requests yang masih kosong';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Memulai proses backfill data Approval Request...');

        $query = ApprovalRequest::with(['approvable', 'requestedBy']);
        if (!$this->option('force')) {
            $query->where(function ($q) {
                $q->whereNull('business_unit_id')
                  ->orWhereNull('branch_id');
            });
        }

        $requests = $query->get();
        $total = $requests->count();

        if ($total === 0) {
            $this->info('Semua data approval request sudah terisi dengan benar. Tidak ada yang perlu di-backfill.');
            return 0;
        }

        $this->info("Ditemukan {$total} approval request yang akan diproses.");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $updated = 0;
        foreach ($requests as $r) {
            $buId = null;
            $branchId = null;
            $amount = null;

            if ($r->approvable instanceof Order) {
                $buId = $r->approvable->business_unit_id;
                $branchId = $r->approvable->branch_id;
                $amount = $r->approvable->grand_total;
            } elseif ($r->approvable instanceof SellPhone) {
                $buId = $r->approvable->business_unit_id;
                $branchId = $r->approvable->branch_id;
                $amount = $r->approvable->appraised_value;
            } elseif ($r->approvable instanceof WarrantyClaim) {
                $buId = $r->approvable->warranty?->policy?->business_unit_id;
                $branchId = $r->approvable->claimedBy?->branch_id ?? $r->requestedBy?->branch_id;
            } elseif ($r->approvable instanceof Warranty) {
                $buId = $r->approvable->policy?->business_unit_id;
                $branchId = $r->requestedBy?->branch_id;
            }

            // Fallback ke data user requester
            if (!$buId) {
                $buId = $r->requestedBy?->business_unit_id ?? 1;
            }
            if (!$branchId) {
                $branchId = $r->requestedBy?->branch_id;
            }

            $r->update([
                'business_unit_id' => $buId,
                'branch_id' => $branchId,
                'total_amount' => $amount ?? $r->total_amount,
            ]);

            $updated++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Berhasil melakukan backfill pada {$updated} data approval request.");

        return 0;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class BackfillWarrantyClaims extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'warranty:backfill-claims';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill replacement fields on warranty claims from old approval requests';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting backfill for warranty claims...');

        $requests = \App\Models\ApprovalRequest::where('approvable_type', \App\Models\WarrantyClaim::class)
            ->where('request_type', 'WARRANTY_REPLACEMENT')
            ->where('status', 'COMPLETED')
            ->get();

        $count = 0;

        foreach ($requests as $request) {
            $claim = \App\Models\WarrantyClaim::find($request->approvable_id);
            if (!$claim) continue;

            $payload = $request->payload;
            if (empty($payload)) continue;

            $replacement_imei = $payload['replacement_imei'] ?? null;
            $replacement_type = $payload['replacement_type'] ?? 'same';
            $replacement_item_no = $payload['replacement_item_no'] ?? null;
            $replacement_product_name = $payload['replacement_product_name'] ?? null;

            $newItemNo = $replacement_type === 'different' ? $replacement_item_no : null;
            
            // Set missing fields
            $updated = false;
            if (empty($claim->resolution_type)) {
                $claim->resolution_type = $replacement_type === 'same' ? 'replacement_same' : 'replacement_different';
                $updated = true;
            }
            if (empty($claim->replacement_item_no) && $newItemNo) {
                $claim->replacement_item_no = $newItemNo;
                $updated = true;
            }
            if (empty($claim->replacement_product_name) && $replacement_product_name) {
                $claim->replacement_product_name = $replacement_product_name;
                $updated = true;
            }

            if ($updated) {
                $claim->save();
                $count++;
                $this->line("Updated Claim ID {$claim->id}: type={$claim->resolution_type}, ItemNo={$claim->replacement_item_no}");
            }
        }

        $this->info("Finished backfilling. Total updated records: $count");
    }
}

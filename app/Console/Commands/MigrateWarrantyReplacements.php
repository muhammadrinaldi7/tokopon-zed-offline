<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MigrateWarrantyReplacements extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'warranty:migrate-replacements';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate old replicated warranties into warranty_serial_logs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting migration of old replicated warranties...');

        $replacedClaims = \App\Models\WarrantyClaim::where('resolution', 'replaced')
            ->with('warranty')
            ->get();

        $migratedCount = 0;

        foreach ($replacedClaims as $claim) {
            $voidedWarranty = $claim->warranty;
            if (!$voidedWarranty) continue;
            
            // Cari garansi baru yang direplikasi (ciri: policy sama, customer sama, waktu dibuat sesudah claim)
            $newWarranty = \App\Models\Warranty::where('warranty_policy_id', $voidedWarranty->warranty_policy_id)
                ->where('customer_user_id', $voidedWarranty->customer_user_id)
                ->where('serial_number', '!=', $voidedWarranty->serial_number)
                ->where('created_at', '>=', $claim->created_at)
                ->first();

            if ($newWarranty) {
                // Buat log retroaktif
                $log = \App\Models\WarrantySerialLog::firstOrCreate(
                    [
                        'warranty_id' => $voidedWarranty->id,
                        'warranty_claim_id' => $claim->id,
                        'old_serial_number' => $voidedWarranty->serial_number,
                        'new_serial_number' => $newWarranty->serial_number,
                    ],
                    [
                        'reason' => 'replacement_migration',
                        'notes' => 'Data migrasi otomatis dari sistem lama',
                    ]
                );
                
                // Set original SN di garansi yang baru aktif
                if (!$newWarranty->original_serial_number) {
                    $newWarranty->original_serial_number = $voidedWarranty->serial_number;
                    $newWarranty->replacement_count = 1;
                    $newWarranty->save();
                }

                $this->info("Migrated SN: {$voidedWarranty->serial_number} -> {$newWarranty->serial_number}");
                $migratedCount++;
            }
        }

        $this->info("Migration completed. Total migrated: $migratedCount");
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BusinessUnit;
use App\Models\BusinessUnitProject;
use App\Services\AccurateService;
use Illuminate\Support\Facades\Log;

class SyncAccurateProjects extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accurate:sync-projects';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch and sync accurate project list for all active business units';

    /**
     * Execute the console command.
     */
    public function handle(AccurateService $accurateService)
    {
        $businessUnits = BusinessUnit::where('is_active', true)
            ->whereNotNull('accurate_host')
            ->whereNotNull('accurate_token')
            ->get();

        $this->info("Found {$businessUnits->count()} active business units with accurate credentials.");

        foreach ($businessUnits as $bu) {
            $this->info("Fetching projects for BU: {$bu->name} ({$bu->code})");

            try {
                $projects = $accurateService->projectListDo($bu->code);

                if (!empty($projects)) {
                    $count = 0;
                    foreach ($projects as $project) {
                        if (isset($project['id']) && isset($project['no']) && isset($project['name'])) {
                            BusinessUnitProject::updateOrCreate(
                                [
                                    'business_unit_id' => $bu->id,
                                    'project_no' => $project['no'],
                                ],
                                [
                                    'project_id' => $project['id'],
                                    'name' => $project['name'],
                                ]
                            );
                            $count++;
                        }
                    }
                    $this->info("Successfully synced {$count} projects for {$bu->name}.");
                } else {
                    $this->info("No projects found for {$bu->name}.");
                }

            } catch (\Exception $e) {
                $this->error("Failed to fetch projects for {$bu->name}: " . $e->getMessage());
                Log::error("SyncAccurateProjects Error for BU {$bu->code}: " . $e->getMessage());
            }
        }

        $this->info("Sync completed.");
        return Command::SUCCESS;
    }
}

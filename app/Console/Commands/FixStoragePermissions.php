<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class FixStoragePermissions extends Command
{
    protected $signature = 'storage:fix-permissions';
    protected $description = 'Fix permissions for storage and public media directories to ensure web worker write access';

    public function handle()
    {
        $storagePath = storage_path();
        $publicStoragePath = storage_path('app/public');
        $bootstrapCachePath = base_path('bootstrap/cache');

        $this->info("Fixing storage permissions on: {$storagePath}");

        $paths = [
            $storagePath,
            $publicStoragePath,
            $bootstrapCachePath,
        ];

        foreach ($paths as $path) {
            if (File::exists($path)) {
                $this->chmodRecursive($path, 0777, 0666);
                $this->info("✓ Permissions updated for: {$path}");
            }
        }

        $this->info("All storage and cache directories have been set to 0777 / 0666 successfully.");
        return Command::SUCCESS;
    }

    private function chmodRecursive($path, $dirPerms = 0777, $filePerms = 0666)
    {
        if (!file_exists($path)) return;

        @chmod($path, $dirPerms);

        if (is_dir($path)) {
            $items = scandir($path);
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') continue;
                $fullPath = $path . DIRECTORY_SEPARATOR . $item;
                if (is_dir($fullPath)) {
                    @chmod($fullPath, $dirPerms);
                    $this->chmodRecursive($fullPath, $dirPerms, $filePerms);
                } else {
                    @chmod($fullPath, $filePerms);
                }
            }
        }
    }
}

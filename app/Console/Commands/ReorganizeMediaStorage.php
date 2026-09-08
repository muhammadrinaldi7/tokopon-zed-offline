<?php

namespace App\Console\Commands;

use App\Paths\TokoponPathGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ReorganizeMediaStorage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:reorganize-storage 
                            {--disk=public : The storage disk to process}
                            {--dry-run : Simulate the migration without moving files or deleting folders}
                            {--quarantine-orphans : Move unindexed numeric folders to _orphans/}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reorganize media files from root numeric IDs into structured model folders (e.g. device_inspections/{id}/)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $diskName = $this->option('disk') ?: config('media-library.disk_name', 'public');
        $isDryRun = $this->option('dry-run');
        $quarantineOrphans = $this->option('quarantine-orphans');

        $this->info("===============================================================");
        $this->info("  SPATIE MEDIA LIBRARY STORAGE REORGANIZATION");
        $this->info("===============================================================");
        $this->line("Target Disk: <comment>{$diskName}</comment>");
        $this->line("Mode: " . ($isDryRun ? "<fg=yellow;options=bold>DRY RUN (Simulasi - Tanpa Ubah Data)</>" : "<fg=green;options=bold>LIVE EXECUTION (Pindahkan File)</>"));
        $this->newLine();

        $storage = Storage::disk($diskName);
        $pathGenerator = new TokoponPathGenerator();

        $mediaQuery = Media::where('disk', $diskName);
        $totalMedia = $mediaQuery->count();

        if ($totalMedia === 0) {
            $this->warn("Tidak ada record media ditemukan pada disk '{$diskName}'.");
            return Command::SUCCESS;
        }

        $this->info("Ditemukan {$totalMedia} record media di database. Memulai analisis...");

        $alreadyOrganized = 0;
        $movedCount = 0;
        $missingCount = 0;
        $failedCount = 0;
        $movedList = [];
        $missingList = [];

        $progressBar = $this->output->createProgressBar($totalMedia);
        $progressBar->start();

        $mediaQuery->chunk(100, function ($mediaItems) use (
            $storage,
            $pathGenerator,
            $isDryRun,
            &$alreadyOrganized,
            &$movedCount,
            &$missingCount,
            &$failedCount,
            &$movedList,
            &$missingList,
            $progressBar
        ) {
            foreach ($mediaItems as $media) {
                $targetPath = $pathGenerator->getPath($media); // e.g. device_inspections/15/76/
                $fileName = $media->file_name;
                $targetFilePath = $targetPath . $fileName;

                // 1. Cek apakah file sudah berada di folder target baru yang rapi
                if ($storage->exists($targetFilePath)) {
                    $alreadyOrganized++;
                    
                    // Bersihkan folder legacy lama jika ada dan kosong
                    $oldLegacyPath = $media->id . '/';
                    if (!$isDryRun && $storage->exists($oldLegacyPath)) {
                        $filesInLegacy = $storage->allFiles($oldLegacyPath);
                        if (empty($filesInLegacy)) {
                            $storage->deleteDirectory($oldLegacyPath);
                        }
                    }
                    $progressBar->advance();
                    continue;
                }

                // 2. Tentukan kemungkinan lokasi file saat ini (legacy {id}/ atau intermediate 1-level {model}/{model_id}/)
                $possibleSourceDirs = [
                    $media->id . '/', // Legacy Spatie default
                ];

                // Tambahkan kemungkinan folder 1-level sebelumnya
                if ($media->model_type && $media->model_id) {
                    $modelClass = $media->model_type;
                    if ($modelClass === 'App\Models\TradeIn') {
                        $possibleSourceDirs[] = 'tradein/' . $media->model_id . '/';
                    } elseif ($modelClass === 'App\Models\SellPhone') {
                        $possibleSourceDirs[] = 'sellphone/' . $media->model_id . '/';
                    } elseif ($modelClass === 'App\Models\DeviceInspection') {
                        $possibleSourceDirs[] = 'device_inspections/' . $media->model_id . '/';
                    } elseif ($modelClass === 'App\Models\User') {
                        $possibleSourceDirs[] = 'users/' . $media->model_id . '/';
                    } elseif ($modelClass === 'App\Models\Product' || $modelClass === 'App\Models\ProductAccurate') {
                        $possibleSourceDirs[] = 'products/' . $media->model_id . '/';
                    } else {
                        $folderName = \Illuminate\Support\Str::snake(\Illuminate\Support\Str::plural(class_basename($modelClass)));
                        $possibleSourceDirs[] = $folderName . '/' . $media->model_id . '/';
                    }
                }

                $foundSourceDir = null;
                $foundSourceFile = null;

                foreach ($possibleSourceDirs as $srcDir) {
                    $srcFile = $srcDir . $fileName;
                    if ($storage->exists($srcFile)) {
                        $foundSourceDir = $srcDir;
                        $foundSourceFile = $srcFile;
                        break;
                    }
                }

                // 3. Jika file sumber ditemukan, pindahkan ke target baru
                if ($foundSourceFile) {
                    if ($isDryRun) {
                        $movedCount++;
                        $movedList[] = [
                            'id' => $media->id,
                            'model' => class_basename($media->model_type) . " ({$media->model_id})",
                            'from' => $foundSourceFile,
                            'to' => $targetFilePath,
                        ];
                    } else {
                        try {
                            // Buat target directory jika belum ada
                            $storage->makeDirectory($targetPath);

                            // Pindahkan file utama
                            $storage->move($foundSourceFile, $targetFilePath);

                            // Pindahkan conversions jika ada
                            $legacyConvPath = $foundSourceDir . 'conversions/';
                            $targetConvPath = $targetPath . 'conversions/';
                            if ($storage->exists($legacyConvPath)) {
                                $storage->makeDirectory($targetConvPath);
                                foreach ($storage->files($legacyConvPath) as $convFile) {
                                    $cName = basename($convFile);
                                    $storage->move($convFile, $targetConvPath . $cName);
                                }
                            }

                            // Pindahkan responsive images jika ada
                            $legacyRespPath = $foundSourceDir . 'responsive/';
                            $targetRespPath = $targetPath . 'responsive/';
                            if ($storage->exists($legacyRespPath)) {
                                $storage->makeDirectory($targetRespPath);
                                foreach ($storage->files($legacyRespPath) as $respFile) {
                                    $rName = basename($respFile);
                                    $storage->move($respFile, $targetRespPath . $rName);
                                }
                            }

                            // Hapus folder sumber jika itu folder angka murni dan sudah kosong
                            if (is_numeric(rtrim($foundSourceDir, '/'))) {
                                $remaining = $storage->allFiles($foundSourceDir);
                                if (empty($remaining)) {
                                    $storage->deleteDirectory($foundSourceDir);
                                }
                            }

                            $movedCount++;
                        } catch (\Exception $e) {
                            $failedCount++;
                            $this->error("\nGagal memindahkan media ID {$media->id}: " . $e->getMessage());
                        }
                    }
                } else {
                    // File tidak ditemukan di lokasi manapun
                    $missingCount++;
                    $missingList[] = [
                        'id' => $media->id,
                        'model' => class_basename($media->model_type) . " ({$media->model_id})",
                        'file' => $fileName,
                        'expected_at' => implode(' OR ', $possibleSourceDirs),
                    ];
                }

                $progressBar->advance();
            }
        });

        $progressBar->finish();
        $this->newLine(2);

        // 3. Scan & Bersihkan Direktori Angka Kosong di Root Disk
        $cleanedEmptyFolders = 0;
        $quarantinedCount = 0;

        $topDirs = $storage->directories();
        foreach ($topDirs as $dir) {
            // Cek apakah nama folder murni angka (seperti 1, 2, 1024)
            if (is_numeric($dir)) {
                $filesInDir = $storage->allFiles($dir);
                if (empty($filesInDir)) {
                    if (!$isDryRun) {
                        $storage->deleteDirectory($dir);
                    }
                    $cleanedEmptyFolders++;
                } else {
                    // Jika ada isi, cek apakah ada recordnya di media DB
                    $mediaId = (int)$dir;
                    $existsInDb = Media::where('id', $mediaId)->exists();
                    if (!$existsInDb && $quarantineOrphans) {
                        if (!$isDryRun) {
                            $storage->makeDirectory('_orphans');
                            $storage->move($dir, '_orphans/' . $dir);
                        }
                        $quarantinedCount++;
                    }
                }
            }
        }

        // Tampilkan Hasil Rekapitulasi
        $this->table(
            ['Metrik Migrasi', 'Jumlah File / Folder'],
            [
                ['Total Media Terdaftar di DB', $totalMedia],
                ['Sudah Berada di Folder Baru (Rapi)', "<info>{$alreadyOrganized}</info>"],
                [$isDryRun ? 'Siap Dipindahkan' : 'Berhasil Dipindahkan', "<comment>{$movedCount}</comment>"],
                ['Gagal Dipindahkan', $failedCount > 0 ? "<error>{$failedCount}</error>" : "0"],
                ['File Fisik Tidak Ditemukan (Missing)', $missingCount > 0 ? "<error>{$missingCount}</error>" : "0"],
                ['Folder Angka Kosong yang Dibersihkan', "<info>{$cleanedEmptyFolders}</info>"],
                ['Folder Yatim Piatu (Orphans) Dikarantina', $quarantinedCount > 0 ? "<comment>{$quarantinedCount}</comment>" : "0"],
            ]
        );

        if (!empty($movedList) && $isDryRun && count($movedList) <= 20) {
            $this->newLine();
            $this->info("Contoh pemindahan yang akan dilakukan:");
            $sampleRows = array_slice($movedList, 0, 10);
            $this->table(['Media ID', 'Model', 'Asal (Lama)', 'Tujuan (Baru)'], $sampleRows);
        }

        if (!empty($missingList)) {
            $this->newLine();
            $this->warn("Peringatan: Ada {$missingCount} file yang tercatat di DB tetapi fisiknya tidak ada di disk:");
            $sampleMissing = array_slice($missingList, 0, 5);
            $this->table(['Media ID', 'Model', 'File Name', 'Expected Path'], $sampleMissing);
        }

        if ($isDryRun) {
            $this->newLine();
            $this->info("Untuk mengeksekusi pemindahan file fisik, jalankan perintah tanpa flag --dry-run:");
            $this->line("  <comment>php artisan media:reorganize-storage</comment>");
        } else {
            $this->newLine();
            $this->info("✅ Migrasi struktur storage media selesai! Direktori storage/app/public/ kini rapi.");
        }

        return Command::SUCCESS;
    }
}

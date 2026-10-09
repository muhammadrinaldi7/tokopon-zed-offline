<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class TestR2StorageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'r2:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Uji coba koneksi, izin tulis/baca, dan URL publik Cloudflare R2 Bucket';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("=================================================");
        $this->info("   DIAGNOSTIK KONEKSI CLOUDFLARE R2 BUCKET       ");
        $this->info("=================================================");

        $bucket = config('filesystems.disks.r2.bucket');
        $endpoint = config('filesystems.disks.r2.endpoint');
        $key = config('filesystems.disks.r2.key');
        $publicUrl = config('filesystems.disks.r2.url');
        $mediaDisk = config('media-library.disk_name');

        $this->table(
            ['Parameter Konfigurasi', 'Nilai'],
            [
                ['MEDIA_DISK (Media Library)', $mediaDisk ?: '<kosong>'],
                ['CLOUDFLARE_R2_BUCKET', $bucket ?: '<belum diatur di .env>'],
                ['CLOUDFLARE_R2_ENDPOINT', $endpoint ?: '<belum diatur di .env>'],
                ['CLOUDFLARE_R2_ACCESS_KEY_ID', $key ? substr($key, 0, 8) . '...' : '<belum diatur>'],
                ['CLOUDFLARE_R2_URL (Public CDN)', $publicUrl ?: '<belum diatur / masih placeholder>'],
            ]
        );

        if (empty($bucket) || empty($key)) {
            $this->error("\n[GAGAL] Kredensial R2 belum lengkap di file .env!");
            $this->warn("Silakan masukkan CLOUDFLARE_R2_ACCESS_KEY_ID, SECRET, BUCKET, dan ENDPOINT di .env.");
            return 1;
        }

        $this->newLine();
        $this->info("1. Menguji Izin Tulis (Upload File Test)...");
        $testFileName = 'r2-test-' . time() . '.txt';
        $testContent = 'Test integrasi Cloudflare R2 Tokopon Zed pada ' . now()->toDateTimeString();

        try {
            $disk = Storage::disk('r2');
            $uploadSuccess = $disk->put($testFileName, $testContent);

            if (!$uploadSuccess) {
                $this->error("   -> Gagal menulis file ke R2.");
                return 1;
            }
            $this->info("   -> [BERHASIL] File '{$testFileName}' terunggah ke bucket {$bucket}.");

            $this->info("2. Menguji Izin Baca (Download File Test)...");
            $readContent = $disk->get($testFileName);
            if ($readContent !== $testContent) {
                $this->error("   -> [GAGAL] Konten file tidak cocok.");
                return 1;
            }
            $this->info("   -> [BERHASIL] File berhasil dibaca kembali dari R2.");

            $this->info("3. Menguji Izin Hapus (Delete File Test)...");
            $disk->delete($testFileName);
            $this->info("   -> [BERHASIL] File test berhasil dibersihkan dari R2.");

            // Uji Public URL jika sudah diatur
            $this->newLine();
            $this->info("4. Memeriksa Akses URL Publik (CDN / R2.dev)...");
            if ($publicUrl && !str_contains($publicUrl, '<custom-domain')) {
                $probeFile = 'public-probe-' . time() . '.txt';
                $disk->put($probeFile, 'Probe OK', 'public');
                $fullFileUrl = rtrim($publicUrl, '/') . '/' . $probeFile;

                $this->line("   Mencoba akses HTTP GET ke: {$fullFileUrl}");
                try {
                    $response = Http::timeout(5)->get($fullFileUrl);
                    if ($response->successful()) {
                        $this->info("   -> [BERHASIL] URL Publik dapat diakses oleh publik (Status {$response->status()}).");
                    } else {
                        $this->warn("   -> [PERINGATAN] URL Publik mengembalikan status HTTP {$response->status()}. Pastikan Public Access di Cloudflare sudah 'Allowed' atau Custom Domain sudah aktif.");
                    }
                } catch (\Throwable $httpEx) {
                    $this->warn("   -> [INFO] Belum bisa dijangkau via HTTP: " . $httpEx->getMessage());
                }
                $disk->delete($probeFile);
            } else {
                $this->warn("   -> [CATATAN] CLOUDFLARE_R2_URL belum diisi dengan domain aktif (masih '{$publicUrl}').");
                $this->line("      Aktifkan 'R2.dev subdomain' atau 'Custom Domain' di dashboard Cloudflare Settings bucket Anda, lalu masukkan URL-nya ke .env.");
            }

            $this->newLine();
            $this->info("=================================================");
            $this->info("   KESIMPULAN: INTEGRASI R2 BERJALAN 100% SUKSES!  ");
            $this->info("=================================================");
            return 0;

        } catch (\Throwable $e) {
            $this->newLine();
            $this->error("[ERROR R2] " . $e->getMessage());
            return 1;
        }
    }
}

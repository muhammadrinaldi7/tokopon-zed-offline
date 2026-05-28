<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\AkunKasirMail;

class KirimEmailKasirCommand extends Command
{
    // Ini nama perintah yang akan kita ketik di terminal nanti
    protected $signature = 'email:kirim-kasir';

    // Deskripsi perintahnya
    protected $description = 'Kirim email kredensial login secara manual ke semua user kasir baru';

    public function handle()
    {
        $this->info('Memulai proses pengiriman email manual...');

        // Ambil data user yang ada di file CSV kamu (bisa dicari berdasarkan branch_id kasir atau relasi profile)
        // Sebagai contoh aman, kita ambil data user yang namanya/emailnya terdaftar di file CSV kasir Anda
        $csvFile = database_path('seeders/csv/users_kasir.csv');

        if (!file_exists($csvFile)) {
            $this->error("File CSV tidak ditemukan!");
            return Command::FAILURE;
        }

        $fileHandle = fopen($csvFile, 'r');
        $isHeader = true;
        $emails = [];

        while (($row = fgetcsv($fileHandle, 1000, ',')) !== FALSE) {
            if ($isHeader) {
                $isHeader = false;
                continue;
            }
            if (!empty($row[3])) $emails[] = trim($row[3]);
        }
        fclose($fileHandle);

        // Ambil data user dari DB berdasarkan list email yang ada di CSV
        $users = DB::table('users')->whereIn('email', $emails)->get();

        if ($users->isEmpty()) {
            $this->warn('Tidak ada data user kasir yang cocok di database.');
            return Command::SUCCESS;
        }

        $defaultPassword = 'password123'; // Password yang kamu set di seeder tadi

        foreach ($users as $user) {
            $this->info("Sedang mengirim email ke: {$user->email}...");

            try {
                // Kirim sinkronus (langsung jalankan SMTP)
                Mail::to($user->email)->send(new AkunKasirMail($user->name, $user->email, $defaultPassword));
                $this->info("✅ Sukses terkirim ke: {$user->email}");
            } catch (\Exception $e) {
                $this->error("❌ Gagal mengirim ke {$user->email}. Error: " . $e->getMessage());
            }
        }

        $this->info('Semua proses pengiriman email manual selesai!');
        return Command::SUCCESS;
    }
}

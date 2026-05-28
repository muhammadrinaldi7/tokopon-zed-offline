<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserKasirCsvSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Path menuju lokasi file CSV yang Anda simpan
        $csvFile = database_path('seeders/csv/users_kasir.csv');

        if (!file_exists($csvFile)) {
            $this->command->error("File CSV tidak ditemukan di lokasi: {$csvFile}");
            return;
        }

        // Membuka file CSV
        $fileHandle = fopen($csvFile, 'r');
        $isHeader = true;

        while (($row = fgetcsv($fileHandle, 1000, ',')) !== FALSE) {
            // Lewati baris pertama jika merupakan Header
            if ($isHeader) {
                $isHeader = false;
                continue;
            }

            $cabangName     = trim($row[1] ?? '');
            $nama           = trim($row[2] ?? '');
            $email          = trim($row[3] ?? '');
            $noHpRaw        = trim($row[4] ?? '');

            // Validasi: Jika nama atau email kosong, lewati baris ini
            if (empty($nama) || empty($email)) {
                continue;
            }

            // ================= LOGIKA MERAPIKAN NOMOR HP =================
            // 1. Hilangkan spasi, tanda strip (-), atau titik (.) jika ada
            $noHpClean = preg_replace('/[^0-9]/', '', $noHpRaw);

            // 2. Jika nomor diawali dengan '628', ubah menjadi '08'
            if (Str::startsWith($noHpClean, '628')) {
                $noHpClean = '0' . substr($noHpClean, 2);
            }
            // 3. Jika nomor langsung diawali dengan '8' (contoh: 82157940375), tambahkan '0' di depannya
            elseif (Str::startsWith($noHpClean, '8')) {
                $noHpClean = '0' . $noHpClean;
            }
            // =============================================================

            // Ambil branch_id secara otomatis dari tabel 'branches'
            $branch = DB::table('branches')->where('name', $cabangName)->first();
            $branchId = $branch ? $branch->id : null;

            // Insert atau Update data ke tabel 'users'
            DB::table('users')->updateOrInsert(
                ['email' => $email],
                [
                    'name'       => $nama,
                    'password'   => Hash::make('password123'),
                    'branch_id'  => $branchId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            // Ambil ID dari user yang baru saja diproses
            $user = DB::table('users')->where('email', $email)->first();

            if ($user) {
                // Insert atau Update nomor HP yang SUDAH RAPI ke tabel 'user_profiles'
                DB::table('user_profiles')->updateOrInsert(
                    ['user_id' => $user->id],
                    [
                        'full_name'    => $nama,
                        'phone_number' => $noHpClean, // <--- Hasil format rapi '08xxxxxxxxx'
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ]
                );
            }
        }

        fclose($fileHandle);

        $this->command->info('Seeding sukses!');
    }
}

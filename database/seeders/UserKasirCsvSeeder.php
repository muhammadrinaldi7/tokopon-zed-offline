<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User; // <-- PENTING: Pastikan Model User di-import ke sini

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

        // ================= TAHAP 1: COLLECT EMAIL DARI CSV =================
        $fileHandle = fopen($csvFile, 'r');
        $isHeader = true;
        $emails = [];

        while (($row = fgetcsv($fileHandle, 1000, ',')) !== FALSE) {
            if ($isHeader) {
                $isHeader = false;
                continue;
            }
            if (!empty($row[3])) {
                $emails[] = trim($row[3]);
            }
        }
        fclose($fileHandle);


        // ================= TAHAP 2: ROLLBACK / BERSIHKAN DATA LAMA =================
        if (!empty($emails)) {
            // Ambil semua ID user lama berdasarkan email di CSV
            $userIds = DB::table('users')->whereIn('email', $emails)->pluck('id');

            if ($userIds->isNotEmpty()) {
                // 1. Hapus relasi role Spatie di tabel model_has_roles agar tidak nyampah
                DB::table('model_has_roles')
                    ->whereIn('model_id', $userIds)
                    ->where('model_type', User::class)
                    ->delete();

                // 2. Hapus profile di tabel user_profiles
                DB::table('user_profiles')->whereIn('user_id', $userIds)->delete();

                // 3. Hapus user utama di tabel users
                DB::table('users')->whereIn('id', $userIds)->delete();
            }
        }


        // ================= TAHAP 3: PROSES SEEDING DATA BARU =================
        $fileHandle = fopen($csvFile, 'r');
        $isHeader = true;

        while (($row = fgetcsv($fileHandle, 1000, ',')) !== FALSE) {
            if ($isHeader) {
                $isHeader = false;
                continue;
            }

            $cabangName     = trim($row[1] ?? '');
            $nama           = trim($row[2] ?? '');
            $email          = trim($row[3] ?? '');
            $noHpRaw        = trim($row[4] ?? '');

            if (empty($nama) || empty($email)) {
                continue;
            }

            // Logika merapikan nomor HP
            $noHpClean = preg_replace('/[^0-9]/', '', $noHpRaw);
            if (Str::startsWith($noHpClean, '628')) {
                $noHpClean = '0' . substr($noHpClean, 2);
            } elseif (Str::startsWith($noHpClean, '8')) {
                $noHpClean = '0' . $noHpClean;
            }

            // Ambil branch_id secara otomatis dari tabel 'branches'
            $branch = DB::table('branches')->where('name', $cabangName)->first();
            $branchId = $branch ? $branch->id : null;

            // 🔥 TAMBAHAN: Ambil warehouse_id secara otomatis dari tabel 'warehouses'
            $warehouse = DB::table('warehouses')->where('name', $cabangName)->first();
            $warehouseId = $warehouse ? $warehouse->id : null;

            // Masukkan data menggunakan Eloquent Model User
            $adminUser = User::create([
                'name'         => $nama,
                'email'        => $email,
                'password'     => Hash::make('password123'),
                'branch_id'    => $branchId,
                'warehouse_id' => $warehouseId,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            if ($adminUser) {
                $adminUser->assignRole('kasir_sju');

                // Insert data nomor HP ke tabel 'user_profiles'
                DB::table('user_profiles')->insert([
                    'user_id'      => $adminUser->id,
                    'full_name'    => $nama,
                    'phone_number' => $noHpClean,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }
        }

        fclose($fileHandle);

        $this->command->info('Seeding sukses!');
    }
}

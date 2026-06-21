<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;

class UserKasirCsvSeeder extends Seeder
{
    /**
     * Run the database seeds (REAL INSERT MODE).
     */
    public function run(): void
    {
        // Path menuju lokasi file CSV baru dari Google Form
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
            $userIds = DB::table('users')->whereIn('email', $emails)->pluck('id');

            if ($userIds->isNotEmpty()) {
                $this->command->warn("♻️ Menghapus " . $userIds->count() . " data user lama yang konflik agar tidak terjadi duplicate entry...");

                // 1. Hapus relasi role Spatie
                DB::table('model_has_roles')
                    ->whereIn('model_id', $userIds)
                    ->where('model_type', User::class)
                    ->delete();

                // 2. Hapus profile di user_profiles
                DB::table('user_profiles')->whereIn('user_id', $userIds)->delete();

                // 3. Hapus user utama di tabel users
                DB::table('users')->whereIn('id', $userIds)->delete();
            }
        }


        // ================= TAHAP 3: PROSES SEEDING DATA BARU KE DATABASE =================
        $fileHandle = fopen($csvFile, 'r');
        $isHeader = true;
        $suksesCount = 0;

        $this->command->info("🚀 Memulai proses insert data nyata ke database...");

        while (($row = fgetcsv($fileHandle, 1000, ',')) !== FALSE) {
            if ($isHeader) {
                $isHeader = false;
                continue;
            }

            $cabangName = trim($row[1] ?? '');
            $nama       = trim($row[2] ?? '');
            $email      = trim($row[3] ?? '');
            $noHpRaw    = trim($row[4] ?? '');
            $posisiRaw  = trim($row[5] ?? '');

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

            // Ambil branch_id secara otomatis berdasarkan nama utuh dari CSV
            $branch = DB::table('branches')->where('name', $cabangName)->first();
            $branchId = $branch ? $branch->id : null;

            // Ambil warehouse_id secara otomatis berdasarkan nama utuh dari CSV
            $warehouse = DB::table('warehouses')->where('name', $cabangName)->first();
            $warehouseId = $warehouse ? $warehouse->id : null;

            $adminUser = User::create([
                'name'              => $nama,
                'email'             => $email,
                'password'          => Hash::make('password123'),
                'branch_id'         => $branchId,
                'warehouse_id'      => $warehouseId,
                'business_unit_id'  => 2,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);

            if ($adminUser) {
                // LOGIKA PENENTUAN ROLE DINAMIS
                $roleName = 'kasir_sju'; // Default fallback

                $posisi = strtolower($posisiRaw);
                if ($posisi === 'spv') {
                    $roleName = 'bm';
                } elseif ($posisi === 'bm') {
                    $roleName = 'bm';
                } elseif ($posisi === 'fl') {
                    $roleName = 'fl';
                } elseif ($posisi === 'kasir') {
                    $roleName = 'kasir_sju';
                }

                // Assign role Spatie ke user yang berhasil dibuat
                $adminUser->assignRole($roleName);

                // Insert data nomor HP ke tabel 'user_profiles'
                DB::table('user_profiles')->insert([
                    'user_id'      => $adminUser->id,
                    'full_name'    => $nama,
                    'phone_number' => $noHpClean,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);

                $suksesCount++;
            }
        }

        fclose($fileHandle);

        $this->command->info("------------------------------------------------------------");
        $this->command->info("✅ Seeding Berhasil! Sebanyak {$suksesCount} data akun GSK telah disimpan permanen ke database.");
        $this->command->info("🔑 Semua akun diset ke Business Unit ID: 2 & Password default: 123");
    }
}

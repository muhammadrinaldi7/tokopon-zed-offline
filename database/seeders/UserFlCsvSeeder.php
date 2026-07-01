<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;

class UserFlCsvSeeder extends Seeder
{
    /**
     * Run the database seeds (REAL INSERT MODE).
     */
    public function run(): void
    {
        $csvFile = database_path('seeders/csv/users_fl.csv');

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
            if (!empty($row[2])) {
                $emails[] = trim($row[2]);
            }
        }
        fclose($fileHandle);

        // ================= TAHAP 2: ROLLBACK / BERSIHKAN DATA LAMA =================
        if (!empty($emails)) {
            $userIds = DB::table('users')->whereIn('email', $emails)->pluck('id');

            if ($userIds->isNotEmpty()) {
                $this->command->warn("♻️ Menghapus " . $userIds->count() . " data user lama yang konflik agar tidak terjadi duplicate entry...");

                DB::table('model_has_roles')
                    ->whereIn('model_id', $userIds)
                    ->where('model_type', User::class)
                    ->delete();

                DB::table('user_profiles')->whereIn('user_id', $userIds)->delete();
                DB::table('users')->whereIn('id', $userIds)->delete();
            }
        }

        // ================= TAHAP 3: PROSES SEEDING DATA BARU KE DATABASE =================
        $fileHandle = fopen($csvFile, 'r');
        $isHeader = true;
        $suksesCount = 0;

        $this->command->info("🚀 Memulai proses insert data nyata FL/Kasir ke database...");

        while (($row = fgetcsv($fileHandle, 1000, ',')) !== FALSE) {
            if ($isHeader) {
                $isHeader = false;
                continue;
            }

            $cabangName = trim($row[1] ?? '');
            $email      = trim($row[2] ?? '');
            $posisiRaw  = trim($row[3] ?? '');

            if (empty($email)) {
                continue;
            }

            // Fix typo email .gmail.com menjadi @gmail.com
            if (!str_contains($email, '@') && str_contains($email, '.gmail.com')) {
                $email = str_replace('.gmail.com', '@gmail.com', $email);
            }

            // Generate nama dari bagian depan email
            $namaRaw = explode('@', $email)[0];
            $nama = ucwords(str_replace(['.', '_', '-'], ' ', $namaRaw));

            $branch = DB::table('branches')->where('name', $cabangName)->first();
            $branchId = $branch ? $branch->id : null;

            $warehouse = DB::table('warehouses')->where('name', $cabangName)->first();
            $warehouseId = $warehouse ? $warehouse->id : null;

            $adminUser = User::create([
                'name'              => $nama,
                'email'             => $email,
                'password'          => Hash::make('password123'),
                'branch_id'         => $branchId,
                'warehouse_id'      => $warehouseId,
                'business_unit_id'  => 1,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);

            if ($adminUser) {
                // Penentuan Role Dinamis (FL -> fl_sju)
                $roleName = 'fl_sju';

                $posisi = strtolower($posisiRaw);
                if ($posisi === 'spv' || $posisi === 'bm') {
                    $roleName = 'bm';
                } elseif ($posisi === 'fl') {
                    $roleName = 'fl_sju';
                } elseif ($posisi === 'kasir') {
                    $roleName = 'kasir_sju';
                }

                $adminUser->assignRole($roleName);

                DB::table('user_profiles')->insert([
                    'user_id'      => $adminUser->id,
                    'full_name'    => $nama,
                    'phone_number' => '',
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);

                $suksesCount++;
            }
        }

        fclose($fileHandle);

        $this->command->info("------------------------------------------------------------");
        $this->command->info("✅ Seeding Berhasil! Sebanyak {$suksesCount} data akun baru telah disimpan.");
    }
}

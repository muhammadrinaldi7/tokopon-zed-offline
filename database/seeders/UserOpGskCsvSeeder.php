<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use Spatie\Permission\Models\Role;

class UserOpGskCsvSeeder extends Seeder
{
    /**
     * Run the database seeds (REAL INSERT MODE).
     */
    public function run(): void
    {
        $csvFile = database_path('seeders/csv/user-op-gsk.csv');

        if (!file_exists($csvFile)) {
            $this->command->error("File CSV tidak ditemukan di lokasi: {$csvFile}");
            return;
        }

        // ================= TAHAP 1: COLLECT EMAIL DARI CSV =================
        $fileHandle = fopen($csvFile, 'r');
        $isHeader = true;
        $emails = [];
        $seenEmails = [];

        while (($row = fgetcsv($fileHandle, 1000, ',')) !== FALSE) {
            if ($isHeader) {
                $isHeader = false;
                continue;
            }

            $email = trim($row[3] ?? '');
            if (empty($email)) {
                continue;
            }

            // Fix typo format email jika ada
            if (!str_contains($email, '@') && str_contains($email, '.gmail.com')) {
                $email = str_replace('.gmail.com', '@gmail.com', $email);
            }
            $email = strtolower($email);

            if (!in_array($email, $seenEmails)) {
                $seenEmails[] = $email;
                $emails[] = $email;
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
        $processedEmails = [];

        $this->command->info("🚀 Memulai proses insert data operasional GSK ke database...");

        // Dapatkan business_unit_id untuk GSK (default code 'second' / fallback ID 2)
        $businessUnit = DB::table('business_units')->where('code', 'second')->orWhere('code', 'gsk')->first();
        $businessUnitId = $businessUnit ? $businessUnit->id : 2;

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

            if (empty($email)) {
                continue;
            }

            // Fix typo format email jika ada
            if (!str_contains($email, '@') && str_contains($email, '.gmail.com')) {
                $email = str_replace('.gmail.com', '@gmail.com', $email);
            }
            $email = strtolower($email);

            // Jika ada duplikasi data di CSV, ambil yang pertama dan abaikan yang kedua/seterusnya
            if (in_array($email, $processedEmails)) {
                $this->command->warn("⚠️ Melewati entri duplikat untuk email: {$email}");
                continue;
            }
            $processedEmails[] = $email;

            // Jika nama kosong, fallback ke ekstrak nama dari email
            if (empty($nama)) {
                $namaRaw = explode('@', $email)[0];
                $nama = ucwords(str_replace(['.', '_', '-'], ' ', $namaRaw));
            }

            // Logika merapikan nomor HP (bersihkan karakter non-angka / hidden unicode)
            $noHpClean = preg_replace('/[^0-9]/', '', $noHpRaw);
            if (Str::startsWith($noHpClean, '628')) {
                $noHpClean = '0' . substr($noHpClean, 2);
            } elseif (Str::startsWith($noHpClean, '8')) {
                $noHpClean = '0' . $noHpClean;
            }

            // Ambil branch_id secara otomatis berdasarkan nama cabang dari CSV
            $branch = DB::table('branches')->where('name', $cabangName)->first();
            $branchId = $branch ? $branch->id : null;

            // Ambil warehouse_id secara otomatis berdasarkan nama cabang dari CSV
            $warehouse = DB::table('warehouses')->where('name', $cabangName)->first();
            $warehouseId = $warehouse ? $warehouse->id : null;

            $adminUser = User::create([
                'name'              => $nama,
                'email'             => $email,
                'password'          => Hash::make('password123'),
                'branch_id'         => $branchId,
                'warehouse_id'      => $warehouseId,
                'business_unit_id'  => $businessUnitId,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);

            if ($adminUser) {
                // Penentuan Role GSK
                $roleName = strtolower(trim($posisiRaw));
                if (empty($roleName)) {
                    $roleName = 'fl_gsk'; // Default fallback jika kosong
                }

                // Pastikan role Spatie sudah terdaftar
                Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

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
        $this->command->info("✅ Seeding Berhasil! Sebanyak {$suksesCount} data akun GSK telah disimpan.");
        $this->command->info("🔑 Semua akun diset ke Business Unit ID: {$businessUnitId} & Password default: password123");
    }
}

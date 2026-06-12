<?php

namespace App\Livewire\Admin\Accurate;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Services\AccurateService;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

use Livewire\Attributes\On;

class CustomerAccurateManagement extends Component
{
    public $syncStatus = 'idle'; // idle, running, completed, error
    public $syncMessage = '';
    public $syncImportedCount = 0;
    public $syncSkippedCount = 0;
    public $databaseSource = 'syihab';
    public $syncCurrentPage = 1;

    public function startSync()
    {
        $this->syncStatus = 'running';
        $this->syncMessage = 'Memulai proses sinkronisasi pelanggan dari Accurate...';
        $this->syncImportedCount = 0;
        $this->syncSkippedCount = 0;
        // Kita tidak mereset syncCurrentPage di sini agar user bisa melanjutkannya
        
        $this->dispatch('trigger-next-page');
    }

    #[On('trigger-next-page')]
    public function processNextPage(AccurateService $accurateService)
    {
        if ($this->syncStatus !== 'running') return;

        $defaultPassword = Hash::make('password123');

        try {
            $response = $accurateService->fetchCustomers($this->syncCurrentPage, $this->databaseSource);

            $customers = $response['d'] ?? [];
            $bu = \App\Models\BusinessUnit::where('code', $this->databaseSource)->first();
            if (!$bu) throw new \Exception('Business Unit tidak ditemukan.');

            foreach ($customers as $cust) {
                    $customerNo = $cust['customerNo'] ?? null;
                    $name = $cust['name'] ?? null;
                    $email = $cust['email'] ?? null;
                    $mobilePhone = $cust['mobilePhone'] ?? null;
                    $accurateId = $cust['id'] ?? null;

                    if (!$customerNo || !$name) {
                        $this->syncSkippedCount++;
                        continue;
                    }

                    // 1. Cari berdasarkan Customer No di pivot untuk Unit Usaha ini
                    $user = User::whereHas('accurateCustomers', function($q) use ($customerNo, $bu) {
                        $q->where('accurate_customer_no', $customerNo)
                          ->where('business_unit_id', $bu->id);
                    })->first();

                    if (!$user) {
                        // 2. Prioritas Utama: Pengecekan via Nomor HP
                        if (!empty($mobilePhone)) {
                            $user = User::whereHas('profile', function($q) use ($mobilePhone) {
                                // Hapus karakter non-digit untuk pencocokan yang lebih akurat
                                $cleanPhone = preg_replace('/[^0-9]/', '', $mobilePhone);
                                $q->where('phone_number', 'like', '%' . $cleanPhone . '%');
                            })->first();
                        }

                        // 3. Jika Nomor HP tidak ketemu (atau kosong), coba Pengecekan via Email
                        if (!$user && !empty($email)) {
                            $user = User::where('email', $email)->first();
                        }

                        // 4. Jika tetap tidak ketemu, baru kita buat User Baru
                        if (!$user) {
                            $userEmail = !empty($email) ? $email : ($customerNo . '@customer.zpos.local');

                            // Periksa apakah email buatan/asli sudah dipakai (mencegah bentrok langka)
                            $existingEmail = User::where('email', $userEmail)->first();
                            if ($existingEmail) {
                                $userEmail = $customerNo . '_' . uniqid() . '@customer.zpos.local';
                            }

                            $user = User::create([
                                'name' => $name,
                                'email' => $userEmail,
                                'password' => $defaultPassword,
                            ]);

                            try {
                                $user->assignRole('user');
                            } catch (\Exception $e) {
                                // Abaikan jika role customer belum ada
                            }
                        } else {
                            // Jika sudah ada (ketemu dari email/HP), kita biarkan saja agar tidak menimpa nama yang sudah diedit user
                            // Hanya update namanya jika di Tokopon masih nama default/kosong
                            if (empty($user->name) || str_starts_with($user->email, 'CUST')) {
                                $user->update(['name' => $name]);
                            }
                        }
                    }

                    // Update or create pivot relation
                    $user->accurateCustomers()->updateOrCreate(
                        ['business_unit_id' => $bu->id],
                        [
                            'accurate_customer_id' => str_replace('"', '', $accurateId),
                            'accurate_customer_no' => str_replace('"', '', $customerNo)
                        ]
                    );

                    // 2. Simpan profil (hanya untuk nomor HP jika ada)
                    if ($mobilePhone) {
                        UserProfile::updateOrCreate(
                            ['user_id' => $user->id],
                            [
                                'full_name' => $name,
                                'phone_number' => $mobilePhone
                            ]
                        );
                    }

                    $this->syncImportedCount++;
                }

            // Accurate pagination limit is 100 per page. If we got less than 100, it's the last page.
            if (count($customers) < 100) {
                $this->syncStatus = 'completed';
                $this->syncMessage = "Proses selesai! $this->syncImportedCount pelanggan berhasil ditarik.";
                $this->dispatch('toast', title: 'Berhasil', message: 'Sinkronisasi pelanggan selesai.', type: 'success');
            } else {
                $this->syncCurrentPage++;
                $this->syncMessage = "Menarik data halaman ke-{$this->syncCurrentPage}...";
                $this->dispatch('trigger-next-page');
            }
        } catch (\Exception $e) {
            Log::error('Customer Sync Error: ' . $e->getMessage());
            $this->syncStatus = 'error';
            $this->syncMessage = 'Terjadi kesalahan: ' . $e->getMessage();
            $this->dispatch('toast', title: 'Error', message: 'Gagal melakukan sinkronisasi.', type: 'error');
        }
    }

    #[Layout('layouts.admin')]
    public function render()
    {
        $customers = User::whereHas('accurateCustomers')->with(['profile', 'accurateCustomers.businessUnit'])->paginate(10);

        return view('livewire.admin.accurate.customer-accurate-management', compact('customers'));
    }
}

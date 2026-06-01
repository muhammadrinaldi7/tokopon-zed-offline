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

                    // 1. Cari berdasarkan Customer No
                    $user = User::where('accurate_customer_no', $customerNo)->first();

                    if (!$user) {
                        // Jika tidak ada, buat User Baru
                        // Generate email unik jika kosong, untuk mencegah bentrok email duplikat
                        $userEmail = !empty($email) ? $email : ($customerNo . '@customer.zpos.local');

                        // Periksa apakah email buatan/asli sudah dipakai (mencegah bentrok)
                        $existingEmail = User::where('email', $userEmail)->first();
                        if ($existingEmail) {
                            $userEmail = $customerNo . '_' . uniqid() . '@customer.zpos.local';
                        }

                        $user = User::create([
                            'name' => $name,
                            'email' => $userEmail,
                            'password' => $defaultPassword,
                            'accurate_customer_no' => $customerNo,
                            'accurate_customer_id' => $accurateId
                        ]);

                        try {
                            $user->assignRole('user');
                        } catch (\Exception $e) {
                            // Abaikan jika role customer belum ada di database
                        }
                    } else {
                        // Jika sudah ada, cukup update namanya jika perlu
                        $user->update([
                            'name' => $name,
                        ]);
                    }

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
        $customers = User::whereNotNull('accurate_customer_no')->with('profile')->paginate(10);

        return view('livewire.admin.accurate.customer-accurate-management', compact('customers'));
    }
}

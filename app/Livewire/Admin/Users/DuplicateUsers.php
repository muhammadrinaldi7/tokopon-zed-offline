<?php

namespace App\Livewire\Admin\Users;

use App\Models\Order;
use App\Models\SellPhone;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin', ['title' => 'Audit & Duplikat Pengguna - TokoPun'])]
class DuplicateUsers extends Component
{
    use WithPagination;

    public $search = '';
    public $orderFilter = 'all'; // 'all', 'has_transactions', 'has_orders', 'has_sell_phones', 'no_transactions', 'conflict_transactions'
    public $perPage = 15;

    // Modal Pesanan (Penjualan / Orders)
    public $isOrdersModalOpen = false;
    public $selectedUserId = null;
    public $selectedUserName = '';
    public $selectedUserEmail = '';
    public $selectedUserPhone = '';
    public $selectedUserOrders = [];

    // Modal Pembelian HP (Sell Phones / Buyback)
    public $isSellPhonesModalOpen = false;
    public $selectedUserSellPhones = [];

    // Modal Penggabungan Akun (Merge Users)
    public $isMergeModalOpen = false;
    public $selectedMergePhone = '';
    public $targetUserId = null;
    public $mergeCandidateUsers = [];

    // Bulk Merge Checkboxes (Current Page Selection)
    public $selectedPhones = [];
    public $selectAllOnPage = false;

    // Mass Bulk Cleaner Runner (0 Transactions Groups)
    public $isBulkCleanerModalOpen = false;
    public $bulkCleanerRunning = false;
    public $bulkTotalCleanGroups = 0;
    public $bulkProcessedCount = 0;
    public $bulkSuccessCount = 0;
    public $bulkSkippedCount = 0;
    public $bulkBatchSize = 100;

    protected $paginationTheme = 'tailwind';

    public function mount()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user || (!$user->can('manage-users') && !$user->hasAnyRole(['admin', 'superadmin']))) {
            return redirect('/admin/dashboard');
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
        $this->clearSelectedPhones();
    }

    public function updatingOrderFilter()
    {
        $this->resetPage();
        $this->clearSelectedPhones();
    }

    public function updatingPage()
    {
        $this->clearSelectedPhones();
    }

    /**
     * Ambil daftar ID akun pengguna yang merupakan staf / karyawan operasional toko
     */
    protected function getStaffUserIds(): array
    {
        return Cache::remember('staff_user_ids', 120, function () {
            return User::where(function ($q) {
                $q->whereHas('roles', fn($r) => $r->whereNotIn('name', ['customer', 'user']))
                    ->orWhereExists(fn($sub) => $sub->select(DB::raw(1))->from('employes')->whereColumn('employes.user_id', 'users.id'))
                    ->orWhereExists(fn($sub) => $sub->select(DB::raw(1))->from('cashier_shifts')->whereColumn('cashier_shifts.user_id', 'users.id'));
            })->pluck('id')->toArray();
        });
    }

    /**
     * Tampilkan notifikasi alert & toast di antarmuka admin
     */
    protected function notify(string $type, string $message, string $title = '')
    {
        if (empty($title)) {
            $title = $type === 'success' ? 'Berhasil' : ($type === 'error' ? 'Gagal' : 'Perhatian');
        }

        // 1. Toast notification floating (x-toast di layouts/admin.blade.php)
        $this->dispatch('toast', title: $title, message: $message, type: $type);

        // 2. Livewire ToastNotification component
        $this->dispatch('show-toast', type: $type, message: $message);

        // 3. Fallback inline admin-alert
        $this->dispatch('admin-alert', type: $type, message: $message);
    }

    public function openOrdersModal($userId)
    {
        $user = User::with(['profile'])->find($userId);
        if (!$user) {
            $this->notify('error', 'Data pengguna tidak ditemukan.');
            return;
        }

        $this->selectedUserId = $user->id;
        $this->selectedUserName = $user->name;
        $this->selectedUserEmail = $user->email;
        $this->selectedUserPhone = $user->profile->phone_number ?? '-';

        $this->selectedUserOrders = Order::with(['businessUnit'])
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number ?? ('ORD-' . $order->id),
                    'order_date' => $order->order_date ? $order->order_date->format('d M Y') : ($order->created_at ? $order->created_at->format('d M Y H:i') : '-'),
                    'grand_total' => $order->grand_total ?? 0,
                    'order_status' => $order->order_status ?? 'UNKNOWN',
                    'business_unit' => $order->businessUnit->name ?? '-',
                ];
            })
            ->toArray();

        $this->isOrdersModalOpen = true;
    }

    public function closeOrdersModal()
    {
        $this->isOrdersModalOpen = false;
        $this->selectedUserId = null;
        $this->selectedUserName = '';
        $this->selectedUserEmail = '';
        $this->selectedUserPhone = '';
        $this->selectedUserOrders = [];
    }

    public function openSellPhonesModal($userId)
    {
        $user = User::with(['profile'])->find($userId);
        if (!$user) {
            $this->notify('error', 'Data pengguna tidak ditemukan.');
            return;
        }

        $this->selectedUserId = $user->id;
        $this->selectedUserName = $user->name;
        $this->selectedUserEmail = $user->email;
        $this->selectedUserPhone = $user->profile->phone_number ?? '-';

        $this->selectedUserSellPhones = SellPhone::with(['businessUnit'])
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->get()
            ->map(function ($item) {
                $storage = !empty($item->phone_storage) ? $item->phone_storage : '';
                $ram = !empty($item->phone_ram) ? ($item->phone_ram . (!str_contains(strtolower($item->phone_ram), 'gb') ? 'GB' : '')) : '';
                $specs = trim($ram . ($ram && $storage ? ' / ' : '') . $storage);

                return [
                    'id' => $item->id,
                    'invoice_number' => $item->invoice_number ?? ('SP-' . $item->id),
                    'phone_brand' => $item->phone_brand ?? '-',
                    'phone_model' => $item->phone_model ?? '-',
                    'phone_specs' => $specs ?: '-',
                    'imei' => $item->imei ?? '-',
                    'appraised_value' => $item->appraised_value ?? 0,
                    'status' => $item->status ?? 'UNKNOWN',
                    'business_unit' => $item->businessUnit->name ?? '-',
                    'created_at' => $item->created_at ? $item->created_at->format('d M Y H:i') : '-',
                ];
            })
            ->toArray();

        $this->isSellPhonesModalOpen = true;
    }

    public function closeSellPhonesModal()
    {
        $this->isSellPhonesModalOpen = false;
        $this->selectedUserId = null;
        $this->selectedUserName = '';
        $this->selectedUserEmail = '';
        $this->selectedUserPhone = '';
        $this->selectedUserSellPhones = [];
    }

    /**
     * Membuka modal penggabungan akun untuk kelompok nomor telepon tertentu
     */
    public function openMergeModal($phoneNumber)
    {
        $this->selectedMergePhone = $phoneNumber;

        $staffIds = $this->getStaffUserIds();

        $users = User::with([
            'profile',
            'accurateCustomers.businessUnit',
            'accurateVendors.businessUnit',
            'roles',
        ])
            ->whereNotIn('id', $staffIds)
            ->withCount(['orders', 'sellPhones'])
            ->withSum('orders as orders_total_amount', 'grand_total')
            ->withSum('sellPhones as sell_phones_total_amount', 'appraised_value')
            ->whereHas('profile', function ($q) use ($phoneNumber) {
                $q->where('phone_number', $phoneNumber);
            })
            ->orderByDesc('orders_count')
            ->orderByDesc('sell_phones_count')
            ->orderBy('created_at')
            ->get();

        if ($users->count() < 2) {
            $this->notify('error', 'Nomor ini tidak memiliki akun duplikat untuk digabungkan.');
            return;
        }

        $this->mergeCandidateUsers = $users->map(function ($u) {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'created_at' => $u->created_at ? $u->created_at->format('d M Y') : '-',
                'orders_count' => $u->orders_count ?? 0,
                'orders_total_amount' => $u->orders_total_amount ?? 0,
                'sell_phones_count' => $u->sell_phones_count ?? 0,
                'sell_phones_total_amount' => $u->sell_phones_total_amount ?? 0,
                'accurate_customers' => $u->accurateCustomers->map(fn($ac) => [
                    'no' => $ac->accurate_customer_no,
                    'bu' => $ac->businessUnit->name ?? '',
                ])->toArray(),
                'accurate_vendors' => $u->accurateVendors->map(fn($av) => [
                    'no' => $av->accurate_vendor_no,
                    'bu' => $av->businessUnit->name ?? '',
                ])->toArray(),
                'is_staff' => false,
            ];
        })->toArray();

        // Default target: akun pertama (yang memiliki order / transaksi terbanyak)
        $defaultTarget = $users->first();
        $this->targetUserId = $defaultTarget ? $defaultTarget->id : null;

        $this->isMergeModalOpen = true;
    }

    public function closeMergeModal()
    {
        $this->isMergeModalOpen = false;
        $this->selectedMergePhone = '';
        $this->targetUserId = null;
        $this->mergeCandidateUsers = [];
    }

    /**
     * Eksekusi penggabungan akun manual dari modal single merge
     */
    public function executeMerge()
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();
        if (!$currentUser || (!$currentUser->can('manage-users') && !$currentUser->hasAnyRole(['admin', 'superadmin']))) {
            $this->notify('error', 'Anda tidak memiliki izin untuk menggabungkan akun.');
            return;
        }

        if (empty($this->targetUserId) || empty($this->selectedMergePhone)) {
            $this->notify('error', 'Silakan pilih Akun Utama terlebih dahulu.');
            return;
        }

        $res = $this->mergeSingleGroup($this->selectedMergePhone, (int) $this->targetUserId);

        if ($res['success']) {
            $this->closeMergeModal();
            $this->notify('success', "Berhasil menggabungkan {$res['merged_count']} akun ke Akun Utama \"{$res['target_name']}\"!", 'Penggabungan Berhasil');
        } else {
            $this->notify('error', 'Gagal menggabungkan akun: ' . ($res['reason'] ?? 'Kesalahan tidak diketahui'));
        }
    }

    /**
     * Logika inti penggabungan 1 kelompok nomor telepon (reusable untuk single merge & bulk merge)
     */
    public function mergeSingleGroup(string $phoneNumber, ?int $explicitTargetUserId = null): array
    {
        $staffIds = $this->getStaffUserIds();

        $users = User::with(['profile'])
            ->whereNotIn('id', $staffIds)
            ->withCount(['orders', 'sellPhones'])
            ->whereHas('profile', fn($q) => $q->where('phone_number', $phoneNumber))
            ->orderByDesc('orders_count')
            ->orderByDesc('sell_phones_count')
            ->orderBy('created_at')
            ->get();

        if ($users->count() < 2) {
            return ['success' => false, 'skipped' => true, 'reason' => 'Kurang dari 2 akun terdaftar'];
        }

        if ($explicitTargetUserId !== null) {
            $targetUser = $users->firstWhere('id', $explicitTargetUserId);
            if (!$targetUser) {
                return ['success' => false, 'skipped' => false, 'reason' => 'Akun Utama yang dipilih tidak valid'];
            }
        } else {
            // Pemilihan target otomatis untuk bulk merge:
            // Jika ada lebih dari 1 akun yang sama-sama punya transaksi, skip (konflik)
            $usersWithTransactions = $users->filter(fn($u) => $u->orders_count > 0 || $u->sell_phones_count > 0);
            if ($usersWithTransactions->count() > 1) {
                return ['success' => false, 'skipped' => true, 'reason' => 'Konflik: Lebih dari 1 akun memiliki riwayat transaksi'];
            }

            // Target adalah akun pertama (terbanyak transaksi atau terdaftar paling awal)
            $targetUser = $users->first();
        }

        $targetUserId = $targetUser->id;
        $sourceUserIds = $users->where('id', '!=', $targetUserId)->pluck('id')->toArray();

        if (empty($sourceUserIds)) {
            return ['success' => false, 'skipped' => true, 'reason' => 'Tidak ada akun sumber untuk digabungkan'];
        }

        // Safety Guard tambahan: pastikan tidak ada staf
        $staffConflict = User::whereIn('id', $sourceUserIds)
            ->where(function ($q) {
                $q->whereHas('roles', fn($r) => $r->whereNotIn('name', ['customer', 'user']))
                    ->orWhereExists(fn($sub) => $sub->select(DB::raw(1))->from('employes')->whereColumn('employes.user_id', 'users.id'))
                    ->orWhereExists(fn($sub) => $sub->select(DB::raw(1))->from('cashier_shifts')->whereColumn('cashier_shifts.user_id', 'users.id'));
            })
            ->first();

        if ($staffConflict) {
            return ['success' => false, 'skipped' => true, 'reason' => "Akun {$staffConflict->name} terdaftar sebagai staf"];
        }

        try {
            DB::transaction(function () use ($targetUserId, $sourceUserIds) {
                // 1. orders
                DB::table('orders')->whereIn('user_id', $sourceUserIds)->update(['user_id' => $targetUserId]);
                // 2. sell_phones
                DB::table('sell_phones')->whereIn('user_id', $sourceUserIds)->update(['user_id' => $targetUserId]);
                // 3. sell_phone_issues
                DB::table('sell_phone_issues')->whereIn('user_id', $sourceUserIds)->update(['user_id' => $targetUserId]);
                // 4. trade_ins
                DB::table('trade_ins')->whereIn('user_id', $sourceUserIds)->update(['user_id' => $targetUserId]);
                // 5. warranties
                DB::table('warranties')->whereIn('customer_user_id', $sourceUserIds)->update(['customer_user_id' => $targetUserId]);
                // 6. warranty_claims
                DB::table('warranty_claims')->whereIn('customer_user_id', $sourceUserIds)->update(['customer_user_id' => $targetUserId]);
                // 7. customer_deposits
                DB::table('customer_deposits')->whereIn('user_id', $sourceUserIds)->update(['user_id' => $targetUserId]);
                // 8. order_issues
                DB::table('order_issues')->whereIn('user_id', $sourceUserIds)->update(['user_id' => $targetUserId]);
                // 9. user_addresses
                DB::table('user_addresses')->whereIn('user_id', $sourceUserIds)->update(['user_id' => $targetUserId]);

                // 10. user_bank_accounts
                $targetBankAccounts = DB::table('user_bank_accounts')
                    ->where('user_id', $targetUserId)
                    ->pluck('account_number')
                    ->filter()
                    ->toArray();

                $sourceBankAccounts = DB::table('user_bank_accounts')->whereIn('user_id', $sourceUserIds)->get();
                foreach ($sourceBankAccounts as $sba) {
                    if (!empty($sba->account_number) && !in_array($sba->account_number, $targetBankAccounts)) {
                        DB::table('user_bank_accounts')->where('id', $sba->id)->update(['user_id' => $targetUserId]);
                        $targetBankAccounts[] = $sba->account_number;
                    } else {
                        DB::table('user_bank_accounts')->where('id', $sba->id)->delete();
                    }
                }

                // 11. product_reviews
                DB::table('product_reviews')->whereIn('user_id', $sourceUserIds)->update(['user_id' => $targetUserId]);

                // 12. conversations & messages
                DB::table('conversations')->whereIn('user_id', $sourceUserIds)->update(['user_id' => $targetUserId]);
                DB::table('messages')->whereIn('user_id', $sourceUserIds)->update(['user_id' => $targetUserId]);

                if (Schema::hasTable('agent_conversations')) {
                    DB::table('agent_conversations')->whereIn('user_id', $sourceUserIds)->update(['user_id' => $targetUserId]);
                }
                if (Schema::hasTable('agent_conversation_messages')) {
                    DB::table('agent_conversation_messages')->whereIn('user_id', $sourceUserIds)->update(['user_id' => $targetUserId]);
                }

                // 13. carts & cart_items
                $targetCart = DB::table('carts')->where('user_id', $targetUserId)->first();
                $sourceCarts = DB::table('carts')->whereIn('user_id', $sourceUserIds)->get();
                foreach ($sourceCarts as $sCart) {
                    if ($targetCart) {
                        DB::table('cart_items')->where('cart_id', $sCart->id)->update(['cart_id' => $targetCart->id]);
                        DB::table('carts')->where('id', $sCart->id)->delete();
                    } else {
                        DB::table('carts')->where('id', $sCart->id)->update(['user_id' => $targetUserId]);
                        $targetCart = DB::table('carts')->where('user_id', $targetUserId)->first();
                    }
                }

                // 14. user_accurate_customers
                $targetAccurateBUs = DB::table('user_accurate_customers')
                    ->where('user_id', $targetUserId)
                    ->pluck('business_unit_id')
                    ->toArray();

                $sourceAccurateCustomers = DB::table('user_accurate_customers')->whereIn('user_id', $sourceUserIds)->get();
                foreach ($sourceAccurateCustomers as $sac) {
                    if (!in_array($sac->business_unit_id, $targetAccurateBUs)) {
                        DB::table('user_accurate_customers')->where('id', $sac->id)->update(['user_id' => $targetUserId]);
                        $targetAccurateBUs[] = $sac->business_unit_id;
                    } else {
                        DB::table('user_accurate_customers')->where('id', $sac->id)->delete();
                    }
                }

                // 15. user_accurate_vendors
                $targetVendorBUs = DB::table('user_accurate_vendors')
                    ->where('user_id', $targetUserId)
                    ->pluck('business_unit_id')
                    ->toArray();

                $sourceAccurateVendors = DB::table('user_accurate_vendors')->whereIn('user_id', $sourceUserIds)->get();
                foreach ($sourceAccurateVendors as $sav) {
                    if (!in_array($sav->business_unit_id, $targetVendorBUs)) {
                        DB::table('user_accurate_vendors')->where('id', $sav->id)->update(['user_id' => $targetUserId]);
                        $targetVendorBUs[] = $sav->business_unit_id;
                    } else {
                        DB::table('user_accurate_vendors')->where('id', $sav->id)->delete();
                    }
                }

                // 16. user_profiles
                $targetProfile = DB::table('user_profiles')->where('user_id', $targetUserId)->first();
                $sourceProfiles = DB::table('user_profiles')->whereIn('user_id', $sourceUserIds)->get();
                if ($targetProfile) {
                    $enrich = [];
                    if (empty($targetProfile->domisili)) {
                        $firstDom = $sourceProfiles->first(fn($p) => !empty($p->domisili))?->domisili;
                        if ($firstDom) $enrich['domisili'] = $firstDom;
                    }
                    if (empty($targetProfile->birth_date)) {
                        $firstBirth = $sourceProfiles->first(fn($p) => !empty($p->birth_date))?->birth_date;
                        if ($firstBirth) $enrich['birth_date'] = $firstBirth;
                    }
                    if (empty($targetProfile->gender)) {
                        $firstGender = $sourceProfiles->first(fn($p) => !empty($p->gender))?->gender;
                        if ($firstGender) $enrich['gender'] = $firstGender;
                    }
                    if (!empty($enrich)) {
                        DB::table('user_profiles')->where('id', $targetProfile->id)->update($enrich);
                    }
                }
                DB::table('user_profiles')->whereIn('user_id', $sourceUserIds)->delete();

                // 17. Roles & sessions & delete
                DB::table('model_has_roles')->where('model_type', User::class)->whereIn('model_id', $sourceUserIds)->delete();
                DB::table('model_has_permissions')->where('model_type', User::class)->whereIn('model_id', $sourceUserIds)->delete();
                if (Schema::hasTable('sessions')) {
                    DB::table('sessions')->whereIn('user_id', $sourceUserIds)->delete();
                }
                DB::table('users')->whereIn('id', $sourceUserIds)->delete();
            });

            return [
                'success' => true,
                'skipped' => false,
                'merged_count' => count($sourceUserIds),
                'target_name' => $targetUser->name,
                'target_id' => $targetUserId,
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'skipped' => false, 'reason' => $e->getMessage()];
        }
    }

    /**
     * Pilih / Hapus semua centang pada halaman saat ini
     */
    public function toggleSelectAllOnPage(array $pagePhones)
    {
        if ($this->selectAllOnPage) {
            $this->selectedPhones = array_values(array_diff($this->selectedPhones, $pagePhones));
            $this->selectAllOnPage = false;
        } else {
            $this->selectedPhones = array_values(array_unique(array_merge($this->selectedPhones, $pagePhones)));
            $this->selectAllOnPage = true;
        }
    }

    public function clearSelectedPhones()
    {
        $this->selectedPhones = [];
        $this->selectAllOnPage = false;
    }

    /**
     * Eksekusi penggabungan untuk kelompok-kelompok nomor telepon yang dicentang
     */
    public function executeSelectedBulkMerge()
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();
        if (!$currentUser || (!$currentUser->can('manage-users') && !$currentUser->hasAnyRole(['admin', 'superadmin']))) {
            $this->notify('error', 'Anda tidak memiliki izin untuk menggabungkan akun.');
            return;
        }

        if (empty($this->selectedPhones)) {
            $this->notify('error', 'Tidak ada kelompok nomor telepon yang dipilih.');
            return;
        }

        $success = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($this->selectedPhones as $phone) {
            $res = $this->mergeSingleGroup($phone);
            if ($res['success']) {
                $success++;
            } elseif ($res['skipped']) {
                $skipped++;
            } else {
                $failed++;
            }
        }

        $this->clearSelectedPhones();

        if ($success > 0 && $skipped === 0 && $failed === 0) {
            $this->notify('success', "Berhasil menggabungkan seluruh {$success} kelompok nomor telepon terpilih!", 'Bulk Merge Berhasil');
        } elseif ($success > 0 && $skipped > 0) {
            $this->notify('warning', "Berhasil menggabungkan {$success} kelompok. {$skipped} kelompok dilewati karena terdapat konflik transaksi multi-akun.", 'Sebagian Digabungkan');
        } elseif ($success === 0 && $skipped > 0) {
            $this->notify('warning', "Semua kelompok terpilih ({$skipped}) dilewati karena terdapat riwayat transaksi yang memerlukan review manual.", 'Tidak Ada yang Digabung');
        } else {
            $this->notify('error', 'Gagal memproses penggabungan beberapa kelompok nomor telepon terpilih.');
        }
    }

    // ─── MASS BULK CLEANER RUNNER (0 TRANSAKSI) ───

    public function openBulkCleanerModal()
    {
        $stats = $this->getStats();
        $this->bulkTotalCleanGroups = $stats['phonesWithoutTransactionsCount'];
        $this->bulkProcessedCount = 0;
        $this->bulkSuccessCount = 0;
        $this->bulkSkippedCount = 0;
        $this->bulkCleanerRunning = false;
        $this->isBulkCleanerModalOpen = true;
    }

    public function closeBulkCleanerModal()
    {
        $this->bulkCleanerRunning = false;
        $this->isBulkCleanerModalOpen = false;
    }

    public function startBulkCleaner()
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();
        if (!$currentUser || (!$currentUser->can('manage-users') && !$currentUser->hasAnyRole(['admin', 'superadmin']))) {
            $this->notify('error', 'Anda tidak memiliki izin untuk menjalankan pembersihan massal.');
            return;
        }

        $this->bulkCleanerRunning = true;
        $this->dispatch('trigger-next-cleaner-batch');
    }

    public function stopBulkCleaner()
    {
        $this->bulkCleanerRunning = false;
    }

    public function processNextBulkCleanerBatch()
    {
        if (!$this->bulkCleanerRunning) {
            return;
        }

        $staffIds = $this->getStaffUserIds();

        // Ambil batch nomor telepon 0 transaksi berikutnya
        $cleanPhones = DB::table('user_profiles as up')
            ->select('up.phone_number')
            ->whereNotNull('up.phone_number')
            ->where('up.phone_number', '!=', '')
            ->where('up.phone_number', '!=', '-')
            ->where('up.phone_number', '!=', '0')
            ->whereNotIn('up.user_id', $staffIds)
            ->whereNotIn('up.phone_number', function ($sub) use ($staffIds) {
                $sub->select('up_sub.phone_number')
                    ->from('user_profiles as up_sub')
                    ->join('orders as o_sub', 'o_sub.user_id', '=', 'up_sub.user_id')
                    ->whereNotIn('up_sub.user_id', $staffIds)
                    ->groupBy('up_sub.phone_number');
            })
            ->whereNotIn('up.phone_number', function ($sub) use ($staffIds) {
                $sub->select('up_sub.phone_number')
                    ->from('user_profiles as up_sub')
                    ->join('sell_phones as sp_sub', 'sp_sub.user_id', '=', 'up_sub.user_id')
                    ->whereNotIn('up_sub.user_id', $staffIds)
                    ->groupBy('up_sub.phone_number');
            })
            ->groupBy('up.phone_number')
            ->havingRaw('COUNT(up.id) > 1')
            ->limit($this->bulkBatchSize)
            ->pluck('phone_number')
            ->toArray();

        if (empty($cleanPhones)) {
            $this->bulkCleanerRunning = false;
            $this->notify('success', "Pembersihan massal selesai! Berhasil menggabungkan {$this->bulkSuccessCount} kelompok nomor telepon tanpa transaksi.", 'Pembersihan Selesai');
            return;
        }

        foreach ($cleanPhones as $phone) {
            $res = $this->mergeSingleGroup($phone);
            if ($res['success']) {
                $this->bulkSuccessCount++;
            } else {
                $this->bulkSkippedCount++;
            }
            $this->bulkProcessedCount++;
        }

        if ($this->bulkCleanerRunning) {
            $this->dispatch('trigger-next-cleaner-batch');
        }
    }

    /**
     * Hitung ringkasan statistik KPI (Mengecualikan akun staf/karyawan)
     */
    protected function getStats()
    {
        $staffIds = $this->getStaffUserIds();

        // Hitung total kelompok nomor HP duplikat (exclude kosong, null, dash, nol, dan staf)
        $totalDuplicatePhones = DB::table('user_profiles')
            ->select('phone_number')
            ->whereNotNull('phone_number')
            ->where('phone_number', '!=', '')
            ->where('phone_number', '!=', '-')
            ->where('phone_number', '!=', '0')
            ->whereNotIn('user_id', $staffIds)
            ->groupBy('phone_number')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();

        // Hitung total akun yang terlibat duplikasi (exclude staf)
        $totalDuplicateAccounts = DB::table('user_profiles')
            ->whereNotIn('user_id', $staffIds)
            ->whereIn('phone_number', function ($q) use ($staffIds) {
                $q->select('phone_number')
                    ->from('user_profiles')
                    ->whereNotNull('phone_number')
                    ->where('phone_number', '!=', '')
                    ->where('phone_number', '!=', '-')
                    ->where('phone_number', '!=', '0')
                    ->whereNotIn('user_id', $staffIds)
                    ->groupBy('phone_number')
                    ->havingRaw('COUNT(*) > 1');
            })
            ->count();

        // Hitung berapa nomor telepon duplikat yang setidaknya 1 akunnya memiliki order (exclude staf)
        $phonesWithOrdersCount = DB::table('user_profiles as up')
            ->join('orders as o', 'o.user_id', '=', 'up.user_id')
            ->whereNotNull('up.phone_number')
            ->where('up.phone_number', '!=', '')
            ->where('up.phone_number', '!=', '-')
            ->where('up.phone_number', '!=', '0')
            ->whereNotIn('up.user_id', $staffIds)
            ->whereIn('up.phone_number', function ($q) use ($staffIds) {
                $q->select('phone_number')
                    ->from('user_profiles')
                    ->whereNotNull('phone_number')
                    ->where('phone_number', '!=', '')
                    ->where('phone_number', '!=', '-')
                    ->where('phone_number', '!=', '0')
                    ->whereNotIn('user_id', $staffIds)
                    ->groupBy('phone_number')
                    ->havingRaw('COUNT(*) > 1');
            })
            ->distinct()
            ->count('up.phone_number');

        // Hitung berapa nomor telepon duplikat yang setidaknya 1 akunnya memiliki pembelian HP (sell_phones)
        $phonesWithSellPhonesCount = DB::table('user_profiles as up')
            ->join('sell_phones as sp', 'sp.user_id', '=', 'up.user_id')
            ->whereNotNull('up.phone_number')
            ->where('up.phone_number', '!=', '')
            ->where('up.phone_number', '!=', '-')
            ->where('up.phone_number', '!=', '0')
            ->whereNotIn('up.user_id', $staffIds)
            ->whereIn('up.phone_number', function ($q) use ($staffIds) {
                $q->select('phone_number')
                    ->from('user_profiles')
                    ->whereNotNull('phone_number')
                    ->where('phone_number', '!=', '')
                    ->where('phone_number', '!=', '-')
                    ->where('phone_number', '!=', '0')
                    ->whereNotIn('user_id', $staffIds)
                    ->groupBy('phone_number')
                    ->havingRaw('COUNT(*) > 1');
            })
            ->distinct()
            ->count('up.phone_number');

        // Total nomor telepon yang memiliki transaksi (baik order ATAU sell_phones)
        $phonesWithTransactionsCount = DB::table('user_profiles as up')
            ->whereNotNull('up.phone_number')
            ->where('up.phone_number', '!=', '')
            ->where('up.phone_number', '!=', '-')
            ->where('up.phone_number', '!=', '0')
            ->whereNotIn('up.user_id', $staffIds)
            ->whereIn('up.phone_number', function ($q) use ($staffIds) {
                $q->select('phone_number')
                    ->from('user_profiles')
                    ->whereNotNull('phone_number')
                    ->where('phone_number', '!=', '')
                    ->where('phone_number', '!=', '-')
                    ->where('phone_number', '!=', '0')
                    ->whereNotIn('user_id', $staffIds)
                    ->groupBy('phone_number')
                    ->havingRaw('COUNT(*) > 1');
            })
            ->where(function ($q) {
                $q->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('orders')
                        ->whereColumn('orders.user_id', 'up.user_id');
                })->orWhereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('sell_phones')
                        ->whereColumn('sell_phones.user_id', 'up.user_id');
                });
            })
            ->distinct()
            ->count('up.phone_number');

        $phonesWithoutTransactionsCount = max(0, $totalDuplicatePhones - $phonesWithTransactionsCount);

        return [
            'totalDuplicatePhones' => $totalDuplicatePhones,
            'totalDuplicateAccounts' => $totalDuplicateAccounts,
            'phonesWithOrdersCount' => $phonesWithOrdersCount,
            'phonesWithSellPhonesCount' => $phonesWithSellPhonesCount,
            'phonesWithTransactionsCount' => $phonesWithTransactionsCount,
            'phonesWithoutTransactionsCount' => $phonesWithoutTransactionsCount,
        ];
    }

    public function render()
    {
        $staffIds = $this->getStaffUserIds();

        // 1. Query dasar untuk kelompok nomor telepon ganda (excluding kosong & staff)
        $query = DB::table('user_profiles as up')
            ->select('up.phone_number', DB::raw('COUNT(up.id) as accounts_count'))
            ->whereNotNull('up.phone_number')
            ->where('up.phone_number', '!=', '')
            ->where('up.phone_number', '!=', '-')
            ->where('up.phone_number', '!=', '0')
            ->whereNotIn('up.user_id', $staffIds)
            ->groupBy('up.phone_number')
            ->havingRaw('COUNT(up.id) > 1');

        // 2. Filter Pencarian (Search)
        if (!empty(trim($this->search))) {
            $term = trim($this->search);
            $query->where(function ($q) use ($term, $staffIds) {
                $q->where('up.phone_number', 'like', '%' . $term . '%')
                    ->orWhere('up.full_name', 'like', '%' . $term . '%')
                    ->orWhereIn('up.user_id', function ($sub) use ($term, $staffIds) {
                        $sub->select('id')
                            ->from('users')
                            ->whereNotIn('id', $staffIds)
                            ->where(function ($subQ) use ($term) {
                                $subQ->where('name', 'like', '%' . $term . '%')
                                    ->orWhere('email', 'like', '%' . $term . '%');
                            });
                    });
            });
        }

        // 3. Filter Status Transaksi (Orders & Sell Phones)
        if ($this->orderFilter === 'has_transactions') {
            $query->where(function ($mainQ) use ($staffIds) {
                $mainQ->whereIn('up.phone_number', function ($sub) use ($staffIds) {
                    $sub->select('up_sub.phone_number')
                        ->from('user_profiles as up_sub')
                        ->join('orders as o_sub', 'o_sub.user_id', '=', 'up_sub.user_id')
                        ->whereNotNull('up_sub.phone_number')
                        ->where('up_sub.phone_number', '!=', '')
                        ->where('up_sub.phone_number', '!=', '-')
                        ->where('up_sub.phone_number', '!=', '0')
                        ->whereNotIn('up_sub.user_id', $staffIds)
                        ->groupBy('up_sub.phone_number');
                })->orWhereIn('up.phone_number', function ($sub) use ($staffIds) {
                    $sub->select('up_sub.phone_number')
                        ->from('user_profiles as up_sub')
                        ->join('sell_phones as sp_sub', 'sp_sub.user_id', '=', 'up_sub.user_id')
                        ->whereNotNull('up_sub.phone_number')
                        ->where('up_sub.phone_number', '!=', '')
                        ->where('up_sub.phone_number', '!=', '-')
                        ->where('up_sub.phone_number', '!=', '0')
                        ->whereNotIn('up_sub.user_id', $staffIds)
                        ->groupBy('up_sub.phone_number');
                });
            });
        } elseif ($this->orderFilter === 'has_orders') {
            $query->whereIn('up.phone_number', function ($sub) use ($staffIds) {
                $sub->select('up_sub.phone_number')
                    ->from('user_profiles as up_sub')
                    ->join('orders as o_sub', 'o_sub.user_id', '=', 'up_sub.user_id')
                    ->whereNotNull('up_sub.phone_number')
                    ->where('up_sub.phone_number', '!=', '')
                    ->where('up_sub.phone_number', '!=', '-')
                    ->where('up_sub.phone_number', '!=', '0')
                    ->whereNotIn('up_sub.user_id', $staffIds)
                    ->groupBy('up_sub.phone_number');
            });
        } elseif ($this->orderFilter === 'has_sell_phones') {
            $query->whereIn('up.phone_number', function ($sub) use ($staffIds) {
                $sub->select('up_sub.phone_number')
                    ->from('user_profiles as up_sub')
                    ->join('sell_phones as sp_sub', 'sp_sub.user_id', '=', 'up_sub.user_id')
                    ->whereNotNull('up_sub.phone_number')
                    ->where('up_sub.phone_number', '!=', '')
                    ->where('up_sub.phone_number', '!=', '-')
                    ->where('up_sub.phone_number', '!=', '0')
                    ->whereNotIn('up_sub.user_id', $staffIds)
                    ->groupBy('up_sub.phone_number');
            });
        } elseif ($this->orderFilter === 'no_transactions') {
            $query->whereNotIn('up.phone_number', function ($sub) use ($staffIds) {
                $sub->select('up_sub.phone_number')
                    ->from('user_profiles as up_sub')
                    ->join('orders as o_sub', 'o_sub.user_id', '=', 'up_sub.user_id')
                    ->whereNotNull('up_sub.phone_number')
                    ->where('up_sub.phone_number', '!=', '')
                    ->where('up_sub.phone_number', '!=', '-')
                    ->where('up_sub.phone_number', '!=', '0')
                    ->whereNotIn('up_sub.user_id', $staffIds)
                    ->groupBy('up_sub.phone_number');
            })->whereNotIn('up.phone_number', function ($sub) use ($staffIds) {
                $sub->select('up_sub.phone_number')
                    ->from('user_profiles as up_sub')
                    ->join('sell_phones as sp_sub', 'sp_sub.user_id', '=', 'up_sub.user_id')
                    ->whereNotNull('up_sub.phone_number')
                    ->where('up_sub.phone_number', '!=', '')
                    ->where('up_sub.phone_number', '!=', '-')
                    ->where('up_sub.phone_number', '!=', '0')
                    ->whereNotIn('up_sub.user_id', $staffIds)
                    ->groupBy('up_sub.phone_number');
            });
        } elseif ($this->orderFilter === 'conflict_transactions') {
            $query->whereIn('up.phone_number', function ($sub) use ($staffIds) {
                $sub->select('up_sub.phone_number')
                    ->from('user_profiles as up_sub')
                    ->where(function ($cond) {
                        $cond->whereExists(function ($inner) {
                            $inner->select(DB::raw(1))->from('orders')->whereColumn('orders.user_id', 'up_sub.user_id');
                        })->orWhereExists(function ($inner) {
                            $inner->select(DB::raw(1))->from('sell_phones')->whereColumn('sell_phones.user_id', 'up_sub.user_id');
                        });
                    })
                    ->whereNotNull('up_sub.phone_number')
                    ->where('up_sub.phone_number', '!=', '')
                    ->where('up_sub.phone_number', '!=', '-')
                    ->where('up_sub.phone_number', '!=', '0')
                    ->whereNotIn('up_sub.user_id', $staffIds)
                    ->groupBy('up_sub.phone_number')
                    ->havingRaw('COUNT(DISTINCT up_sub.user_id) > 1');
            });
        }

        // Urutkan nomor HP yang punya akun duplikat terbanyak di atas
        $query->orderByDesc('accounts_count');

        // Paginasi kelompok nomor HP
        $paginatedGroups = $query->paginate($this->perPage);

        // 4. Ambil nomor HP unik pada halaman aktif saat ini
        $activePhoneNumbers = collect($paginatedGroups->items())->pluck('phone_number')->filter()->toArray();

        // 5. Eager load semua user detail untuk nomor HP di halaman aktif (exclude staff)
        $usersByPhone = collect();

        if (!empty($activePhoneNumbers)) {
            $users = User::with([
                'profile',
                'accurateCustomers.businessUnit',
                'accurateVendors.businessUnit',
                'roles',
            ])
                ->whereNotIn('id', $staffIds)
                ->withCount(['orders', 'sellPhones'])
                ->withSum('orders as orders_total_amount', 'grand_total')
                ->withSum('sellPhones as sell_phones_total_amount', 'appraised_value')
                ->whereHas('profile', function ($q) use ($activePhoneNumbers) {
                    $q->whereIn('phone_number', $activePhoneNumbers);
                })
                ->orderByDesc('orders_count')
                ->orderByDesc('sell_phones_count')
                ->orderBy('created_at')
                ->get();

            $usersByPhone = $users->groupBy(function ($u) {
                return $u->profile->phone_number ?? '';
            });
        }

        return view('livewire.admin.users.duplicate-users', [
            'paginatedGroups' => $paginatedGroups,
            'usersByPhone' => $usersByPhone,
            'stats' => $this->getStats(),
            'activePagePhoneNumbers' => $activePhoneNumbers,
            'selectedPhones' => $this->selectedPhones,
            'selectAllOnPage' => $this->selectAllOnPage,
            'isOrdersModalOpen' => $this->isOrdersModalOpen,
            'selectedUserId' => $this->selectedUserId,
            'selectedUserName' => $this->selectedUserName,
            'selectedUserEmail' => $this->selectedUserEmail,
            'selectedUserPhone' => $this->selectedUserPhone,
            'selectedUserOrders' => $this->selectedUserOrders,
            'isSellPhonesModalOpen' => $this->isSellPhonesModalOpen,
            'selectedUserSellPhones' => $this->selectedUserSellPhones,
            'isMergeModalOpen' => $this->isMergeModalOpen,
            'selectedMergePhone' => $this->selectedMergePhone,
            'targetUserId' => $this->targetUserId,
            'mergeCandidateUsers' => $this->mergeCandidateUsers,
            'isBulkCleanerModalOpen' => $this->isBulkCleanerModalOpen,
            'bulkCleanerRunning' => $this->bulkCleanerRunning,
            'bulkTotalCleanGroups' => $this->bulkTotalCleanGroups,
            'bulkProcessedCount' => $this->bulkProcessedCount,
            'bulkSuccessCount' => $this->bulkSuccessCount,
            'bulkSkippedCount' => $this->bulkSkippedCount,
            'bulkBatchSize' => $this->bulkBatchSize,
        ]);
    }
}

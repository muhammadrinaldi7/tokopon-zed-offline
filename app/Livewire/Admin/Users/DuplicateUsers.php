<?php

namespace App\Livewire\Admin\Users;

use App\Models\Order;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin', ['title' => 'Audit & Duplikat Pengguna - TokoPun'])]
class DuplicateUsers extends Component
{
    use WithPagination;

    public $search = '';
    public $orderFilter = 'all'; // 'all', 'has_orders', 'no_orders', 'conflict_orders'
    public $perPage = 15;

    // Modal Pesanan
    public $isOrdersModalOpen = false;
    public $selectedUserId = null;
    public $selectedUserName = '';
    public $selectedUserEmail = '';
    public $selectedUserPhone = '';
    public $selectedUserOrders = [];

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
    }

    public function updatingOrderFilter()
    {
        $this->resetPage();
    }

    public function openOrdersModal($userId)
    {
        $user = User::with(['profile'])->find($userId);
        if (!$user) {
            $this->dispatch('admin-alert', type: 'error', message: 'Data pengguna tidak ditemukan.');
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

    /**
     * Hitung ringkasan statistik KPI
     */
    protected function getStats()
    {
        // Hitung total kelompok nomor HP duplikat (exclude kosong, null, dash, dan nol)
        $totalDuplicatePhones = DB::table('user_profiles')
            ->select('phone_number')
            ->whereNotNull('phone_number')
            ->where('phone_number', '!=', '')
            ->where('phone_number', '!=', '-')
            ->where('phone_number', '!=', '0')
            ->groupBy('phone_number')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();

        // Hitung total akun yang terlibat duplikasi
        $totalDuplicateAccounts = DB::table('user_profiles')
            ->whereIn('phone_number', function ($q) {
                $q->select('phone_number')
                    ->from('user_profiles')
                    ->whereNotNull('phone_number')
                    ->where('phone_number', '!=', '')
                    ->where('phone_number', '!=', '-')
                    ->where('phone_number', '!=', '0')
                    ->groupBy('phone_number')
                    ->havingRaw('COUNT(*) > 1');
            })
            ->count();

        // Hitung berapa nomor telepon duplikat yang setidaknya 1 akunnya memiliki order
        $phonesWithOrdersCount = DB::table('user_profiles as up')
            ->join('orders as o', 'o.user_id', '=', 'up.user_id')
            ->whereNotNull('up.phone_number')
            ->where('up.phone_number', '!=', '')
            ->where('up.phone_number', '!=', '-')
            ->where('up.phone_number', '!=', '0')
            ->whereIn('up.phone_number', function ($q) {
                $q->select('phone_number')
                    ->from('user_profiles')
                    ->whereNotNull('phone_number')
                    ->where('phone_number', '!=', '')
                    ->where('phone_number', '!=', '-')
                    ->where('phone_number', '!=', '0')
                    ->groupBy('phone_number')
                    ->havingRaw('COUNT(*) > 1');
            })
            ->distinct()
            ->count('up.phone_number');

        $phonesWithoutOrdersCount = max(0, $totalDuplicatePhones - $phonesWithOrdersCount);

        return [
            'totalDuplicatePhones' => $totalDuplicatePhones,
            'totalDuplicateAccounts' => $totalDuplicateAccounts,
            'phonesWithOrdersCount' => $phonesWithOrdersCount,
            'phonesWithoutOrdersCount' => $phonesWithoutOrdersCount,
        ];
    }

    public function render()
    {
        // 1. Query dasar untuk kelompok nomor telepon ganda (excluding kosong)
        $query = DB::table('user_profiles as up')
            ->select('up.phone_number', DB::raw('COUNT(up.id) as accounts_count'))
            ->whereNotNull('up.phone_number')
            ->where('up.phone_number', '!=', '')
            ->where('up.phone_number', '!=', '-')
            ->where('up.phone_number', '!=', '0')
            ->groupBy('up.phone_number')
            ->havingRaw('COUNT(up.id) > 1');

        // 2. Filter Pencarian (Search)
        if (!empty(trim($this->search))) {
            $term = trim($this->search);
            $query->where(function ($q) use ($term) {
                $q->where('up.phone_number', 'like', '%' . $term . '%')
                    ->orWhere('up.full_name', 'like', '%' . $term . '%')
                    ->orWhereIn('up.user_id', function ($sub) use ($term) {
                        $sub->select('id')
                            ->from('users')
                            ->where('name', 'like', '%' . $term . '%')
                            ->orWhere('email', 'like', '%' . $term . '%');
                    });
            });
        }

        // 3. Filter Status Order
        if ($this->orderFilter === 'has_orders') {
            // Setidaknya 1 akun di grup nomor ini punya pesanan
            $query->whereIn('up.phone_number', function ($sub) {
                $sub->select('up_sub.phone_number')
                    ->from('user_profiles as up_sub')
                    ->join('orders as o_sub', 'o_sub.user_id', '=', 'up_sub.user_id')
                    ->whereNotNull('up_sub.phone_number')
                    ->where('up_sub.phone_number', '!=', '')
                    ->where('up_sub.phone_number', '!=', '-')
                    ->where('up_sub.phone_number', '!=', '0')
                    ->groupBy('up_sub.phone_number');
            });
        } elseif ($this->orderFilter === 'no_orders') {
            // Semua akun di grup nomor ini belum pernah ada pesanan sama sekali (0 order)
            $query->whereNotIn('up.phone_number', function ($sub) {
                $sub->select('up_sub.phone_number')
                    ->from('user_profiles as up_sub')
                    ->join('orders as o_sub', 'o_sub.user_id', '=', 'up_sub.user_id')
                    ->whereNotNull('up_sub.phone_number')
                    ->where('up_sub.phone_number', '!=', '')
                    ->where('up_sub.phone_number', '!=', '-')
                    ->where('up_sub.phone_number', '!=', '0')
                    ->groupBy('up_sub.phone_number');
            });
        } elseif ($this->orderFilter === 'conflict_orders') {
            // Lebih dari 1 akun di nomor yang sama sama-sama memiliki pesanan
            $query->whereIn('up.phone_number', function ($sub) {
                $sub->select('up_sub.phone_number')
                    ->from('user_profiles as up_sub')
                    ->join('orders as o_sub', 'o_sub.user_id', '=', 'up_sub.user_id')
                    ->whereNotNull('up_sub.phone_number')
                    ->where('up_sub.phone_number', '!=', '')
                    ->where('up_sub.phone_number', '!=', '-')
                    ->where('up_sub.phone_number', '!=', '0')
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

        // 5. Eager load semua user detail untuk nomor HP di halaman aktif
        $usersByPhone = collect();

        if (!empty($activePhoneNumbers)) {
            $users = User::with([
                'profile',
                'accurateCustomers.businessUnit',
                'roles',
            ])
                ->withCount('orders')
                ->withSum('orders as orders_total_amount', 'grand_total')
                ->whereHas('profile', function ($q) use ($activePhoneNumbers) {
                    $q->whereIn('phone_number', $activePhoneNumbers);
                })
                ->orderByDesc('orders_count') // user yang banyak pesanan di urutan pertama
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
            'isOrdersModalOpen' => $this->isOrdersModalOpen,
            'selectedUserId' => $this->selectedUserId,
            'selectedUserName' => $this->selectedUserName,
            'selectedUserEmail' => $this->selectedUserEmail,
            'selectedUserPhone' => $this->selectedUserPhone,
            'selectedUserOrders' => $this->selectedUserOrders,
        ]);
    }
}

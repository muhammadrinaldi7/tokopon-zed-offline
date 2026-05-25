<?php

use App\Models\User;
use App\Models\Branch;
use App\Models\Warehouse;
use App\Services\SettingService;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;

new #[Layout('layouts.admin', ['title' => 'Pengaturan POS - TokoPun'])]
class extends Component {
    use WithPagination;

    public $search = '';
    public $activeTab = 'staff'; // 'staff' or 'general'
    
    // General Settings
    public $default_customer_id;
    public $minimum_stock_alert;

    public function mount()
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        if ($user->roles->count() === 0 || $user->hasRole('user')) {
             return redirect('/');
        }
        
        $settingService = app(SettingService::class);
        $this->default_customer_id = $settingService->get('pos_default_customer_id');
        $this->minimum_stock_alert = $settingService->get('pos_minimum_stock_alert', 5);
    }

    #[Computed]
    public function staff()
    {
        $query = User::with(['roles', 'branch', 'warehouse'])
            ->whereHas('roles', function ($q) {
                $q->whereNotIn('name', ['customer', 'user']);
            });

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        return $query->orderBy('name')->paginate(10);
    }

    #[Computed]
    public function branches()
    {
        return Branch::orderBy('name')->get();
    }

    #[Computed]
    public function warehouses()
    {
        return Warehouse::orderBy('name')->get();
    }

    #[Computed]
    public function customers()
    {
        return User::whereHas('roles', function($q) {
            $q->where('name', 'user');
        })->orderBy('name')->get();
    }

    public function updateStaffBranch($userId, $branchId)
    {
        $user = User::findOrFail($userId);
        $user->update(['branch_id' => $branchId ?: null]);
        $this->dispatch('toast', title: 'Berhasil', message: 'Cabang staff ' . $user->name . ' diperbarui.', type: 'success');
    }

    public function updateStaffWarehouse($userId, $warehouseId)
    {
        $user = User::findOrFail($userId);
        $user->update(['warehouse_id' => $warehouseId ?: null]);
        $this->dispatch('toast', title: 'Berhasil', message: 'Gudang staff ' . $user->name . ' diperbarui.', type: 'success');
    }

    public function saveGeneralSettings()
    {
        $settingService = app(SettingService::class);
        $settingService->set('pos_default_customer_id', $this->default_customer_id);
        $settingService->set('pos_minimum_stock_alert', $this->minimum_stock_alert, 'integer');

        $this->dispatch('toast', title: 'Berhasil', message: 'Pengaturan umum POS diperbarui.', type: 'success');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }
};
?>

<div>
    {{-- Banner --}}
    <div class="bg-linear-to-r from-[#4E44DB] via-[#6355F6] to-[#766bf2] rounded-4xl p-8 text-white mb-8 shadow-xl shadow-[#4E44DB]/15 relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 w-44 h-44 bg-white/5 rounded-full blur-2xl"></div>
        <div class="absolute -left-10 -top-10 w-44 h-44 bg-white/5 rounded-full blur-2xl"></div>
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <h1 class="text-3xl font-black tracking-tight mb-2">Pengaturan POS</h1>
                <p class="text-indigo-100 text-sm font-medium">Kelola hak akses cabang, gudang staff operasional, dan parameter umum sistem Point of Sale.</p>
            </div>
            <div class="bg-white/10 backdrop-blur-md border border-white/20 px-5 py-3 rounded-2xl text-sm font-bold flex items-center h-fit">
                <span class="relative flex h-3.5 w-3.5 mr-3">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-emerald-500"></span>
                </span>
                Sistem POS Aktif
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="flex border-b border-gray-100 mb-8 gap-2">
        <button wire:click="$set('activeTab', 'staff')" 
            class="px-6 py-3 font-bold text-sm transition-all relative cursor-pointer {{ $activeTab === 'staff' ? 'text-[#4E44DB]' : 'text-gray-400 hover:text-gray-600' }}">
            Pemetaan Gudang & Cabang Staff
            @if ($activeTab === 'staff')
                <div class="absolute bottom-0 left-0 right-0 h-0.5 bg-[#4E44DB] rounded-t-full"></div>
            @endif
        </button>
        <button wire:click="$set('activeTab', 'general')" 
            class="px-6 py-3 font-bold text-sm transition-all relative cursor-pointer {{ $activeTab === 'general' ? 'text-[#4E44DB]' : 'text-gray-400 hover:text-gray-600' }}">
            Parameter POS Umum
            @if ($activeTab === 'general')
                <div class="absolute bottom-0 left-0 right-0 h-0.5 bg-[#4E44DB] rounded-t-full"></div>
            @endif
        </button>
    </div>

    {{-- Tab content --}}
    @if ($activeTab === 'staff')
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden mb-8">
            {{-- Search Bar --}}
            <div class="p-6 border-b border-gray-50 bg-gray-50/50 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h3 class="font-extrabold text-gray-800 text-lg">Daftar Staff Operasional</h3>
                    <p class="text-xs text-gray-400 mt-0.5 font-medium">Tentukan cabang & gudang aktif untuk menyaring stok produk di POS staff.</p>
                </div>
                <div class="relative w-full md:w-80">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" wire:model.live.debounce.300ms="search"
                        class="w-full pl-10 pr-4 py-2.5 bg-white border border-gray-200 rounded-xl focus:border-[#4E44DB] focus:ring-0 text-sm font-semibold transition-all placeholder-gray-400 shadow-xs"
                        placeholder="Cari staff...">
                </div>
            </div>

            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50/30">
                            <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Nama & Email</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Role</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Cabang Aktif (POS)</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Gudang Aktif (Stok)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse ($this->staff as $user)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-sm border border-indigo-100 shadow-xs">
                                            {{ substr($user->name, 0, 1) }}
                                        </div>
                                        <div>
                                            <p class="font-bold text-gray-800 text-sm leading-snug">{{ $user->name }}</p>
                                            <p class="text-xs text-gray-400 font-medium mt-0.5">{{ $user->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-5">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($user->roles as $role)
                                            <span class="px-2.5 py-1 text-[10px] font-black uppercase rounded-lg tracking-wider
                                                {{ $role->name === 'admin' || $role->name === 'superadmin' ? 'bg-rose-50 text-rose-600 border border-rose-100' : 'bg-indigo-50 text-indigo-600 border border-indigo-100' }}">
                                                {{ $role->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-6 py-5">
                                    <select wire:change="updateStaffBranch({{ $user->id }}, $event.target.value)"
                                        class="bg-white border border-gray-200 rounded-xl px-3 py-2 text-xs font-bold text-gray-700 focus:border-[#4E44DB] focus:ring-0 transition-all cursor-pointer">
                                        <option value="">-- Pilih Cabang --</option>
                                        @foreach ($this->branches as $branch)
                                            <option value="{{ $branch->id }}" {{ $user->branch_id == $branch->id ? 'selected' : '' }}>
                                                {{ $branch->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-6 py-5">
                                    <select wire:change="updateStaffWarehouse({{ $user->id }}, $event.target.value)"
                                        class="bg-white border border-gray-200 rounded-xl px-3 py-2 text-xs font-bold text-gray-700 focus:border-[#4E44DB] focus:ring-0 transition-all cursor-pointer">
                                        <option value="">-- Pilih Gudang --</option>
                                        @foreach ($this->warehouses as $warehouse)
                                            <option value="{{ $warehouse->id }}" {{ $user->warehouse_id == $warehouse->id ? 'selected' : '' }}>
                                                {{ $warehouse->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-gray-400 font-medium">
                                    <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                    Staff tidak ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if ($this->staff->hasPages())
                <div class="px-6 py-4 border-t border-gray-50 bg-gray-50/20">
                    {{ $this->staff->links() }}
                </div>
            @endif
        </div>
    @endif

    @if ($activeTab === 'general')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 bg-white rounded-3xl border border-gray-100 shadow-sm p-8 space-y-6">
                <div>
                    <h3 class="font-extrabold text-gray-800 text-lg">Parameter Sistem POS</h3>
                    <p class="text-xs text-gray-400 mt-0.5 font-medium">Konfigurasi parameter umum untuk menyederhanakan transaksi kasir.</p>
                </div>

                <div class="space-y-4">
                    {{-- Default Customer --}}
                    <div class="space-y-2">
                        <label class="text-xs font-black text-gray-400 uppercase tracking-widest block">Pelanggan Default (POS Cash/Guest)</label>
                        <select wire:model="default_customer_id"
                            class="w-full bg-white border border-gray-200 rounded-xl px-4 py-3 text-sm font-semibold focus:border-[#4E44DB] focus:ring-0 transition-all cursor-pointer">
                            <option value="">-- Pilih Pelanggan --</option>
                            @foreach ($this->customers as $cust)
                                <option value="{{ $cust->id }}">
                                    {{ $cust->name }} ({{ $cust->email }})
                                </option>
                            @endforeach
                        </select>
                        <p class="text-[11px] text-gray-400">Pengguna terpilih akan otomatis digunakan sebagai pembeli jika kasir tidak memilih customer tertentu.</p>
                    </div>

                    {{-- Minimum Stock Alert --}}
                    <div class="space-y-2">
                        <label class="text-xs font-black text-gray-400 uppercase tracking-widest block">Batas Minimum Peringatan Stok</label>
                        <input type="number" wire:model="minimum_stock_alert"
                            class="w-full bg-white border border-gray-200 rounded-xl px-4 py-3 text-sm font-semibold focus:border-[#4E44DB] focus:ring-0 transition-all"
                            min="0" placeholder="5">
                        <p class="text-[11px] text-gray-400">Ketika stok di gudang terpilih berada di bawah angka ini, POS akan menampilkan label indikator stok kritis.</p>
                    </div>
                </div>

                <div class="border-t border-gray-50 pt-6 flex justify-end">
                    <button wire:click="saveGeneralSettings"
                        class="bg-[#4E44DB] hover:bg-blue-700 text-white font-bold px-6 py-3 rounded-2xl transition-all shadow-md shadow-blue-500/10 cursor-pointer">
                        Simpan Pengaturan
                    </button>
                </div>
            </div>

            <div class="bg-linear-to-br from-indigo-50 to-blue-50/50 rounded-3xl p-8 border border-indigo-100/50 h-fit space-y-4">
                <h4 class="font-extrabold text-indigo-900 text-md">Integrasi Gudang & POS</h4>
                <p class="text-xs text-indigo-700/80 leading-relaxed">
                    Setiap transaksi di Point of Sale (POS) secara otomatis memotong stok dari gudang yang ditugaskan kepada staff bersangkutan. 
                </p>
                <div class="bg-white/80 backdrop-blur-xs p-4 rounded-2xl border border-indigo-100 shadow-xs space-y-2">
                    <p class="text-xs font-bold text-indigo-900">Mengapa pemetaan ini penting?</p>
                    <ul class="text-[11px] text-indigo-700/90 list-disc list-inside space-y-1">
                        <li>Memastikan kesesuaian fisik produk di gerai.</li>
                        <li>Sinkronisasi data penjualan ke Accurate Online sesuai cabang tujuan.</li>
                        <li>Mencegah penjualan barang yang kosong atau milik cabang lain.</li>
                    </ul>
                </div>
            </div>
        </div>
    @endif
</div>

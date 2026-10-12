<div class="p-4 md:p-6 space-y-6">
    {{-- Header Partial --}}
    @include('livewire.zoffline.ecommerce.partials.header', [
        'title' => 'Manajemen Member & Poin Loyalty'
    ])

    {{-- Top Overview Stat Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white p-4.5 rounded-2xl border border-gray-100 shadow-xs flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 border border-amber-200/50">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
            </div>
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total Member</p>
                <h4 class="text-xl font-black text-gray-900 mt-0.5">{{ number_format($stats['total_members']) }}</h4>
                <p class="text-[10px] text-gray-400">Akun terdaftar</p>
            </div>
        </div>

        <div class="bg-white p-4.5 rounded-2xl border border-gray-100 shadow-xs flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 border border-emerald-200/50">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Poin Beredar</p>
                <h4 class="text-xl font-black text-emerald-600 mt-0.5">{{ number_format($stats['total_points_circulating']) }}</h4>
                <p class="text-[10px] text-gray-400">≈ Rp {{ number_format($stats['total_points_circulating'] * $loyalty_point_value) }}</p>
            </div>
        </div>

        <div class="bg-white p-4.5 rounded-2xl border border-gray-100 shadow-xs flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0 border border-indigo-200/50">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                </svg>
            </div>
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total Belanja Member</p>
                <h4 class="text-xl font-black text-gray-900 mt-0.5">Rp {{ number_format($stats['total_spend_recorded'] / 1000000, 1) }}M</h4>
                <p class="text-[10px] text-gray-400">Akumulasi transaksi</p>
            </div>
        </div>

        <div class="bg-white p-4.5 rounded-2xl border border-gray-100 shadow-xs flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0 border border-purple-200/50">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
            </div>
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Mutasi Ledger</p>
                <h4 class="text-xl font-black text-gray-900 mt-0.5">{{ number_format($stats['total_transactions']) }}</h4>
                <p class="text-[10px] text-gray-400">Earn & Redeem log</p>
            </div>
        </div>
    </div>

    {{-- Navigation Tabs --}}
    <div class="flex items-center gap-2 border-b border-gray-200 pb-3">
        <button wire:click="setTab('members')"
            class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 {{ $activeTab === 'members' ? 'bg-neutral-900 text-white shadow-xs' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
            <span>👥</span>
            <span>Daftar Member Loyalty</span>
        </button>

        <button wire:click="setTab('mapping')"
            class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 {{ $activeTab === 'mapping' ? 'bg-neutral-900 text-white shadow-xs' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
            <span>🔄</span>
            <span>Mapping & Sinkronisasi Pelanggan</span>
            @if(isset($syncStatus['unmapped_count']) && $syncStatus['unmapped_count'] > 0)
                <span class="bg-rose-500 text-white text-[10px] px-2 py-0.5 rounded-full font-black">
                    {{ $syncStatus['unmapped_count'] }}
                </span>
            @endif
        </button>

        <button wire:click="setTab('settings')"
            class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 {{ $activeTab === 'settings' ? 'bg-neutral-900 text-white shadow-xs' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
            <span>⚙️</span>
            <span>Pengaturan Aturan Poin</span>
        </button>
    </div>

    {{-- ============================================================== --}}
    {{-- TAB 1: DAFTAR MEMBER LOYALTY --}}
    {{-- ============================================================== --}}
    @if ($activeTab === 'members')
        <div class="space-y-4">
            {{-- Filter & Search Toolbar --}}
            <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div class="relative flex-1 max-w-md">
                    <input type="text" wire:model.live.debounce.300ms="search"
                        placeholder="Cari nomor HP atau nama member..."
                        class="w-full pl-10 pr-4 py-2 text-xs bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-neutral-900 focus:outline-none transition-all">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>

                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-xs text-gray-500 font-semibold">Filter Tier:</span>
                    <button wire:click="$set('filterTier', '')"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all {{ $filterTier === '' ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                        Semua
                    </button>
                    @foreach(['BRONZE', 'SILVER', 'GOLD', 'PLATINUM'] as $t)
                        <button wire:click="$set('filterTier', '{{ $t }}')"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all {{ $filterTier === $t ? 'bg-amber-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                            {{ $t }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Member Table --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-gray-600">
                        <thead class="bg-gray-50/80 text-gray-700 text-[11px] font-bold uppercase tracking-wider border-b border-gray-100">
                            <tr>
                                <th class="p-4">Member / Kontak</th>
                                <th class="p-4">Tier Status</th>
                                <th class="p-4">Saldo Poin</th>
                                <th class="p-4">Nilai Tunai (Rp)</th>
                                <th class="p-4">Total Belanja</th>
                                <th class="p-4">Akumulasi Poin</th>
                                <th class="p-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($accounts as $acc)
                                @php
                                    $tierColors = match($acc->tier) {
                                        'PLATINUM' => 'bg-slate-900 text-slate-100 border-slate-700',
                                        'GOLD' => 'bg-amber-100 text-amber-800 border-amber-300',
                                        'SILVER' => 'bg-slate-100 text-slate-700 border-slate-300',
                                        default => 'bg-orange-50 text-orange-700 border-orange-200',
                                    };
                                @endphp
                                <tr class="hover:bg-gray-50/70 transition-colors">
                                    <td class="p-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-xl bg-gray-100 text-gray-700 flex items-center justify-center font-black text-sm shrink-0 border border-gray-200">
                                                {{ strtoupper(substr($acc->customer_name ?: 'P', 0, 1)) }}
                                            </div>
                                            <div>
                                                <p class="font-bold text-gray-900 text-xs">{{ $acc->customer_name ?: 'Pelanggan Tokopon' }}</p>
                                                <p class="text-[11px] font-mono text-gray-500 mt-0.5">📱 {{ $acc->phone_number }}</p>
                                                @if($acc->user_id)
                                                    <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-semibold bg-blue-50 text-blue-700">
                                                        User ID #{{ $acc->user_id }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-4">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black border uppercase tracking-wide {{ $tierColors }}">
                                            {{ $acc->tier }} MEMBER
                                        </span>
                                    </td>
                                    <td class="p-4">
                                        <div class="flex items-baseline gap-1">
                                            <span class="text-sm font-black text-gray-900">{{ number_format($acc->points_balance) }}</span>
                                            <span class="text-[10px] font-bold text-amber-600">Pts</span>
                                        </div>
                                    </td>
                                    <td class="p-4">
                                        <span class="font-bold text-emerald-600">
                                            Rp {{ number_format($acc->points_balance * $loyalty_point_value) }}
                                        </span>
                                    </td>
                                    <td class="p-4 font-medium text-gray-700">
                                        Rp {{ number_format($acc->total_spend) }}
                                    </td>
                                    <td class="p-4 text-gray-500 font-mono">
                                        {{ number_format($acc->lifetime_points) }} Pts
                                    </td>
                                    <td class="p-4 text-right">
                                        <div class="inline-flex items-center gap-1.5">
                                            <button wire:click="openHistoryModal({{ $acc->id }})"
                                                title="Lihat Riwayat Ledger Poin"
                                                class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-gray-100 hover:bg-gray-200 text-gray-700 transition">
                                                Ledger
                                            </button>
                                            <button wire:click="openAdjustModal({{ $acc->id }})"
                                                title="Penyesuaian Poin Manual"
                                                class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 transition">
                                                +/- Poin
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="p-8 text-center text-gray-400">
                                        <div class="max-w-xs mx-auto space-y-2">
                                            <span class="text-3xl">📭</span>
                                            <p class="font-bold text-gray-700 text-sm">Tidak Ada Member Ditemukan</p>
                                            <p class="text-xs">Coba ubah kata kunci pencarian atau lakukan sinkronisasi data pelanggan lama pada tab Mapping.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination Links --}}
                <div class="p-4 border-t border-gray-100">
                    {{ $accounts->links() }}
                </div>
            </div>
        </div>
    @endif

    {{-- ============================================================== --}}
    {{-- TAB 2: MAPPING & SINKRONISASI DATA PELANGGAN LAMA --}}
    {{-- ============================================================== --}}
    @if ($activeTab === 'mapping')
        <div class="space-y-6">
            {{-- Info & Action Banner --}}
            <div class="bg-gradient-to-r from-blue-900 to-indigo-900 rounded-3xl p-6 text-white shadow-lg relative overflow-hidden">
                <div class="relative z-10 max-w-2xl">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-blue-500/30 text-blue-200 border border-blue-400/30">
                        Migrasi & Data Harmonization
                    </span>
                    <h3 class="text-2xl font-black mt-2">Sinkronisasi Data Pelanggan ke Loyalty</h3>
                    <p class="text-xs text-blue-100/90 leading-relaxed mt-2">
                        Fitur ini secara otomatis memindai seluruh data pengguna terdaftar di sistem (<code class="bg-blue-950/60 px-1 py-0.5 rounded font-mono">user_profiles</code> & <code class="bg-blue-950/60 px-1 py-0.5 rounded font-mono">users</code>), menstandarkan format nomor HP (ke <code class="bg-blue-950/60 px-1 py-0.5 rounded font-mono">628xxx</code>), lalu membuatkan akun Member Loyalty secara aman tanpa duplikasi.
                    </p>

                    <div class="mt-4 pt-4 border-t border-blue-700/60 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" wire:model="calcHistoricalSpend"
                                class="w-4 h-4 rounded text-amber-500 focus:ring-amber-400 border-blue-400/50 bg-blue-950/60">
                            <span class="text-xs font-semibold text-blue-100">
                                Hitung total belanja lampau (Order COMPLETED) untuk penentuan Tier awal otomatis
                            </span>
                        </label>

                        <button wire:click="runSync" wire:loading.attr="disabled"
                            class="px-5 py-2.5 rounded-xl font-bold text-xs bg-amber-400 hover:bg-amber-300 text-neutral-950 shadow-md transition flex items-center justify-center gap-2 shrink-0 disabled:opacity-50">
                            <span wire:loading.remove wire:target="runSync">🚀 Mulai Sinkronisasi Sekarang</span>
                            <span wire:loading wire:target="runSync" class="flex items-center gap-2">
                                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Sedang Memproses...
                            </span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Sync Metrics Overview --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-xs">
                    <p class="text-[11px] font-bold text-gray-400 uppercase">Total User Terdaftar</p>
                    <h3 class="text-2xl font-black text-gray-900 mt-1">{{ number_format($syncStatus['total_users'] ?? 0) }}</h3>
                    <p class="text-[10px] text-gray-400">Tabel users</p>
                </div>
                <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-xs">
                    <p class="text-[11px] font-bold text-gray-400 uppercase">User dengan No. HP</p>
                    <h3 class="text-2xl font-black text-blue-600 mt-1">{{ number_format($syncStatus['total_profiles_with_phone'] ?? 0) }}</h3>
                    <p class="text-[10px] text-gray-400">Tabel user_profiles</p>
                </div>
                <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-xs">
                    <p class="text-[11px] font-bold text-gray-400 uppercase">Akun Loyalty Aktif</p>
                    <h3 class="text-2xl font-black text-emerald-600 mt-1">{{ number_format($syncStatus['total_loyalty_accounts'] ?? 0) }}</h3>
                    <p class="text-[10px] text-gray-400">Tabel loyalty_accounts</p>
                </div>
                <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-xs">
                    <p class="text-[11px] font-bold text-gray-400 uppercase">Belum Ter-mapping</p>
                    <h3 class="text-2xl font-black text-rose-600 mt-1">{{ number_format($syncStatus['unmapped_count'] ?? 0) }}</h3>
                    <p class="text-[10px] text-gray-400">Memerlukan sinkronisasi</p>
                </div>
            </div>

            {{-- Unmapped Preview Table --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-xs overflow-hidden">
                <div class="p-4 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h4 class="font-bold text-sm text-gray-900">Daftar Pengguna Belum Terpetakan (Sampel Antrean)</h4>
                        <p class="text-[11px] text-gray-500">Pengguna berikut memiliki nomor HP di profil mereka namun belum tercatat di tabel loyalty.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-gray-600">
                        <thead class="bg-gray-50 text-gray-700 font-bold uppercase text-[10px] border-b border-gray-100">
                            <tr>
                                <th class="p-3.5">User ID</th>
                                <th class="p-3.5">Nama Pengguna</th>
                                <th class="p-3.5">No. HP Mentah</th>
                                <th class="p-3.5">No. HP Standar (Normalized)</th>
                                <th class="p-3.5">Email</th>
                                <th class="p-3.5">Tgl Terdaftar</th>
                                <th class="p-3.5 text-right">Aksi Cepat</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($syncStatus['unmapped_samples'] ?? [] as $sample)
                                <tr class="hover:bg-gray-50/70">
                                    <td class="p-3.5 font-mono text-gray-400">#{{ $sample['user_id'] }}</td>
                                    <td class="p-3.5 font-bold text-gray-900">{{ $sample['name'] }}</td>
                                    <td class="p-3.5 font-mono text-gray-500">{{ $sample['raw_phone'] }}</td>
                                    <td class="p-3.5 font-mono text-emerald-600 font-bold">{{ $sample['normalized_phone'] }}</td>
                                    <td class="p-3.5 text-gray-500">{{ $sample['email'] ?: '-' }}</td>
                                    <td class="p-3.5 text-gray-400">{{ $sample['registered_at'] }}</td>
                                    <td class="p-3.5 text-right">
                                        <button wire:click="mapSingleUser({{ $sample['user_id'] }})"
                                            class="px-3 py-1 rounded-lg text-xs font-bold bg-indigo-50 hover:bg-indigo-100 text-indigo-700 transition">
                                            Map Akun Ini
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="p-8 text-center text-gray-400">
                                        <div class="max-w-xs mx-auto space-y-1">
                                            <span class="text-2xl">✨</span>
                                            <p class="font-bold text-gray-700 text-xs">Semua Data Pengguna Telah Terpetakan!</p>
                                            <p class="text-[11px]">Seluruh pengguna dengan nomor HP aktif telah terdaftar ke akun Loyalty Tokopon.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- ============================================================== --}}
    {{-- TAB 3: PENGATURAN ATURAN SISTEM POIN --}}
    {{-- ============================================================== --}}
    @if ($activeTab === 'settings')
        <div class="bg-white rounded-3xl p-6 md:p-8 border border-gray-100 shadow-xs max-w-4xl space-y-6">
            <div>
                <h3 class="text-xl font-black text-gray-900">Konfigurasi Program Loyalitas</h3>
                <p class="text-xs text-gray-500 mt-1">
                    Atur nilai konversi poin, rasio perolehan dari transaksi, batasan minimal penukaran, serta ambang batas tier keanggotaan.
                </p>
            </div>

            <form wire:submit="saveSettings" class="space-y-6">
                {{-- Master Switch --}}
                <div class="p-4.5 rounded-2xl border border-gray-200/80 bg-gray-50/60 flex items-center justify-between">
                    <div>
                        <h4 class="font-bold text-sm text-gray-900">Status Sistem Loyalitas & Poin</h4>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Jika dimatikan, perolehan poin dan penukaran poin di kasir POS & Mobile App akan dinonaktifkan sementara.
                        </p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" wire:model="loyalty_is_active" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                    </label>
                </div>

                {{-- Konversi & Ratio Section --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1.5">
                            Rasio Perolehan Poin (Rp)
                        </label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-2.5 text-xs text-gray-400 font-bold">Rp</span>
                            <input type="number" step="1000" wire:model="loyalty_earn_ratio"
                                class="w-full pl-10 pr-3.5 py-2 text-xs bg-gray-50 border border-gray-200 rounded-xl font-bold text-gray-900 focus:bg-white focus:border-neutral-900 focus:outline-none">
                        </div>
                        <p class="text-[10px] text-gray-400 mt-1">Belanja nominal ini menghasilkan 1 poin (Default: Rp 10.000).</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1.5">
                            Nilai Diskon 1 Poin (Rp)
                        </label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-2.5 text-xs text-gray-400 font-bold">Rp</span>
                            <input type="number" step="10" wire:model="loyalty_point_value"
                                class="w-full pl-10 pr-3.5 py-2 text-xs bg-gray-50 border border-gray-200 rounded-xl font-bold text-gray-900 focus:bg-white focus:border-neutral-900 focus:outline-none">
                        </div>
                        <p class="text-[10px] text-gray-400 mt-1">Nilai potongan tunai untuk 1 poin (Default: Rp 100).</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1.5">
                            Minimal Penukaran Poin
                        </label>
                        <div class="relative">
                            <input type="number" step="5" wire:model="loyalty_min_redeem_points"
                                class="w-full px-3.5 py-2 text-xs bg-gray-50 border border-gray-200 rounded-xl font-bold text-gray-900 focus:bg-white focus:border-neutral-900 focus:outline-none">
                            <span class="absolute right-3.5 top-2.5 text-xs text-gray-400 font-bold">Poin</span>
                        </div>
                        <p class="text-[10px] text-gray-400 mt-1">Batas minimum poin untuk dapat ditukarkan (Default: 50).</p>
                    </div>
                </div>

                {{-- Ambang Batas Tier (Tier Spend Thresholds) --}}
                <div class="pt-4 border-t border-gray-100">
                    <h4 class="font-bold text-sm text-gray-900 mb-1">Ambang Batas Status Tier Member</h4>
                    <p class="text-xs text-gray-500 mb-4">
                        Tentukan syarat akumulasi total belanja pelanggan untuk otomatis naik ke tingkat membership yang lebih tinggi.
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="w-3 h-3 rounded-full bg-slate-400"></span>
                                <span class="font-bold text-xs text-slate-800">SILVER MEMBER</span>
                            </div>
                            <div class="relative">
                                <span class="absolute left-3.5 top-2.5 text-xs text-gray-400 font-bold">Rp</span>
                                <input type="number" step="500000" wire:model="loyalty_silver_min_spend"
                                    class="w-full pl-10 pr-3 py-2 text-xs bg-white border border-gray-200 rounded-xl font-bold text-gray-900 focus:outline-none">
                            </div>
                            <p class="text-[10px] text-gray-400 mt-1.5">Default: Rp 5.000.000</p>
                        </div>

                        <div class="p-4 rounded-2xl bg-amber-50/60 border border-amber-200">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                                <span class="font-bold text-xs text-amber-900">GOLD MEMBER</span>
                            </div>
                            <div class="relative">
                                <span class="absolute left-3.5 top-2.5 text-xs text-gray-400 font-bold">Rp</span>
                                <input type="number" step="1000000" wire:model="loyalty_gold_min_spend"
                                    class="w-full pl-10 pr-3 py-2 text-xs bg-white border border-gray-200 rounded-xl font-bold text-gray-900 focus:outline-none">
                            </div>
                            <p class="text-[10px] text-gray-400 mt-1.5">Default: Rp 15.000.000</p>
                        </div>

                        <div class="p-4 rounded-2xl bg-slate-900 text-white border border-slate-800">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="w-3 h-3 rounded-full bg-cyan-400"></span>
                                <span class="font-bold text-xs text-slate-100">PLATINUM MEMBER</span>
                            </div>
                            <div class="relative">
                                <span class="absolute left-3.5 top-2.5 text-xs text-slate-400 font-bold">Rp</span>
                                <input type="number" step="5000000" wire:model="loyalty_platinum_min_spend"
                                    class="w-full pl-10 pr-3 py-2 text-xs bg-slate-800 border border-slate-700 rounded-xl font-bold text-white focus:outline-none">
                            </div>
                            <p class="text-[10px] text-slate-400 mt-1.5">Default: Rp 50.000.000</p>
                        </div>
                    </div>
                </div>

                {{-- Action Button --}}
                <div class="pt-4 flex justify-end">
                    <button type="submit" wire:loading.attr="disabled"
                        class="px-6 py-2.5 rounded-xl font-bold text-xs bg-neutral-900 hover:bg-neutral-800 text-white shadow-md transition flex items-center gap-2">
                        <span wire:loading.remove wire:target="saveSettings">💾 Simpan Perubahan Pengaturan</span>
                        <span wire:loading wire:target="saveSettings">Menyimpan...</span>
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- ============================================================== --}}
    {{-- MODAL 1: RIWAYAT TRANSAKSI LEDGER MEMBER --}}
    {{-- ============================================================== --}}
    @if ($showHistoryModal && $selectedAccount)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden border border-gray-100 animate-in fade-in zoom-in-95 duration-150">
                <div class="p-5 border-b border-gray-100 flex items-center justify-between bg-gray-50/80">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center font-black">
                            👑
                        </div>
                        <div>
                            <h3 class="font-black text-sm text-gray-900">Riwayat Mutasi Poin</h3>
                            <p class="text-[11px] text-gray-500 font-mono">{{ $selectedAccount->phone_number }} ({{ $selectedAccount->customer_name ?: 'Pelanggan' }})</p>
                        </div>
                    </div>
                    <button wire:click="closeHistoryModal" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div class="p-5 max-h-96 overflow-y-auto space-y-2.5">
                    @forelse ($selectedAccount->transactions as $tx)
                        @php
                            $isAddition = $tx->points > 0;
                            $txColor = $isAddition ? 'text-emerald-600' : 'text-rose-600';
                            $txBg = $isAddition ? 'bg-emerald-50 border-emerald-200' : 'bg-rose-50 border-rose-200';
                            $badgeChannel = $tx->channel === 'POS' ? 'Kasir Cabang (POS)' : ($tx->channel === 'MOBILE_APP' ? 'Aplikasi Mobile' : 'Manual Admin');
                        @endphp
                        <div class="p-3.5 rounded-2xl border bg-white flex items-center justify-between shadow-2xs">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">
                                        {{ $badgeChannel }}
                                    </span>
                                    <span class="text-[10px] font-extrabold uppercase {{ $isAddition ? 'text-emerald-700' : 'text-rose-700' }}">
                                        {{ $tx->type }}
                                    </span>
                                </div>
                                <p class="text-xs font-semibold text-gray-800">{{ $tx->description ?: 'Mutasi Poin' }}</p>
                                <p class="text-[10px] text-gray-400">{{ $tx->created_at->format('d M Y, H:i') }} • Saldo Akhir: {{ number_format($tx->balance_after) }} Pts</p>
                            </div>
                            <div class="text-right">
                                <span class="text-sm font-black {{ $txColor }}">
                                    {{ $isAddition ? '+' : '' }}{{ number_format($tx->points) }} Pts
                                </span>
                                @if($tx->discount_amount > 0)
                                    <p class="text-[10px] font-bold text-rose-500 mt-0.5">-Rp {{ number_format($tx->discount_amount) }}</p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 text-gray-400 text-xs">
                            Belum ada riwayat transaksi poin untuk member ini.
                        </div>
                    @endforelse
                </div>

                <div class="p-4 border-t border-gray-100 bg-gray-50 flex justify-end">
                    <button wire:click="closeHistoryModal" class="px-4 py-2 rounded-xl text-xs font-bold bg-white border border-gray-200 text-gray-700 hover:bg-gray-100">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ============================================================== --}}
    {{-- MODAL 2: PENYESUAIAN POIN MANUAL --}}
    {{-- ============================================================== --}}
    @if ($showAdjustModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden border border-gray-100 animate-in fade-in zoom-in-95 duration-150">
                <div class="p-5 border-b border-gray-100 flex items-center justify-between bg-gray-50/80">
                    <div>
                        <h3 class="font-black text-sm text-gray-900">Penyesuaian Poin Manual</h3>
                        <p class="text-[11px] text-gray-500">Kredit atau debit saldo poin untuk akun pelanggan.</p>
                    </div>
                    <button wire:click="closeAdjustModal" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <form wire:submit="saveAdjustment" class="p-5 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1.5">Tipe Penyesuaian</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" wire:click="$set('adjustType', 'ADD')"
                                class="py-2 rounded-xl text-xs font-bold border transition {{ $adjustType === 'ADD' ? 'bg-emerald-500 text-white border-emerald-500 shadow-xs' : 'bg-gray-50 text-gray-600 border-gray-200' }}">
                                ➕ Tambah Poin (Bonus)
                            </button>
                            <button type="button" wire:click="$set('adjustType', 'DEDUCT')"
                                class="py-2 rounded-xl text-xs font-bold border transition {{ $adjustType === 'DEDUCT' ? 'bg-rose-500 text-white border-rose-500 shadow-xs' : 'bg-gray-50 text-gray-600 border-gray-200' }}">
                                ➖ Kurangi Poin (Koreksi)
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1.5">Jumlah Poin</label>
                        <input type="number" step="1" min="1" wire:model="adjustPoints"
                            class="w-full px-3.5 py-2 text-xs bg-gray-50 border border-gray-200 rounded-xl font-bold text-gray-900 focus:bg-white focus:border-neutral-900 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1.5">Keterangan / Alasan</label>
                        <textarea rows="2" wire:model="adjustDescription"
                            placeholder="Contoh: Bonus kompensasi layanan CS / Promo loyalty..."
                            class="w-full px-3.5 py-2 text-xs bg-gray-50 border border-gray-200 rounded-xl text-gray-900 focus:bg-white focus:border-neutral-900 focus:outline-none"></textarea>
                    </div>

                    <div class="pt-3 border-t border-gray-100 flex items-center justify-end gap-2">
                        <button type="button" wire:click="closeAdjustModal" class="px-4 py-2 rounded-xl text-xs font-bold bg-white border border-gray-200 text-gray-700 hover:bg-gray-100">
                            Batal
                        </button>
                        <button type="submit" wire:loading.attr="disabled"
                            class="px-5 py-2 rounded-xl text-xs font-bold bg-neutral-900 hover:bg-neutral-800 text-white shadow-sm transition">
                            <span wire:loading.remove wire:target="saveAdjustment">Terapkan Penyesuaian</span>
                            <span wire:loading wire:target="saveAdjustment">Memproses...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>

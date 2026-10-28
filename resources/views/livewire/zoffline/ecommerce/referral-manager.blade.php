<div class="p-4 md:p-6 space-y-6">
    {{-- Top Header Partial --}}
    @include('livewire.zoffline.ecommerce.partials.header', [
        'title' => 'Manajemen Referral & Komisi Afiliasi'
    ])

    {{-- Feedback Alert --}}
    @if($feedbackMessage)
        <div class="p-4 rounded-2xl flex items-center justify-between gap-3 {{ $feedbackType === 'success' ? 'bg-emerald-50 border border-emerald-200 text-emerald-800' : 'bg-rose-50 border border-rose-200 text-rose-800' }}">
            <div class="flex items-center gap-3">
                <span class="text-lg">{{ $feedbackType === 'success' ? 'âœ…' : 'âš ï¸' }}</span>
                <p class="text-xs font-bold leading-relaxed">{{ $feedbackMessage }}</p>
            </div>
            <button wire:click="$set('feedbackMessage', null)" class="text-xs font-black opacity-60 hover:opacity-100">âœ•</button>
        </div>
    @endif

    {{-- Top Overview Stat Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        {{-- Card 1: Withdrawals Pending --}}
        <div class="bg-white p-4.5 rounded-2xl border border-gray-100 shadow-xs flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 border border-amber-200/50">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div>
                <div class="flex items-center gap-1.5">
                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Tarik Menunggu</p>
                    @if($stats['total_pending_withdrawals_count'] > 0)
                        <span class="px-1.5 py-0.2 bg-amber-500 text-white rounded-md text-[9px] font-black animate-pulse">
                            {{ $stats['total_pending_withdrawals_count'] }}
                        </span>
                    @endif
                </div>
                <h4 class="text-xl font-black text-amber-600 mt-0.5">
                    Rp {{ number_format($stats['total_pending_withdrawals_amount'], 0, ',', '.') }}
                </h4>
                <p class="text-[10px] text-gray-400">{{ $stats['total_pending_withdrawals_count'] }} pengajuan perlu diproses</p>
            </div>
        </div>

        {{-- Card 2: Total Ditransfer --}}
        <div class="bg-white p-4.5 rounded-2xl border border-gray-100 shadow-xs flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 border border-emerald-200/50">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Komisi Ditransfer</p>
                <h4 class="text-xl font-black text-emerald-600 mt-0.5">
                    Rp {{ number_format($stats['total_withdrawn_transferred'], 0, ',', '.') }}
                </h4>
                <p class="text-[10px] text-gray-400">Pencairan sukses</p>
            </div>
        </div>

        {{-- Card 3: Komisi Hold H+7 --}}
        <div class="bg-white p-4.5 rounded-2xl border border-gray-100 shadow-xs flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0 border border-indigo-200/50">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Komisi Hold H+7</p>
                <h4 class="text-xl font-black text-indigo-600 mt-0.5">
                    Rp {{ number_format($stats['total_commissions_pending_hold'], 0, ',', '.') }}
                </h4>
                <p class="text-[10px] text-gray-400">Masa cooling-off garansi</p>
            </div>
        </div>

        {{-- Card 4: Total Member Afiliasi --}}
        <div class="bg-white p-4.5 rounded-2xl border border-gray-100 shadow-xs flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0 border border-purple-200/50">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
            </div>
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Member Afiliasi</p>
                <h4 class="text-xl font-black text-gray-900 mt-0.5">
                    {{ number_format($stats['total_referral_users']) }}
                </h4>
                <p class="text-[10px] text-gray-400">Total kode aktif diterbitkan</p>
            </div>
        </div>
    </div>

    {{-- Navigation Tabs --}}
    <div class="flex items-center gap-2 border-b border-gray-200 pb-3">
        <button wire:click="setTab('withdrawals')"
            class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 {{ $activeTab === 'withdrawals' ? 'bg-neutral-900 text-white shadow-xs' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
            <span>ðŸ’¸</span>
            <span>Pengajuan Penarikan Dana</span>
            @if($stats['total_pending_withdrawals_count'] > 0)
                <span class="bg-amber-500 text-white text-[10px] px-2 py-0.2 rounded-full font-black">
                    {{ $stats['total_pending_withdrawals_count'] }}
                </span>
            @endif
        </button>

        <button wire:click="setTab('commissions')"
            class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 {{ $activeTab === 'commissions' ? 'bg-neutral-900 text-white shadow-xs' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
            <span>ðŸ“‹</span>
            <span>Ledger Komisi Pesanan</span>
        </button>

        <button wire:click="setTab('members')"
            class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 {{ $activeTab === 'members' ? 'bg-neutral-900 text-white shadow-xs' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
            <span>ðŸ‘¥</span>
            <span>Member & Performa Afiliasi</span>
        </button>

        <button wire:click="setTab('settings')"
            class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 {{ $activeTab === 'settings' ? 'bg-neutral-900 text-white shadow-xs' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
            <span>âš™ï¸</span>
            <span>Pengaturan Program</span>
        </button>
    </div>

    {{-- TAB 1: WITHDRAWALS CONTENT --}}
    @if($activeTab === 'withdrawals')
        <div class="bg-white rounded-2xl border border-gray-100 shadow-xs overflow-hidden">
            <div class="p-4 border-b border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-3 bg-gray-50/50">
                <div class="flex items-center gap-2 flex-1 max-w-md">
                    <input type="text" wire:model.live.debounce.300ms="withdrawalSearch"
                        placeholder="Cari nama member, email, atau no. rekening..."
                        class="w-full text-xs rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 bg-white" />
                </div>
                <div class="flex items-center gap-2">
                    <select wire:model.live="filterWithdrawalStatus"
                        class="text-xs rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 bg-white">
                        <option value="">Semua Status</option>
                        <option value="PENDING">Menunggu Persetujuan (Pending)</option>
                        <option value="TRANSFERRED">Berhasil Ditransfer</option>
                        <option value="REJECTED">Ditolak</option>
                    </select>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-gray-600">
                    <thead class="bg-gray-50/80 text-[11px] font-bold text-gray-400 uppercase tracking-wider border-b border-gray-100">
                        <tr>
                            <th class="py-3 px-4">Tgl Pengajuan</th>
                            <th class="py-3 px-4">Member</th>
                            <th class="py-3 px-4">Rekening Tujuan</th>
                            <th class="py-3 px-4 text-right">Nominal</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4">Petugas / Bukti</th>
                            <th class="py-3 px-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($withdrawals as $item)
                            <tr class="hover:bg-gray-50/60 transition-colors">
                                <td class="py-3 px-4 text-[11px] text-gray-500 whitespace-nowrap">
                                    {{ $item->created_at->format('d M Y, H:i') }}
                                </td>
                                <td class="py-3 px-4">
                                    <p class="font-bold text-gray-900">{{ $item->user->name ?? 'User #' . $item->user_id }}</p>
                                    <p class="text-[10px] text-gray-400">{{ $item->user->email ?? '-' }}</p>
                                    @if($item->user->profile?->phone_number)
                                        <p class="text-[10px] text-indigo-600 font-medium">ðŸ“± {{ $item->user->profile->phone_number }}</p>
                                    @endif
                                </td>
                                <td class="py-3 px-4">
                                    @if($item->bankAccount)
                                        <span class="inline-block px-1.5 py-0.2 rounded bg-gray-100 text-gray-800 text-[10px] font-black uppercase">
                                            {{ $item->bankAccount->bank_name }}
                                        </span>
                                        <p class="font-mono font-bold text-gray-900 mt-0.5">{{ $item->bankAccount->account_number }}</p>
                                        <p class="text-[10px] text-gray-500">a.n. {{ $item->bankAccount->account_name }}</p>
                                    @else
                                        <span class="text-rose-500 text-[11px] italic">Rekening telah dihapus</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right whitespace-nowrap">
                                    <p class="font-black text-gray-900 text-sm">
                                        Rp {{ number_format($item->net_amount, 0, ',', '.') }}
                                    </p>
                                </td>
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    @if($item->status === 'PENDING')
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-amber-100 text-amber-800 border border-amber-200">
                                            MENUNGGU
                                        </span>
                                    @elseif($item->status === 'TRANSFERRED')
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            DITRANSFER
                                        </span>
                                    @elseif($item->status === 'REJECTED')
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-rose-100 text-rose-800 border border-rose-200">
                                            DITOLAK
                                        </span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-gray-100 text-gray-800">
                                            {{ $item->status }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-[11px]">
                                    @if($item->handler)
                                        <p class="text-gray-700 font-medium">Oleh: {{ $item->handler->name }}</p>
                                    @endif
                                    @if($item->proof_attachment_url)
                                        <a href="{{ $item->proof_attachment_url }}" target="_blank"
                                            class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-800 font-bold underline text-[10px] mt-0.5">
                                            <span>ðŸ“Ž</span>
                                            <span>Bukti Transfer</span>
                                        </a>
                                    @endif
                                    @if($item->notes)
                                        <p class="text-[10px] text-gray-500 line-clamp-1 mt-0.5">{{ $item->notes }}</p>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    @if($item->status === 'PENDING')
                                        <div class="flex items-center justify-center gap-1.5">
                                            <button wire:click="openApproveModal({{ $item->id }})"
                                                class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-bold text-[11px] shadow-xs transition-colors">
                                                Transfer
                                            </button>
                                            <button wire:click="openRejectModal({{ $item->id }})"
                                                class="px-2.5 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg font-bold text-[11px] transition-colors">
                                                Tolak
                                            </button>
                                        </div>
                                    @else
                                        <span class="text-gray-400 text-[11px]">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-gray-400">
                                    <p class="text-2xl mb-1">ðŸ“­</p>
                                    <p class="font-medium">Tidak ada permohonan penarikan dana ditemukan.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($withdrawals->hasPages())
                <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                    {{ $withdrawals->links() }}
                </div>
            @endif
        </div>
    @endif

    {{-- TAB 2: COMMISSIONS CONTENT --}}
    @if($activeTab === 'commissions')
        <div class="bg-white rounded-2xl border border-gray-100 shadow-xs overflow-hidden">
            <div class="p-4 border-b border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-3 bg-gray-50/50">
                <div class="flex items-center gap-2 flex-1 max-w-md">
                    <input type="text" wire:model.live.debounce.300ms="commissionSearch"
                        placeholder="Cari nama pengundang, pembeli, atau no. pesanan..."
                        class="w-full text-xs rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 bg-white" />
                </div>
                <div class="flex items-center gap-2">
                    <select wire:model.live="filterCommissionStatus"
                        class="text-xs rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 bg-white">
                        <option value="">Semua Status</option>
                        <option value="PENDING">PENDING (Hold H+7)</option>
                        <option value="APPROVED">APPROVED (Siap Ditarik)</option>
                        <option value="PAID">PAID (Telah Ditarik)</option>
                        <option value="VOID">VOID (Dibatalkan)</option>
                    </select>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-gray-600">
                    <thead class="bg-gray-50/80 text-[11px] font-bold text-gray-400 uppercase tracking-wider border-b border-gray-100">
                        <tr>
                            <th class="py-3 px-4">No. Pesanan</th>
                            <th class="py-3 px-4">Pengundang (Referrer)</th>
                            <th class="py-3 px-4">Teman Pembeli (Referee)</th>
                            <th class="py-3 px-4 text-right">Nilai Belanja</th>
                            <th class="py-3 px-4 text-right">Komisi (%)</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4">Masa Hold (H+7)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($commissions as $item)
                            <tr class="hover:bg-gray-50/60 transition-colors">
                                <td class="py-3 px-4 font-mono font-bold text-gray-900 whitespace-nowrap">
                                    {{ $item->order->order_number ?? 'Order #' . $item->order_id }}
                                    <p class="text-[10px] text-gray-400 font-sans font-normal">{{ $item->created_at->format('d M Y, H:i') }}</p>
                                </td>
                                <td class="py-3 px-4">
                                    <p class="font-bold text-gray-900">{{ $item->referrer->name ?? '-' }}</p>
                                    <p class="text-[10px] text-gray-400">{{ $item->referrer->referral_code ?? '' }}</p>
                                </td>
                                <td class="py-3 px-4">
                                    <p class="font-bold text-gray-800">{{ $item->referee->name ?? '-' }}</p>
                                    <p class="text-[10px] text-gray-400">{{ $item->referee->email ?? '' }}</p>
                                </td>
                                <td class="py-3 px-4 text-right font-medium text-gray-700 whitespace-nowrap">
                                    Rp {{ number_format($item->order_amount, 0, ',', '.') }}
                                </td>
                                <td class="py-3 px-4 text-right whitespace-nowrap">
                                    <p class="font-black text-emerald-600 text-sm">
                                        +Rp {{ number_format($item->commission_amount, 0, ',', '.') }}
                                    </p>
                                    <p class="text-[10px] text-gray-400">Rate: {{ $item->commission_rate }}%</p>
                                </td>
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    @if($item->status === 'APPROVED')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            APPROVED
                                        </span>
                                    @elseif($item->status === 'PENDING')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-800 border border-amber-200">
                                            HOLD H+7
                                        </span>
                                    @elseif($item->status === 'VOID')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-100 text-rose-800 border border-rose-200">
                                            VOID (BATAL)
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-blue-100 text-blue-800 border border-blue-200">
                                            {{ $item->status }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-[11px] whitespace-nowrap text-gray-500">
                                    @if($item->hold_until)
                                        <p class="{{ $item->hold_until->isPast() ? 'text-gray-400' : 'text-amber-600 font-bold' }}">
                                            {{ $item->hold_until->format('d M Y') }}
                                        </p>
                                        <p class="text-[10px] text-gray-400">
                                            {{ $item->hold_until->diffForHumans() }}
                                        </p>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-gray-400">
                                    <p class="text-2xl mb-1">ðŸŽ</p>
                                    <p class="font-medium">Belum ada riwayat komisi referral yang tercatat.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($commissions->hasPages())
                <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                    {{ $commissions->links() }}
                </div>
            @endif
        </div>
    @endif

    {{-- TAB 3: MEMBERS & PERFORMANCE CONTENT --}}
    @if($activeTab === 'members')
        <div class="bg-white rounded-2xl border border-gray-100 shadow-xs overflow-hidden">
            <div class="p-4 border-b border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-3 bg-gray-50/50">
                <div class="flex items-center gap-2 flex-1 max-w-md">
                    <input type="text" wire:model.live.debounce.300ms="memberSearch"
                        placeholder="Cari nama member, nomor HP, atau kode referral..."
                        class="w-full text-xs rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 bg-white" />
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-gray-600">
                    <thead class="bg-gray-50/80 text-[11px] font-bold text-gray-400 uppercase tracking-wider border-b border-gray-100">
                        <tr>
                            <th class="py-3 px-4">Member</th>
                            <th class="py-3 px-4">Kode Referral Unik</th>
                            <th class="py-3 px-4 text-center">Teman Terdaftar</th>
                            <th class="py-3 px-4 text-center">Pesanan Selesai</th>
                            <th class="py-3 px-4">Tgl Registrasi</th>
                            <th class="py-3 px-4">Status Akun</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($members as $user)
                            <tr class="hover:bg-gray-50/60 transition-colors">
                                <td class="py-3 px-4">
                                    <p class="font-bold text-gray-900">{{ $user->name }}</p>
                                    <p class="text-[10px] text-gray-400">{{ $user->email }}</p>
                                    @if($user->profile?->phone_number)
                                        <p class="text-[10px] text-indigo-600 font-medium">ðŸ“± {{ $user->profile->phone_number }}</p>
                                    @endif
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <span class="px-2.5 py-1 bg-amber-50 text-amber-900 border border-amber-200/80 rounded-lg font-mono font-black text-xs tracking-wider">
                                        {{ $user->referral_code }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center font-bold text-gray-800 text-sm">
                                    {{ $user->referrals_count ?? 0 }}
                                </td>
                                <td class="py-3 px-4 text-center font-bold text-emerald-600 text-sm">
                                    {{ $user->referralCodeRecord?->total_converted_orders ?? 0 }}
                                </td>
                                <td class="py-3 px-4 text-[11px] text-gray-500 whitespace-nowrap">
                                    {{ $user->created_at->format('d M Y') }}
                                    <p class="text-[10px] text-gray-400">Kanal: {{ $user->registration_channel ?? 'POS/MOBILE' }}</p>
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    @if($user->mobile_activated_at)
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            MOBILE AKTIF
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-gray-100 text-gray-700">
                                            POS SAJA
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-gray-400">
                                    <p class="text-2xl mb-1">ðŸ‘¥</p>
                                    <p class="font-medium">Tidak ada member dengan kode referral ditemukan.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($members->hasPages())
                <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                    {{ $members->links() }}
                </div>
            @endif
        </div>
    @endif

    {{-- TAB 4: SETTINGS CONTENT --}}
    @if($activeTab === 'settings')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Form Pengaturan Utama --}}
            <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-xs p-6 space-y-6">
                <div>
                    <h3 class="text-base font-black text-gray-900 tracking-tight">Aturan & Tarif Komisi Afiliasi</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Konfigurasi persentase komisi, batasan, dan masa proteksi garansi toko.</p>
                </div>

                {{-- Status Program Switch --}}
                <div class="p-4 rounded-xl bg-gray-50 border border-gray-200/60 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-gray-900">Aktifkan Program Referral & Komisi</p>
                        <p class="text-[11px] text-gray-500 mt-0.5">Jika nonaktif, user tidak dapat membagikan kode dan komisi pesanan tidak dicatat.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" wire:model="referral_is_active" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                    </label>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Tipe Komisi --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Tipe Perhitungan Komisi</label>
                        <select wire:model="referral_commission_type"
                            class="w-full text-xs rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="PERCENTAGE">Persentase dari Total Belanja (%)</option>
                            <option value="FIXED">Nominal Tetap per Pesanan (Rp)</option>
                        </select>
                    </div>

                    {{-- Nilai Rate Komisi --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">
                            {{ $referral_commission_type === 'PERCENTAGE' ? 'Nilai Persentase Komisi (%)' : 'Nominal Komisi Flat (Rp)' }}
                        </label>
                        <input type="number" step="0.01" wire:model="referral_commission_rate"
                            class="w-full text-xs rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500" />
                        <span class="text-[10px] text-gray-400">Rekomendasi retail gadget: 1.0% - 2.5%</span>
                    </div>

                    {{-- Ruang Lingkup Reward --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Cakupan Pemberian Komisi</label>
                        <select wire:model="referral_reward_scope"
                            class="w-full text-xs rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="FIRST_ORDER_ONLY">Hanya Pesanan Pertama Teman (First Order)</option>
                            <option value="ALL_ORDERS">Setiap Pesanan Selesai (Lifetime)</option>
                        </select>
                        <span class="text-[10px] text-gray-400">Mencegah penyalahgunaan marketing berulang</span>
                    </div>

                    {{-- Batas Plafon Maksimal Komisi --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Batas Maksimal Komisi / Pesanan (Cap)</label>
                        <input type="number" step="1000" wire:model="referral_max_commission_cap"
                            class="w-full text-xs rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500" />
                        <span class="text-[10px] text-gray-400">Contoh: Rp 100.000 max per invoice</span>
                    </div>

                    {{-- Masa Tahan Anti-Fraud (Cooling-off) --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Masa Tahan Komisi (Hold Days)</label>
                        <input type="number" wire:model="referral_hold_days"
                            class="w-full text-xs rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500" />
                        <span class="text-[10px] text-gray-400">Default: 7 hari sesuai masa garansi tukar unit</span>
                    </div>

                    {{-- Minimal Penarikan --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Minimal Saldo Penarikan (Rp)</label>
                        <input type="number" step="5000" wire:model="referral_min_withdrawal"
                            class="w-full text-xs rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500" />
                        <span class="text-[10px] text-gray-400">Default: Rp 50.000</span>
                    </div>
                </div>

                <div class="pt-4 border-t border-gray-100 flex items-center justify-end">
                    <button wire:click="saveSettings"
                        class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-xs transition-colors flex items-center gap-2">
                        <span>ðŸ’¾</span>
                        <span>Simpan Perubahan Pengaturan</span>
                    </button>
                </div>
            </div>

            {{-- Kolom Kanan: Otomasi & Trigger Manual --}}
            <div class="space-y-6">
                <div class="bg-white rounded-2xl border border-gray-100 shadow-xs p-5 space-y-4">
                    <div class="flex items-center gap-2">
                        <span class="text-lg">â°</span>
                        <h4 class="text-sm font-black text-gray-900">Jadwal Otomasi (Scheduler)</h4>
                    </div>
                    <p class="text-xs text-gray-600 leading-relaxed">
                        Sistem secara otomatis mengeksekusi perintah <code class="bg-gray-100 px-1 py-0.5 rounded text-indigo-700 font-mono text-[11px]">referral:release-holds</code> setiap hari pada pukul <strong>01:00 AM</strong> untuk mengubah komisi yang telah melewati masa hold H+7 menjadi status <strong>APPROVED</strong>.
                    </p>
                    <div class="p-3 rounded-xl bg-indigo-50 border border-indigo-100/80">
                        <p class="text-[11px] font-bold text-indigo-900">Eksekusi Manual Sekarang?</p>
                        <p class="text-[10px] text-indigo-700 mt-0.5 mb-2.5">Jalankan pelepasan komisi matured tanpa menunggu jadwal malam.</p>
                        <button wire:click="releaseHoldsManually"
                            class="w-full py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition-colors shadow-xs">
                            Lepaskan Komisi Matured Sekarang
                        </button>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-gray-100 shadow-xs p-5 space-y-3">
                    <div class="flex items-center gap-2">
                        <span class="text-lg">ðŸ›¡ï¸</span>
                        <h4 class="text-sm font-black text-gray-900">Proteksi Anti-Fraud</h4>
                    </div>
                    <ul class="text-[11px] text-gray-600 space-y-2 list-disc list-inside">
                        <li>Komisi otomatis <strong>VOID</strong> jika pesanan dibatalkan (CANCELLED).</li>
                        <li>Self-referral diblokir (pengguna tidak bisa menggunakan kode miliknya sendiri).</li>
                        <li>Pencairan dana dicek manual oleh finance sebelum transfer bank dilakukan.</li>
                    </ul>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL: APPROVE WITHDRAWAL (TRANSFER) --}}
    @if($showApproveModal && $selectedWithdrawal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
            <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-gray-100 space-y-5 animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                    <div class="flex items-center gap-2">
                        <span class="text-lg">ðŸ’¸</span>
                        <h3 class="text-base font-black text-gray-900">Konfirmasi Transfer Komisi</h3>
                    </div>
                    <button wire:click="closeApproveModal" class="text-gray-400 hover:text-gray-600 text-sm font-bold">âœ•</button>
                </div>

                <div class="p-4 rounded-2xl bg-amber-50/70 border border-amber-200/80 space-y-2">
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-gray-500">Member:</span>
                        <span class="font-bold text-gray-900">{{ $selectedWithdrawal->user->name }}</span>
                    </div>
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-gray-500">Bank Tujuan:</span>
                        <span class="font-bold text-indigo-700 uppercase">{{ $selectedWithdrawal->bankAccount->bank_name ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-gray-500">Nomor Rekening:</span>
                        <span class="font-mono font-black text-gray-900">{{ $selectedWithdrawal->bankAccount->account_number ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-gray-500">Atas Nama:</span>
                        <span class="font-bold text-gray-900">{{ $selectedWithdrawal->bankAccount->account_name ?? '-' }}</span>
                    </div>
                    <div class="pt-2 border-t border-amber-200/60 flex justify-between items-center">
                        <span class="text-xs font-black text-gray-700">Total Ditransfer:</span>
                        <span class="text-lg font-black text-emerald-700">Rp {{ number_format($selectedWithdrawal->net_amount, 0, ',', '.') }}</span>
                    </div>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Unggah Bukti Transfer (Opsional)</label>
                        <input type="file" wire:model="proofFile" accept="image/*"
                            class="block w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                        <div wire:loading wire:target="proofFile" class="text-[10px] text-indigo-600 mt-1 font-medium">Mengunggah file...</div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Catatan Tambahan (Opsional)</label>
                        <input type="text" wire:model="approvalNotes" placeholder="Contoh: No. Ref m-Banking: 98127391"
                            class="w-full text-xs rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                </div>

                <div class="pt-2 flex items-center justify-end gap-2">
                    <button wire:click="closeApproveModal"
                        class="px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">
                        Batal
                    </button>
                    <button wire:click="submitApprove" wire:loading.attr="disabled"
                        class="px-5 py-2.5 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-xs transition-colors">
                        Setujui & Tandai Ditransfer
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL: REJECT WITHDRAWAL --}}
    @if($showRejectModal && $rejectWithdrawal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
            <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-gray-100 space-y-4 animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                    <div class="flex items-center gap-2">
                        <span class="text-lg">âš ï¸</span>
                        <h3 class="text-base font-black text-gray-900">Tolak Permohonan Penarikan</h3>
                    </div>
                    <button wire:click="closeRejectModal" class="text-gray-400 hover:text-gray-600 text-sm font-bold">âœ•</button>
                </div>

                <p class="text-xs text-gray-600 leading-relaxed">
                    Saldo sebesar <strong class="text-gray-900">Rp {{ number_format($rejectWithdrawal->amount, 0, ',', '.') }}</strong> akan otomatis dikembalikan ke saldo aktif akun <strong>{{ $rejectWithdrawal->user->name }}</strong>.
                </p>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Alasan Penolakan (Wajib)</label>
                    <textarea wire:model="rejectionReason" rows="3"
                        placeholder="Contoh: Nomor rekening tidak cocok dengan nama nasabah di bank..."
                        class="w-full text-xs rounded-xl border-gray-200 focus:border-rose-500 focus:ring-rose-500"></textarea>
                    @error('rejectionReason')
                        <span class="text-rose-600 text-[11px] font-bold">{{ $message }}</span>
                    @enderror
                </div>

                <div class="pt-2 flex items-center justify-end gap-2">
                    <button wire:click="closeRejectModal"
                        class="px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">
                        Batal
                    </button>
                    <button wire:click="submitReject" wire:loading.attr="disabled"
                        class="px-5 py-2.5 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-xl shadow-xs transition-colors">
                        Tolak Permohonan
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
<div>
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900">Kelola Pesanan</h1>
            <p class="text-gray-500 text-sm mt-1">Pantau dan kelola seluruh transaksi pelanggan.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.orders.issues') }}" wire:navigate
                class="relative px-4 py-2 bg-amber-50 text-amber-800 border border-amber-200 rounded-lg hover:bg-amber-100 transition-colors text-sm font-bold flex items-center gap-2">
                <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                </svg>
                <span>Kendala Pesanan</span>
                @if (($openIssuesTotal ?? 0) > 0)
                    <span class="flex h-5 min-w-[20px] items-center justify-center rounded-full bg-rose-600 px-1.5 text-[11px] font-extrabold text-white animate-pulse">
                        {{ $openIssuesTotal }}
                    </span>
                @endif
            </a>

            <a href="{{ route('admin.orders.import-draft') }}" wire:navigate
                class="px-4 py-2 bg-[#1c69d4] text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-bold flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                Import via Draft
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white p-4 rounded-2xl shadow-sm border border-neutral-100-sm border border-gray-100 flex flex-col md:flex-row gap-4 mb-6">
        <div class="flex-1 relative">
            <svg class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none"
                viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input type="text" wire:model.live.debounce.300ms="search"
                placeholder="Cari No. Pesanan atau Nama Pembeli..."
                class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border-gray-200 rounded-lg text-sm focus:ring-[#1c69d4]/20 focus:border-[#1c69d4]">
        </div>
        <div class="w-full md:w-52 shrink-0">
            <select wire:model.live="channelFilter"
                class="w-full px-4 py-2.5 bg-gray-50 border-gray-200 rounded-lg text-sm focus:ring-[#1c69d4]/20 focus:border-[#1c69d4]">
                <option value="">Semua Saluran</option>
                <option value="POS">Hanya Kasir POS</option>
                <option value="MOBILE_APP">Hanya Mobile App</option>
            </select>
        </div>
        <div class="w-full md:w-56 shrink-0">
            <select wire:model.live="statusFilter"
                class="w-full px-4 py-2.5 bg-gray-50 border-gray-200 rounded-lg text-sm focus:ring-[#1c69d4]/20 focus:border-[#1c69d4]">
                <option value="">Semua Status</option>
                <option value="WAITING_VERIFICATION">Menunggu Verifikasi (Mobile)</option>
                <option value="WAITING_PAYMENT">Menunggu Bayar (Mobile)</option>
                <option value="PENDING">Pending (POS)</option>
                <option value="PROCESSING">Diproses</option>
                <option value="SHIPPED">Dikirim</option>
                <option value="COMPLETED">Selesai</option>
                <option value="CANCELLED">Dibatalkan</option>
            </select>
        </div>
        <div class="w-full md:w-52 shrink-0">
            <select wire:model.live="warehouseFilter"
                class="w-full px-4 py-2.5 bg-gray-50 border-gray-200 rounded-lg text-sm focus:ring-[#1c69d4]/20 focus:border-[#1c69d4]">
                <option value="">Semua Warehouse</option>
                @foreach ($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Orders Table --}}
    <div class="bg-white rounded-2xl shadow-sm border border-neutral-100-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-xs text-gray-500 uppercase tracking-wider">
                        <th class="px-6 py-4 font-bold">No. Pesanan</th>
                        <th class="px-6 py-4 font-bold">Pembeli & Waktu</th>
                        <th class="px-6 py-4 font-bold">Items & Total</th>
                        <th class="px-6 py-4 font-bold">Status</th>
                        <th class="px-6 py-4 font-bold text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 align-top">
                    @forelse ($orders as $order)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="font-bold text-gray-900 text-sm">{{ $order->order_number }}</span>
                                    @if ($order->order_channel === 'MOBILE_APP')
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                            📱 Mobile
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                            🏪 POS
                                        </span>
                                    @endif
                                </div>
                                <div class="text-[10px] text-gray-400 font-mono mt-1 select-all"
                                    title="Klik untuk menyalin (segera hadir)">
                                    ID: {{ $order->id }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-bold text-gray-800 text-sm">{{ $order->user->name ?? 'User Terhapus' }}
                                </p>
                                <p class="text-xs text-gray-500 mt-1">{{ $order->created_at->format('d M Y, H:i') }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm font-semibold text-gray-800">{{ $order->items->count() }} Item</p>
                                <p class="text-sm font-black text-[#1c69d4] mt-1">Rp
                                    {{ number_format($order->grand_total, 0, ',', '.') }}</p>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $statusColors = [
                                        'WAITING_PAYMENT' => 'bg-amber-50 text-amber-600 border-amber-200',
                                        'WAITING_VERIFICATION' => 'bg-orange-50 text-orange-700 border-orange-300 font-black animate-pulse',
                                        'PENDING' => 'bg-amber-50 text-amber-600 border-amber-100',
                                        'PROCESSING' => 'bg-blue-50 text-blue-600 border-blue-100',
                                        'SHIPPED' => 'bg-purple-50 text-purple-600 border-purple-100',
                                        'COMPLETED' => 'bg-emerald-50 text-emerald-600 border-emerald-100',
                                        'CANCELLED' => 'bg-rose-50 text-rose-600 border-rose-100',
                                    ];
                                    $statusLabels = [
                                        'WAITING_PAYMENT' => 'Menunggu Bayar',
                                        'WAITING_VERIFICATION' => 'Verifikasi Bukti',
                                        'PENDING' => 'Pending (POS)',
                                        'PROCESSING' => 'Diproses',
                                        'SHIPPED' => 'Dikirim',
                                        'COMPLETED' => 'Selesai',
                                        'CANCELLED' => 'Dibatalkan',
                                    ];
                                @endphp
                                <span
                                    class="text-xs font-bold px-3 py-1 rounded-lg border {{ $statusColors[$order->order_status] ?? 'bg-gray-100 text-gray-600' }}">
                                    {{ $statusLabels[$order->order_status] ?? $order->order_status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    {{-- Quick Actions for Order Progress --}}
                                    @if ($order->order_status === 'WAITING_VERIFICATION')
                                        <button wire:click="openVerification({{ $order->id }})"
                                            class="text-xs font-bold bg-amber-500 hover:bg-amber-600 text-white px-3 py-1.5 rounded-lg transition flex items-center gap-1 shadow-sm">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Verifikasi
                                        </button>
                                    @elseif ($order->order_status === 'WAITING_PAYMENT')
                                        <button wire:click="updateOrderStatus({{ $order->id }}, 'CANCELLED')"
                                            wire:confirm="Batalkan pesanan mobile yang belum dibayar ini?"
                                            class="text-xs font-bold bg-rose-50 text-rose-600 hover:bg-rose-100 px-2.5 py-1.5 rounded-lg transition">
                                            Batal
                                        </button>
                                    @elseif ($order->order_status === 'PENDING')
                                        <button wire:click="updateOrderStatus({{ $order->id }}, 'PROCESSING')"
                                            wire:confirm="Proses pesanan ini?"
                                            class="text-xs font-bold bg-blue-50 text-blue-600 hover:bg-blue-100 px-3 py-1.5 rounded-lg transition">
                                            Proses
                                        </button>
                                        <button wire:click="updateOrderStatus({{ $order->id }}, 'CANCELLED')"
                                            wire:confirm="Batalkan pesanan ini?"
                                            class="text-xs font-bold bg-rose-50 text-rose-600 hover:bg-rose-100 px-3 py-1.5 rounded-lg transition">
                                            Batal
                                        </button>
                                    @elseif ($order->order_status === 'PROCESSING')
                                        <button wire:click="updateOrderStatus({{ $order->id }}, 'SHIPPED')"
                                            wire:confirm="Tandai pesanan telah dikirim?"
                                            class="text-xs font-bold bg-purple-50 text-purple-600 hover:bg-purple-100 px-3 py-1.5 rounded-lg transition">
                                            Kirim
                                        </button>
                                    @endif

                                    {{-- ─── TOMBOL RE-SEND KHUSUS ADMIN ─── --}}
                                    @if (Auth::user()->hasRole('admin'))
                                        <button wire:click="resendEmail({{ $order->id }})"
                                            class="p-1 text-blue-500 hover:bg-blue-50 rounded-lg transition"
                                            title="Kirim Ulang Email (Admin)">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                        </button>

                                        @if (\App\Services\CrmWhatsAppService::isWhatsAppEnabled())
                                        @if (\App\Services\CrmWhatsAppService::isCrmActive())
                                        <button wire:click="resendCrmWhatsApp({{ $order->id }})"
                                            class="p-1 text-teal-600 hover:bg-teal-50 rounded-lg transition"
                                            title="Kirim Ulang CRM WA Zed (Admin)">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                            </svg>
                                        </button>
                                        @else
                                        <button wire:click="resendWhatsApp({{ $order->id }})"
                                            class="p-1 text-emerald-500 hover:bg-emerald-50 rounded-lg transition"
                                            title="Kirim Ulang WA Qontak (Admin)">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                            </svg>
                                        </button>
                                        @endif
                                        @endif
                                    @endif

                                    {{-- Order Detail Button (Struk) --}}
                                    <button wire:click="viewReceipt({{ $order->id }})"
                                        class="p-1.5 text-gray-400 hover:text-[#1c69d4] hover:bg-blue-50 rounded-lg transition"
                                        title="Lihat Struk">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                            stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>

                                    {{-- Order Issues / Comments Button --}}
                                    <button wire:click="openIssues({{ $order->id }})"
                                        class="relative p-1.5 text-gray-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition"
                                        title="Catatan & Kesalahan Order">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                        </svg>
                                        @if (($order->open_issues_count ?? 0) > 0)
                                            <span class="absolute -top-1 -right-1 flex h-4 min-w-[16px] items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-extrabold text-white animate-pulse">
                                                {{ $order->open_issues_count }}
                                            </span>
                                        @endif
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <svg class="w-12 h-12 text-gray-200 mx-auto mb-3" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                <p class="text-gray-500 font-medium">Belum ada pesanan.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($orders->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
                {{ $orders->links() }}
            </div>
        @endif
    </div>

    {{-- MODAL: Receipt (Struk) Khusus View Admin --}}
    @include('livewire.zoffline.pos.modal.riwayat-receipt')

    {{-- MODAL: Catatan & Kesalahan Order (Issues) --}}
    @include('livewire.admin.orders.modal.order-issues-modal')

    {{-- MODAL: Verifikasi Bukti Pembayaran Mobile App --}}
    @if ($showVerificationModal && $selectedMobileOrder)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <div class="bg-white rounded-2xl shadow-2xl border border-gray-100 max-w-2xl w-full overflow-hidden flex flex-col max-h-[90vh]">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/70">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-extrabold text-gray-900 text-base">Verifikasi Bukti Pembayaran</h3>
                            <p class="text-xs text-gray-500 font-mono">Pesanan #{{ $selectedMobileOrder->order_number }}</p>
                        </div>
                    </div>
                    <button wire:click="closeVerification" class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 overflow-y-auto space-y-6">
                    <!-- Bukti Transfer Image -->
                    @php
                        $payment = $selectedMobileOrder->payments->last();
                        $proofUrl = $payment && $payment->hasMedia('payment_proof') ? $payment->getFirstMediaUrl('payment_proof') : null;
                    @endphp
                    <div>
                        <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Foto Bukti Transfer</h4>
                        @if ($proofUrl)
                            <div class="relative bg-gray-100 rounded-xl p-2 border border-gray-200 flex justify-center max-h-80 overflow-hidden group">
                                <img src="{{ $proofUrl }}" alt="Bukti Transfer" class="max-h-76 object-contain rounded-lg">
                                <a href="{{ $proofUrl }}" target="_blank" class="absolute bottom-4 right-4 bg-black/70 hover:bg-black text-white text-xs px-3 py-1.5 rounded-lg shadow transition flex items-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                    Buka Ukuran Penuh
                                </a>
                            </div>
                        @else
                            <div class="bg-gray-50 border border-dashed border-gray-300 rounded-xl p-6 text-center text-gray-400 text-sm">
                                Tidak ada file bukti transfer yang terlampir.
                            </div>
                        @endif
                    </div>

                    <!-- Ringkasan Pembayaran & Pelanggan -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-gray-50 p-4 rounded-xl border border-gray-100 text-sm">
                        <div>
                            <span class="text-xs text-gray-400 block">Total Tagihan</span>
                            <span class="text-lg font-black text-[#1c69d4]">Rp {{ number_format($selectedMobileOrder->grand_total, 0, ',', '.') }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-gray-400 block">Metode Pembayaran</span>
                            <span class="font-bold text-gray-800">{{ $payment?->paymentMethod?->name ?? 'Transfer Bank' }}</span>
                            @if ($payment?->paymentMethod?->account_number)
                                <span class="block text-xs text-gray-500 font-mono">{{ $payment->paymentMethod->account_number }} (a.n. {{ $payment->paymentMethod->account_owner }})</span>
                            @endif
                        </div>
                        <div>
                            <span class="text-xs text-gray-400 block">Pembeli</span>
                            <span class="font-bold text-gray-800">{{ $selectedMobileOrder->user?->name ?? '-' }}</span>
                            <span class="block text-xs text-gray-500">{{ $selectedMobileOrder->user?->profile?->phone_number ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-gray-400 block">Gudang Penyedia</span>
                            <span class="font-bold text-gray-800">{{ $selectedMobileOrder->warehouse?->name ?? 'Gudang Online' }}</span>
                        </div>
                    </div>

                    <!-- Item Pesanan -->
                    <div>
                        <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Item Produk Pesanan</h4>
                        <div class="divide-y divide-gray-100 border border-gray-100 rounded-xl overflow-hidden bg-white">
                            @foreach ($selectedMobileOrder->items as $item)
                                <div class="p-3 flex items-center justify-between text-sm">
                                    <div>
                                        <p class="font-bold text-gray-800">{{ $item->product_name }}</p>
                                        @if ($item->serial_number)
                                            <p class="text-xs text-indigo-600 font-mono mt-0.5">SN / IMEI: {{ $item->serial_number }}</p>
                                        @endif
                                    </div>
                                    <div class="text-right">
                                        <span class="text-xs text-gray-500">{{ $item->qty }}x Rp {{ number_format($item->price_at_checkout, 0, ',', '.') }}</span>
                                        <p class="font-bold text-gray-900">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Modal Footer Actions -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex flex-wrap items-center justify-between gap-3">
                    <button wire:click="rejectMobilePayment({{ $selectedMobileOrder->id }})"
                        wire:confirm="Yakin ingin MENOLAK bukti transfer dan membatalkan pesanan ini? Stok dan nomor seri akan dikembalikan ke status Available."
                        class="px-4 py-2.5 bg-rose-50 text-rose-600 hover:bg-rose-100 font-bold text-sm rounded-xl transition">
                        Tolak & Batalkan Pesanan
                    </button>
                    <div class="flex items-center gap-2">
                        <button wire:click="closeVerification"
                            class="px-4 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold text-sm rounded-xl transition">
                            Tutup
                        </button>
                        <button wire:click="approveMobilePayment({{ $selectedMobileOrder->id }})"
                            wire:confirm="Setujui pembayaran pesanan ini? Pesanan akan diubah menjadi COMPLETED, status nomor seri menjadi Sold, dan transaksi otomatis disinkronkan ke Accurate Online."
                            class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm rounded-xl shadow-md hover:shadow-lg transition flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            Setujui Pembayaran
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>


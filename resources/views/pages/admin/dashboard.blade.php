<?php

use App\Models\Order;
use App\Models\User;
use App\Models\Product;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;

new #[Layout('layouts.admin', ['title' => 'Dashboard - TokoPun'])]
class extends Component {
    
    #[Computed]
    public function salesThisMonth()
    {
        return Order::where('created_at', '>=', Carbon::now()->startOfMonth())
            ->whereNotIn('order_status', ['cancelled', 'failed', 'batal'])
            ->sum('grand_total');
    }

    #[Computed]
    public function salesToday()
    {
        return Order::where('created_at', '>=', Carbon::today())
            ->whereNotIn('order_status', ['cancelled', 'failed', 'batal'])
            ->sum('grand_total');
    }

    #[Computed]
    public function ordersThisMonth()
    {
        return Order::where('created_at', '>=', Carbon::now()->startOfMonth())->count();
    }

    #[Computed]
    public function totalCustomers()
    {
        return User::whereHas('roles', function($q) {
            $q->where('name', 'user')->orWhere('name', 'customer');
        })->count();
    }
    
    #[Computed]
    public function totalProducts()
    {
        return Product::count();
    }

    #[Computed]
    public function recentOrders()
    {
        return Order::with('user')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
    }
};
?>

<div>
    {{-- Banner Utama --}}
    <div class="bg-linear-to-r from-[#1c1c1c] via-[#2d2d2d] to-[#1c1c1c] rounded-[2rem] p-8 text-white mb-8 shadow-2xl relative overflow-hidden">
        <div class="absolute -right-20 -bottom-20 w-64 h-64 bg-[#4E44DB]/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-10 -top-10 w-40 h-40 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
        
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <h1 class="text-3xl font-black tracking-tight mb-2">Selamat Datang, {{ auth()->user()->name ?? 'Admin' }}! 👋</h1>
                <p class="text-gray-300 text-sm font-medium">Berikut adalah ringkasan performa penjualan dan operasional toko Anda saat ini.</p>
            </div>
            <div class="bg-white/10 backdrop-blur-md border border-white/10 px-5 py-3 rounded-2xl text-sm font-bold flex items-center h-fit shadow-inner">
                <span class="relative flex h-3 w-3 mr-3">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                </span>
                Sistem Aktif & Terhubung
            </div>
        </div>
    </div>

    {{-- Cards Metrics --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        {{-- Card 1: Omset Hari Ini --}}
        <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-emerald-50 rounded-full group-hover:scale-150 transition-transform duration-500 ease-out z-0"></div>
            <div class="relative z-10">
                <div class="flex justify-between items-start mb-4">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center shadow-inner">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <span class="px-2.5 py-1 text-[10px] font-bold bg-emerald-50 border border-emerald-100 text-emerald-600 rounded-lg">HARI INI</span>
                </div>
                <h3 class="text-sm font-bold text-gray-500 mb-1">Omset Penjualan</h3>
                <p class="text-2xl font-black text-gray-900 tracking-tight">Rp {{ number_format($this->salesToday, 0, ',', '.') }}</p>
            </div>
        </div>

        {{-- Card 2: Omset Bulan Ini --}}
        <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-blue-50 rounded-full group-hover:scale-150 transition-transform duration-500 ease-out z-0"></div>
            <div class="relative z-10">
                <div class="flex justify-between items-start mb-4">
                    <div class="w-12 h-12 rounded-2xl bg-blue-100 text-blue-600 flex items-center justify-center shadow-inner">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                    <span class="px-2.5 py-1 text-[10px] font-bold bg-blue-50 border border-blue-100 text-blue-600 rounded-lg">BULAN INI</span>
                </div>
                <h3 class="text-sm font-bold text-gray-500 mb-1">Total Omset</h3>
                <p class="text-2xl font-black text-gray-900 tracking-tight">Rp {{ number_format($this->salesThisMonth, 0, ',', '.') }}</p>
            </div>
        </div>

        {{-- Card 3: Total Pesanan --}}
        <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-indigo-50 rounded-full group-hover:scale-150 transition-transform duration-500 ease-out z-0"></div>
            <div class="relative z-10">
                <div class="flex justify-between items-start mb-4">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-100 text-indigo-600 flex items-center justify-center shadow-inner">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </div>
                    <span class="px-2.5 py-1 text-[10px] font-bold bg-indigo-50 border border-indigo-100 text-indigo-600 rounded-lg">BULAN INI</span>
                </div>
                <h3 class="text-sm font-bold text-gray-500 mb-1">Pesanan Masuk</h3>
                <p class="text-2xl font-black text-gray-900 tracking-tight">{{ number_format($this->ordersThisMonth, 0, ',', '.') }} <span class="text-sm font-semibold text-gray-400">Trx</span></p>
            </div>
        </div>

        {{-- Card 4: Data Master --}}
        <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-orange-50 rounded-full group-hover:scale-150 transition-transform duration-500 ease-out z-0"></div>
            <div class="relative z-10">
                <div class="flex justify-between items-start mb-4">
                    <div class="w-12 h-12 rounded-2xl bg-orange-100 text-orange-600 flex items-center justify-center shadow-inner">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                </div>
                <h3 class="text-sm font-bold text-gray-500 mb-1">Pelanggan Terdaftar</h3>
                <p class="text-2xl font-black text-gray-900 tracking-tight">{{ number_format($this->totalCustomers, 0, ',', '.') }} <span class="text-sm font-semibold text-gray-400">User</span></p>
            </div>
        </div>
    </div>

    {{-- Tabel Pesanan Terbaru --}}
    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden mb-8">
        <div class="px-8 py-6 border-b border-gray-50 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h3 class="font-extrabold text-gray-900 text-lg">Pesanan Terbaru</h3>
                <p class="text-xs text-gray-400 mt-1 font-medium">5 transaksi terakhir yang masuk ke dalam sistem.</p>
            </div>
            @can('manage-orders')
            <a href="{{ route('admin.orders.management') }}" wire:navigate class="px-5 py-2.5 bg-gray-50 hover:bg-gray-100 text-gray-700 text-sm font-bold rounded-xl transition-colors border border-gray-200 shadow-xs flex items-center gap-2">
                Lihat Semua Pesanan
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
            </a>
            @endcan
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/50">
                        <th class="px-8 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">No. Trx</th>
                        <th class="px-8 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Pelanggan</th>
                        <th class="px-8 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Tanggal</th>
                        <th class="px-8 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Total Pembayaran</th>
                        <th class="px-8 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse ($this->recentOrders as $order)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-8 py-5">
                                <span class="font-mono text-sm font-bold text-[#4E44DB]">{{ $order->order_number }}</span>
                                <span class="block text-[10px] font-bold text-gray-400 mt-0.5 uppercase tracking-wider">{{ $order->order_channel ?? 'POS' }}</span>
                            </td>
                            <td class="px-8 py-5">
                                <p class="font-bold text-gray-800 text-sm">{{ $order->user->name ?? 'Guest / Kasir' }}</p>
                                @if($order->user && $order->user->email)
                                <p class="text-[11px] font-medium text-gray-400 mt-0.5">{{ $order->user->email }}</p>
                                @endif
                            </td>
                            <td class="px-8 py-5">
                                <p class="text-sm font-bold text-gray-700">{{ $order->created_at->format('d M Y') }}</p>
                                <p class="text-[11px] font-medium text-gray-400 mt-0.5">{{ $order->created_at->format('H:i') }} WIB</p>
                            </td>
                            <td class="px-8 py-5">
                                <p class="font-black text-gray-900 text-sm">Rp {{ number_format($order->grand_total, 0, ',', '.') }}</p>
                            </td>
                            <td class="px-8 py-5 text-right">
                                @php
                                    $statusStr = strtolower($order->order_status);
                                    $statusConfig = match(true) {
                                        in_array($statusStr, ['completed', 'selesai']) => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-600', 'border' => 'border-emerald-100', 'label' => 'Selesai'],
                                        in_array($statusStr, ['pending', 'menunggu_pembayaran']) => ['bg' => 'bg-orange-50', 'text' => 'text-orange-600', 'border' => 'border-orange-100', 'label' => 'Pending'],
                                        in_array($statusStr, ['processing', 'diproses']) => ['bg' => 'bg-blue-50', 'text' => 'text-blue-600', 'border' => 'border-blue-100', 'label' => 'Diproses'],
                                        in_array($statusStr, ['cancelled', 'failed', 'batal']) => ['bg' => 'bg-red-50', 'text' => 'text-red-600', 'border' => 'border-red-100', 'label' => 'Dibatalkan'],
                                        default => ['bg' => 'bg-gray-50', 'text' => 'text-gray-600', 'border' => 'border-gray-200', 'label' => ucfirst($order->order_status)],
                                    };
                                @endphp
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider border {{ $statusConfig['bg'] }} {{ $statusConfig['text'] }} {{ $statusConfig['border'] }}">
                                    {{ $statusConfig['label'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-8 py-16 text-center">
                                <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-gray-100">
                                    <svg class="w-8 h-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                    </svg>
                                </div>
                                <h3 class="text-sm font-bold text-gray-800">Belum ada pesanan</h3>
                                <p class="text-xs text-gray-400 mt-1">Transaksi penjualan belum tersedia untuk ditampilkan bulan ini.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

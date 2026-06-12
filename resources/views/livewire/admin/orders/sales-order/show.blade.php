<div>
    <div class="mb-6 flex justify-between items-start">
        <div>
            <a href="{{ route('admin.sales-orders.index') }}" wire:navigate class="text-sm font-medium text-gray-500 hover:text-[#1c69d4] flex items-center gap-1 mb-2 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali ke Daftar SO
            </a>
            <h1 class="text-2xl font-bold text-gray-800">Detail & Peta Relasi SO</h1>
            <p class="text-gray-500 text-sm mt-1">SO Number: {{ $order->order_number }}</p>
        </div>
        
        <div>
            @if($this->getRemainingBalance() > 0)
                <button type="button" wire:click="$set('showDpModal', true)" class="px-5 py-2.5 bg-emerald-50 text-emerald-600 hover:bg-emerald-100 font-bold rounded-xl text-sm transition-colors shadow-sm flex items-center gap-2 border border-emerald-200">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    Terima Pembayaran / DP
                </button>
            @endif
        </div>
    </div>

    {{-- Relationship Map (SAP B1 Style) --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 mb-6 overflow-x-auto">
        <h3 class="font-bold text-gray-800 mb-6 flex items-center gap-2">
            <svg class="w-5 h-5 text-[#1c69d4]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19V6l12-3v13M9 19c-1.657 0-3-1.343-3-3S7.343 13 9 13s3 1.343 3 3-1.343 3-3 3zm12-3c-1.657 0-3-1.343-3-3s1.343-3 3-3 3 1.343 3 3-1.343 3-3 3zM3 13v-3c0-2.21 1.79-4 4-4h16" />
            </svg>
            Relationship Map (Peta Dokumen)
        </h3>
        
        <div class="flex items-center justify-start min-w-[800px] py-4">
            {{-- Node 1: Sales Order --}}
            <div class="relative group">
                <div class="w-48 bg-[#eff6ff] border-2 border-[#1c69d4] rounded-xl p-4 text-center shadow-sm cursor-pointer hover:shadow-md transition-all">
                    <div class="text-[10px] font-bold text-[#1c69d4] uppercase tracking-wider mb-1">Sales Order</div>
                    <div class="text-xs font-bold text-gray-800 mb-1">{{ $order->order_number }}</div>
                    <div class="text-[10px] text-gray-500">{{ $order->order_date ? $order->order_date->format('d/m/Y') : '-' }}</div>
                </div>
            </div>

            {{-- Line --}}
            <div class="w-16 h-0.5 bg-gray-300 relative">
                <div class="absolute right-0 -top-1.5 w-3 h-3 border-t-2 border-r-2 border-gray-300 transform rotate-45"></div>
            </div>

            {{-- Node 2: Down Payment / Payments --}}
            <div class="flex flex-col gap-4">
                @if($order->payments->count() > 0)
                    @foreach($order->payments as $payment)
                        <div class="flex items-center">
                            <div class="relative group">
                                <div class="w-48 bg-emerald-50 border-2 border-emerald-500 rounded-xl p-4 text-center shadow-sm cursor-pointer hover:shadow-md transition-all">
                                    <div class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider mb-1">Pembayaran DP</div>
                                    <div class="text-xs font-bold text-gray-800 mb-1">Rp {{ number_format($payment->amount, 0, ',', '.') }}</div>
                                    <div class="text-[10px] text-gray-500">{{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y') : '-' }}</div>
                                </div>
                            </div>
                            
                            @if($loop->first)
                                {{-- Line --}}
                                <div class="w-16 h-0.5 bg-gray-300 relative">
                                    <div class="absolute right-0 -top-1.5 w-3 h-3 border-t-2 border-r-2 border-gray-300 transform rotate-45"></div>
                                </div>
                                
                                {{-- Node 3: Sales Invoice (Mock) --}}
                                <div class="relative group">
                                    <div class="w-48 bg-gray-50 border-2 border-gray-300 border-dashed rounded-xl p-4 text-center">
                                        <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Sales Invoice</div>
                                        <div class="text-xs font-bold text-gray-400 mb-1">Belum Terbit</div>
                                        <div class="text-[10px] text-gray-400">Menunggu Pelunasan</div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                @else
                    <div class="relative group">
                        <div class="w-48 bg-gray-50 border-2 border-gray-300 border-dashed rounded-xl p-4 text-center">
                            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Uang Muka (DP)</div>
                            <div class="text-xs font-bold text-gray-400 mb-1">Belum Ada Pembayaran</div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- SO Details --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-bold text-gray-800 mb-4 border-b border-gray-100 pb-3">Daftar Barang</h3>
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                            <th class="p-3 font-bold rounded-tl-lg">Produk</th>
                            <th class="p-3 font-bold text-center">Qty</th>
                            <th class="p-3 font-bold text-right">Harga</th>
                            <th class="p-3 font-bold text-right">Diskon</th>
                            <th class="p-3 font-bold text-right rounded-tr-lg">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($order->items as $item)
                            <tr>
                                <td class="p-3 text-sm font-semibold text-gray-800">
                                    {{ $item->variant->product->name ?? $item->variant->secondProduct->name ?? 'Unknown' }}
                                    <div class="text-xs font-normal text-gray-500">{{ $item->variant->storage ?? '' }} {{ $item->variant->color ?? '' }}</div>
                                </td>
                                <td class="p-3 text-sm text-center">{{ $item->quantity }}</td>
                                <td class="p-3 text-sm text-right">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                                <td class="p-3 text-sm text-right text-red-500">{{ $item->discount_amount > 0 ? '-Rp ' . number_format($item->discount_amount, 0, ',', '.') : '-' }}</td>
                                <td class="p-3 text-sm text-right font-bold text-gray-800">Rp {{ number_format($item->total_price, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-bold text-gray-800 mb-4 border-b border-gray-100 pb-3">Ringkasan Nilai SO</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between text-gray-500">
                        <span>Subtotal</span>
                        <span class="font-semibold text-gray-800">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-gray-500">
                        <span>Total Diskon</span>
                        <span class="font-semibold text-red-500">- Rp {{ number_format($order->discount_amount, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center pt-3 border-t border-gray-100">
                        <span class="font-bold text-gray-800">Grand Total</span>
                        <span class="font-black text-lg text-[#1c69d4]">Rp {{ number_format($order->grand_total, 0, ',', '.') }}</span>
                    </div>
                    
                    <div class="mt-4 bg-gray-50 rounded-xl p-4 border border-gray-200">
                        @php $paid = $order->payments->sum('amount'); @endphp
                        <div class="flex justify-between text-gray-500 mb-1">
                            <span>Total DP Dibayar</span>
                            <span class="font-bold text-emerald-600">Rp {{ number_format($paid, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between text-gray-500 border-t border-gray-200 pt-1 mt-1">
                            <span class="font-bold text-gray-800">Sisa Tagihan</span>
                            <span class="font-black text-rose-500">Rp {{ number_format($this->getRemainingBalance(), 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-bold text-gray-800 mb-4 border-b border-gray-100 pb-3">Informasi Tambahan</h3>
                <div class="space-y-3 text-sm">
                    <div>
                        <span class="block text-xs font-bold text-gray-400 uppercase">Pelanggan</span>
                        <span class="font-semibold text-gray-800">{{ $order->user->name ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="block text-xs font-bold text-gray-400 uppercase">Unit Usaha</span>
                        <span class="font-semibold text-gray-800">{{ $order->businessUnit->name ?? '-' }}</span>
                    </div>
                    @if($order->accurate_so_number)
                        <div>
                            <span class="block text-xs font-bold text-gray-400 uppercase">Accurate SO No.</span>
                            <span class="font-bold text-[#1c69d4]">{{ $order->accurate_so_number }}</span>
                        </div>
                    @endif
                    <div>
                        <span class="block text-xs font-bold text-gray-400 uppercase">Catatan</span>
                        <span class="text-gray-600">{{ $order->notes ?? '-' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- DP Modal --}}
    @if($showDpModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
                <div class="p-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                    <h3 class="font-bold text-gray-800">Terima Uang Muka (DP)</h3>
                    <button wire:click="$set('showDpModal', false)" class="text-gray-400 hover:text-rose-500 font-bold">&times;</button>
                </div>
                <form wire:submit="saveDp" class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Sisa Tagihan (Maksimal)</label>
                        <div class="text-lg font-black text-rose-500 bg-rose-50 p-3 rounded-xl border border-rose-100">
                            Rp {{ number_format($this->getRemainingBalance(), 0, ',', '.') }}
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Nominal DP Dibayar (Rp) *</label>
                        <input type="number" wire:model="dp_amount" max="{{ $this->getRemainingBalance() }}" class="w-full rounded-lg border-gray-300 text-sm focus:ring-emerald-500 focus:border-emerald-500 shadow-sm font-bold" required>
                        @error('dp_amount') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Tanggal Pembayaran *</label>
                        <input type="date" wire:model="dp_date" class="w-full rounded-lg border-gray-300 text-sm focus:ring-emerald-500 focus:border-emerald-500 shadow-sm" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Metode Pembayaran (Ke Rekening) *</label>
                        <select wire:model="payment_method_id" class="w-full rounded-lg border-gray-300 text-sm focus:ring-emerald-500 focus:border-emerald-500 shadow-sm" required>
                            <option value="">-- Pilih Rekening Penerima --</option>
                            @foreach($paymentMethods as $method)
                                <option value="{{ $method->id }}">{{ $method->name }}</option>
                            @endforeach
                        </select>
                        @error('payment_method_id') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                    </div>
                    
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Catatan/Referensi</label>
                        <input type="text" wire:model="dp_notes" class="w-full rounded-lg border-gray-300 text-sm focus:ring-emerald-500 focus:border-emerald-500 shadow-sm" placeholder="Contoh: Transfer BCA a/n Budi">
                    </div>

                    <div class="pt-4 border-t border-gray-100 flex justify-end gap-3">
                        <button type="button" wire:click="$set('showDpModal', false)" class="px-4 py-2 bg-gray-100 text-gray-700 font-bold rounded-lg hover:bg-gray-200 transition-colors">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-emerald-500 text-white font-bold rounded-lg hover:bg-emerald-600 transition-colors shadow-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            Simpan DP
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>

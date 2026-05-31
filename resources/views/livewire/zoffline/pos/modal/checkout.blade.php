 {{-- ═══════════════════════════════════════════════════════════
         MODAL: Checkout Confirmation
    ═══════════════════════════════════════════════════════════ --}}
 @if ($showCheckoutModal)
     <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
         <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
             <div class="p-5 bg-gray-50 border-b border-gray-100">
                 <h3 class="font-black text-gray-900 text-xl">Konfirmasi Pembayaran</h3>
                 <p class="text-sm text-gray-500 mt-1">Pastikan semua data sudah benar sebelum memproses.</p>
             </div>
             <div class="p-5 space-y-3 max-h-60 overflow-y-auto">
                 @foreach ($cart as $item)
                     <div class="flex justify-between text-sm border-b border-gray-50 pb-2">
                         <div>
                             <p class="font-bold text-gray-800">
                                 {{ $item['name'] }}
                                 <span class="text-gray-400">
                                     ({{ !empty($item['ram']) && $item['ram'] !== '-' ? $item['ram'] . ' /' : '' }}{{ $item['storage'] }}
                                     {{ $item['color'] }})
                                 </span>
                             </p>
                             <p class="text-[10px] text-gray-400 font-mono">SN: {{ $item['serial_number'] }}</p>
                         </div>
                         <p class="font-bold text-gray-700 whitespace-nowrap">{{ $item['qty'] }}x Rp
                             {{ number_format($item['price'], 0, ',', '.') }}</p>
                     </div>
                 @endforeach
             </div>
             <div class="p-5 bg-gray-50 border-t border-gray-100 space-y-1">
                 <div class="flex justify-between text-sm"><span class="text-gray-500">Subtotal</span><span
                         class="font-bold">Rp {{ number_format($this->subtotal, 0, ',', '.') }}</span></div>
                 @if ($this->discount_amount > 0)
                     <div class="flex justify-between text-sm"><span class="text-rose-500">Diskon</span><span
                             class="font-bold text-rose-500">- Rp
                             {{ number_format($this->discount_amount, 0, ',', '.') }}</span></div>
                 @endif
                 @if ($this->totalPromoDiscount > 0)
                     <div class="flex justify-between text-sm"><span class="text-rose-500">Promo</span><span
                             class="font-bold text-rose-500">- Rp
                             {{ number_format($this->totalPromoDiscount, 0, ',', '.') }}</span></div>
                 @endif
                 <div class="flex justify-between pt-2 border-t border-gray-200"><span
                         class="font-black text-lg">TOTAL</span><span class="font-black text-[#1c69d4] text-lg">Rp
                         {{ number_format($this->grandTotal, 0, ',', '.') }}</span></div>
             </div>
             <div class="p-5 flex gap-3">
                 <button wire:click="$set('showCheckoutModal', false)"
                     class="flex-1 py-3 rounded-xl font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 transition">Batal</button>
                 <button wire:click="processPayment" wire:loading.attr="disabled" wire:target="processPayment"
                     class="flex-1 py-3 rounded-xl font-bold text-white bg-[#1c69d4] hover:bg-blue-700 transition shadow-md shadow-blue-500/20">
                     <span wire:loading.remove wire:target="processPayment">Proses Bayar</span>
                     <span wire:loading wire:target="processPayment">Memproses...</span>
                 </button>
             </div>
         </div>
     </div>
 @endif

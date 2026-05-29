 {{-- ═══════════════════════════════════════════════════════════
         MODAL: Variant Picker
    ═══════════════════════════════════════════════════════════ --}}
 @if ($showVariantModal && $variantModalProduct)
     <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
         <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
             <div class="p-5 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
                 <div>
                     <h3 class="font-black text-gray-900">{{ $variantModalProduct->name }}</h3>
                     <p class="text-xs text-gray-400">Pilih varian yang akan dijual</p>
                 </div>
                 <button wire:click="$set('showVariantModal', false)" class="text-gray-400 hover:text-gray-600">
                     <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                         <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                     </svg>
                 </button>
             </div>
             <div class="p-5 space-y-2 max-h-80 overflow-y-auto">
                 @foreach ($variantModalVariants as $variant)
                     <button wire:click="addVariantToCart({{ $variant['id'] }})"
                         class="w-full p-4 rounded-xl border border-gray-100 hover:border-[#1c69d4]/50 hover:bg-blue-50/30 transition-all text-left flex justify-between items-center {{ $variant['stock'] <= 0 ? 'opacity-40 cursor-not-allowed' : '' }}"
                         {{ $variant['stock'] <= 0 ? 'disabled' : '' }}>
                         <div>
                             <p class="font-bold text-gray-800">{{ $variant['label'] }}</p>
                             @if ($variant['condition'])
                                 <p class="text-[10px] text-emerald-500 font-bold uppercase">
                                     {{ $variant['condition'] }}</p>
                             @endif
                             <p class="text-xs text-gray-400 font-mono mt-0.5">SKU: {{ $variant['sku'] ?: '-' }}
                             </p>
                         </div>
                         <div class="text-right">
                             <p class="font-bold text-[#1c69d4]">Rp
                                 {{ number_format($variant['price'], 0, ',', '.') }}</p>
                             <p
                                 class="text-[10px] text-gray-400 font-bold {{ $variant['stock'] <= 0 ? 'text-rose-500' : '' }}">
                                 Stok: {{ $variant['stock'] }}</p>
                         </div>
                     </button>
                 @endforeach
             </div>
         </div>
     </div>
 @endif

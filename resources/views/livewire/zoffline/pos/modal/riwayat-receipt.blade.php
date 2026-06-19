   {{-- ═══════════════════════════════════════════════════════════
         MODAL: Receipt (Struk) - Read Only for History
    ═══════════════════════════════════════════════════════════ --}}
   @if ($showReceiptModal && $completedOrder)
       <div class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 backdrop-blur-sm">
           <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden relative">
               <div class="p-4 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
                   <h3 class="font-black text-gray-900">Struk Transaksi</h3>
                   <div class="flex items-center gap-6">
                       {{-- Tombol Tutup --}}
                       <button wire:click="closeReceipt" class="text-gray-400 hover:text-gray-600">
                           <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                               stroke-width="2">
                               <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                           </svg>
                       </button>
                   </div>
               </div>

               {{-- Receipt Preview --}}
               <div id="receipt-content" class="p-5 font-mono text-xs leading-relaxed max-h-[60vh] overflow-y-auto">
                   <div class="text-center mb-3">
                       <p class="font-bold text-sm">SYIHAB STORE</p>
                       <p class="text-[10px] text-gray-500">
                           {{ $completedOrder->shipping_address_snapshot['store'] ?? 'Toko' }}</p>
                       <p class="text-[10px] text-gray-400">{{ $completedOrder->created_at->format('d/m/Y H:i') }}
                       </p>
                   </div>
                   <div class="border-t border-dashed border-gray-300 my-2"></div>
                   <p class="text-[10px] text-gray-500">Tanggal:
                       {{ $completedOrder->created_at->format('d/m/Y H:i') }}</p>
                   <p class="text-[10px] text-gray-500">No: {{ $completedOrder->order_number }}</p>
                   <p class="text-[10px] text-gray-500">Kasir: {{ $completedOrder->handledBy->name ?? '-' }}</p>
                   <p class="text-[10px] text-gray-500">Sales: {{ $completedOrder->salesBy->name ?? '-' }}
                   </p>
                   <p class="text-[10px] text-gray-500">Customer: {{ $completedOrder->user->name ?? '-' }}</p>
                   <p class="text-[10px] text-gray-500">Customer No:
                       {{ $completedOrder->user->profile->phone_number ?? '-' }}
                   </p>
                   <div class="border-t border-dashed border-gray-300 my-2"></div>

                   @foreach ($completedOrder->items as $item)
                       @php
                           $v = $item->variant;
                           if ($v instanceof \App\Models\ProductAccurate) {
                               $itemName = $v->name ?? '-';
                               $ram = '';
                               $storage = '';
                               $color = '';
                           } else {
                               $itemName = $v ? $v->product->name ?? ($v->secondProduct->name ?? '-') : '-';
                               $ram = $v ? $v->ram ?? '' : '';
                               $storage = $v ? $v->storage ?? '' : '';
                               $color = $v ? $v->color ?? '' : '';
                           }
                           // Bersihkan awalan nama
                           $itemName = preg_replace('/^(?:DS\s*-\s*HP\s*|DS\s*-\s*|HP\s*-\s*|HP\s*)/i', '', trim($itemName));
                       @endphp
                       <div class="mb-1">
                           <p class="font-bold">{{ $itemName }}
                               @if ($ram != null)
                                   {{ $ram }}/
                               @endif{{ $storage }}
                               {{ $color }}
                           </p>
                           <div class="flex justify-between">
                               <span>{{ $item->qty }}x
                                   {{ number_format($item->price_at_checkout, 0, ',', '.') }}</span>
                               <span>{{ number_format($item->subtotal, 0, ',', '.') }}</span>
                           </div>
                           @if ($item->serial_number)
                               <p class="text-[9px] text-gray-400">SN: {{ $item->serial_number }}</p>
                           @endif
                       </div>
                   @endforeach
                   <div class="border-t border-dashed border-gray-300 my-2"></div>
                   <div class="flex justify-between">
                       <span>Subtotal</span><span>{{ number_format($completedOrder->total_amount, 0, ',', '.') }}</span>
                   </div>
                   @if ($completedOrder->discount_amount > 0)
                       <div class="flex justify-between text-rose-600">
                           <span>Diskon</span><span>-{{ number_format($completedOrder->discount_amount, 0, ',', '.') }}</span>
                       </div>
                   @endif
                   <div class="border-t border-dashed border-gray-300 my-1"></div>
                   <div class="flex justify-between font-bold text-sm"><span>TOTAL</span><span>Rp
                           {{ number_format($completedOrder->grand_total, 0, ',', '.') }}</span></div>
                   <div class="border-t border-dashed border-gray-300 my-2"></div>
                   <div class="space-y-0.5 mb-2">
                       @foreach ($completedOrder->payments as $payment)
                           <div class="flex justify-between text-[10px] text-gray-500">
                               <span>Bayar
                                   ({{ $payment->paymentMethod->name ?? 'Cash' }}{{ $payment->paymentMethodRate ? ' - ' . $payment->paymentMethodRate->name : '' }})
                                   :</span>
                               <span>Rp {{ number_format($payment->amount, 0, ',', '.') }}</span>
                           </div>
                       @endforeach
                   </div>
                   @if ($completedOrder->accurate_invoice_no)
                       <p class="text-[10px] text-gray-400">Inv: {{ $completedOrder->accurate_invoice_no }}</p>
                   @endif
                   <div class="text-start mt-2">
                       <p class="text-[10px] text-gray-400">Catatan : {{ $completedOrder->notes ?? '' }}</p>
                   </div>
                   <div class="text-center mt-4">
                       <p class="text-[10px] text-gray-400">Terima kasih telah berbelanja!</p>
                       <p class="text-[10px] text-gray-300">Call Center : 0811-5600-6464</p>
                   </div>
               </div>

               <div class="p-4 border-t border-gray-100">
                   <button wire:click="closeReceipt"
                       class="w-full py-3 rounded-xl font-bold text-gray-700 bg-gray-100 hover:bg-gray-200 transition shadow-sm">
                       Tutup
                   </button>
               </div>
           </div>
       </div>
   @endif

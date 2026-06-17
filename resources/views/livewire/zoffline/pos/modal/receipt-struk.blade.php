   {{-- ═══════════════════════════════════════════════════════════
         MODAL: Receipt (Struk)
    ═══════════════════════════════════════════════════════════ --}}
   @if ($showReceiptModal && $completedOrder)
       <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
           <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
               <div class="p-4 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
                   <h3 class="font-black text-gray-900">Struk Transaksi</h3>
                   <div class="flex items-center gap-6">
                       {{-- <button
                           onclick="document.getElementById('receipt-content').classList.remove('hidden'); window.print();"
                           class="group relative text-[#1c69d4] hover:text-blue-700 font-bold text-sm flex items-center gap-1">

                           <svg class="w-5 h-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                               stroke-width="2">
                               <path stroke-linecap="round" stroke-linejoin="round"
                                   d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                           </svg>


                           <span
                               class="absolute right-full top-1/2 -translate-y-1/2 mr-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200 bg-gray-800 text-white text-[10px] font-normal py-1 px-2 rounded whitespace-nowrap pointer-events-none">
                               Cetak
                           </span>
                       </button> --}}
                       {{-- <button onclick="cetakStruk()"
                           class="text-orange-600 hover:text-orange-700 font-bold text-sm flex items-center gap-1">
                           <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                               <path stroke-linecap="round" stroke-linejoin="round"
                                   d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                           </svg>
                       </button> --}}
                       {{-- <button wire:click="printEscpos" wire:loading.attr="disabled"
                           class="group relative text-teal-600 hover:text-teal-700 font-bold text-sm flex items-center gap-1 disabled:opacity-50 disabled:cursor-not-allowed">

                           <svg wire:loading.remove wire:target="printEscpos" class="w-5 h-auto" fill="none"
                               viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                               <path stroke-linecap="round" stroke-linejoin="round"
                                   d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                           </svg>

                           <svg wire:loading wire:target="printEscpos" class="animate-spin w-5 h-auto text-teal-600"
                               xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                               <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                   stroke-width="4"></circle>
                               <path class="opacity-75" fill="currentColor"
                                   d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                               </path>
                           </svg>

                           <span
                               class="absolute right-full top-1/2 -translate-y-1/2 mr-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200 bg-gray-800 text-white text-[10px] font-normal py-1 px-2 rounded whitespace-nowrap pointer-events-none">
                               Print
                           </span>
                       </button> --}}
                       <button wire:click="getEscposBase64" wire:loading.attr="disabled"
                           class="group relative text-blue-500 hover:text-blue-700 font-bold text-sm flex items-center gap-1 disabled:opacity-50 disabled:cursor-not-allowed">

                           <svg wire:loading.remove wire:target="getEscposBase64" class="w-7 h-auto" fill="none"
                               viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                               <path stroke-linecap="round" stroke-linejoin="round"
                                   d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                           </svg>

                           <svg wire:loading wire:target="getEscposBase64" class="animate-spin w-5 h-auto text-teal-600"
                               xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                               <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                   stroke-width="4"></circle>
                               <path class="opacity-75" fill="currentColor"
                                   d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                               </path>
                           </svg>


                           <span
                               class="absolute right-full top-1/2 -translate-y-1/2 mr-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200 bg-gray-800 text-white text-[10px] font-normal py-1 px-2 rounded whitespace-nowrap pointer-events-none">
                               Print
                           </span>
                       </button>
                       {{-- ─── TOMBOL WHATSAPP MEKARI QONTAK ─── --}}
                       @if (Auth::user()->hasRole('admin') || !$completedOrder->is_wa_sent)
                           {{-- Aktif jika Admin ATAU jika WA belum pernah dikirim --}}
                           <button wire:click="sendReceiptToQontak" wire:loading.attr="disabled"
                               class="group relative text-emerald-600 hover:text-emerald-700 font-bold text-xs flex items-center gap-1 transition disabled:opacity-50 disabled:cursor-not-allowed">

                               {{-- Icon WhatsApp (Akan hilang saat loading) --}}
                               <svg wire:loading.remove wire:target="sendReceiptToQontak" class="w-7 h-auto"
                                   fill="currentColor" viewBox="0 0 24 24">
                                   <path
                                       d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397 0 11.983 0c3.192.001 6.192 1.242 8.447 3.498c2.256 2.255 3.497 5.255 3.497 8.447c-.004 6.585-5.342 11.93-11.93 11.93c-2.002-.001-3.973-.503-5.729-1.457L0 24zm6.59-4.846c1.6.95 3.188 1.449 4.825 1.451c5.436 0 9.86-4.42 9.864-9.858c.002-2.634-1.023-5.11-2.887-6.974c-1.864-1.864-4.341-2.887-6.973-2.889c-5.44 0-9.865 4.42-9.869 9.859c-.001 1.706.469 3.372 1.36 4.866l-.993 3.626l3.71-.973zm11.233-6.17c-.3-.149-1.774-.875-2.046-.974c-.272-.1-.471-.149-.669.149c-.198.299-.768.974-.941 1.173c-.173.199-.347.224-.647.075c-.3-.15-1.266-.466-2.41-1.487c-.89-.794-1.49-1.774-1.664-2.073c-.173-.3-.018-.462.13-.61c.134-.133.298-.348.446-.521c.15-.173.199-.298.298-.497c.099-.198.05-.372-.025-.521c-.075-.149-.669-1.612-.916-2.207c-.242-.579-.487-.501-.669-.51l-.57-.01c-.199 0-.52.074-.792.372c-.272.297-1.04 1.016-1.04 2.479c0 1.462 1.065 2.875 1.213 3.074c.149.198 2.095 3.2 5.076 4.487c.709.306 1.263.489 1.694.626c.712.226 1.36.194 1.872.118c.571-.085 1.774-.726 2.022-1.392c.247-.667.247-1.241.173-1.392c-.074-.15-.272-.249-.571-.398z" />
                               </svg>

                               {{-- Icon Spinner (Akan muncul dan berputar saat loading request WhatsApp) --}}
                               <svg wire:loading wire:target="sendReceiptToQontak"
                                   class="animate-spin w-5 h-auto text-emerald-600" xmlns="http://www.w3.org/2000/svg"
                                   fill="none" viewBox="0 0 24 24">
                                   <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                       stroke-width="4"></circle>
                                   <path class="opacity-75" fill="currentColor"
                                       d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                   </path>
                               </svg>

                               {{-- Tooltip Text (Muncul di sebelah kiri) --}}
                               <span
                                   class="absolute right-full top-1/2 -translate-y-1/2 mr-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200 bg-gray-800 text-white text-[10px] font-normal py-1 px-2 rounded whitespace-nowrap pointer-events-none">
                                   Whatsapp
                               </span>
                           </button>
                       @else
                           {{-- Terkunci untuk Kasir/FL jika is_wa_sent bernilai true --}}
                           <button disabled
                               class="text-gray-300 cursor-not-allowed font-bold text-xs flex items-center gap-1"
                               title="Sudah dikirim oleh kasir">
                               <svg class="w-4 h-4 opacity-40" fill="currentColor" viewBox="0 0 24 24">
                                   <path
                                       d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397 0 11.983 0c3.192.001 6.192 1.242 8.447 3.498c2.256 2.255 3.497 5.255 3.497 8.447c-.004 6.585-5.342 11.93-11.93 11.93c-2.002-.001-3.973-.503-5.729-1.457L0 24zm6.59-4.846c1.6.95 3.188 1.449 4.825 1.451c5.436 0 9.86-4.42 9.864-9.858c.002-2.634-1.023-5.11-2.887-6.974c-1.864-1.864-4.341-2.887-6.973-2.889c-5.44 0-9.865 4.42-9.869 9.859c-.001 1.706.469 3.372 1.36 4.866l-.993 3.626l3.71-.973zm11.233-6.17c-.3-.149-1.774-.875-2.046-.974c-.272-.1-.471-.149-.669.149c-.198.299-.768.974-.941 1.173c-.173.199-.347.224-.647.075c-.3-.15-1.266-.466-2.41-1.487c-.89-.794-1.49-1.774-1.664-2.073c-.173-.3-.018-.462.13-.61c.134-.133.298-.348.446-.521c.15-.173.199-.298.298-.497c.099-.198.05-.372-.025-.521c-.075-.149-.669-1.612-.916-2.207c-.242-.579-.487-.501-.669-.51l-.57-.01c-.199 0-.52.074-.792.372c-.272.297-1.04 1.016-1.04 2.479c0 1.462 1.065 2.875 1.213 3.074c.149.198 2.095 3.2 5.076 4.487c.709.306 1.263.489 1.694.626c.712.226 1.36.194 1.872.118c.571-.085 1.774-.726 2.022-1.392c.247-.667.247-1.241.173-1.392c-.074-.15-.272-.249-.571-.398z" />
                               </svg>
                               WA (Sent)
                           </button>
                       @endif

                       {{-- ─── TOMBOL EMAIL POS_SALES ─── --}}
                       @if (Auth::user()->hasRole('admin') || !$completedOrder->is_email_sent)
                           {{-- Aktif jika Admin ATAU jika Email belum pernah dikirim --}}
                           <button wire:click="sendReceiptToEmail" wire:loading.attr="disabled"
                               class="group relative text-blue-600 hover:text-blue-700 font-bold text-xs flex items-center gap-1 transition disabled:opacity-50 disabled:cursor-not-allowed">

                               {{-- Icon Email (Akan hilang saat loading) --}}
                               <svg wire:loading.remove wire:target="sendReceiptToEmail" class="w-7 h-auto"
                                   fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                   <path stroke-linecap="round" stroke-linejoin="round"
                                       d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                               </svg>

                               {{-- Icon Spinner (Akan muncul dan berputar saat loading request Email) --}}
                               <svg wire:loading wire:target="sendReceiptToEmail"
                                   class="animate-spin w-5 h-auto text-blue-600" xmlns="http://www.w3.org/2000/svg"
                                   fill="none" viewBox="0 0 24 24">
                                   <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                       stroke-width="4"></circle>
                                   <path class="opacity-75" fill="currentColor"
                                       d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                   </path>
                               </svg>

                               {{-- Tooltip Text (Muncul di sebelah kiri) --}}
                               <span
                                   class="absolute right-full top-1/2 -translate-y-1/2 mr-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200 bg-gray-800 text-white text-[10px] font-normal py-1 px-2 rounded whitespace-nowrap pointer-events-none">
                                   Email
                               </span>
                           </button>
                       @else
                           {{-- Terkunci untuk Kasir/FL jika is_email_sent bernilai true --}}
                           <button disabled
                               class="text-gray-300 cursor-not-allowed font-bold text-xs flex items-center gap-1"
                               title="Sudah dikirim oleh kasir">
                               <svg class="w-4 h-4 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                   stroke-width="2">
                                   <path stroke-linecap="round" stroke-linejoin="round"
                                       d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                               </svg>
                               Email (Sent)
                           </button>
                       @endif

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
               <div id="receipt-content" class="p-5 font-mono text-xs leading-relaxed overflow-y-auto h-125">
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
                   <button wire:click="newTransaction"
                       class="w-full py-3 rounded-xl font-bold text-white bg-emerald-500 hover:bg-emerald-600 transition shadow-md">
                       Transaksi Baru
                   </button>
               </div>
           </div>
       </div>

   @endif

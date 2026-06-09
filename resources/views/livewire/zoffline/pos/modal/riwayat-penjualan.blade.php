  {{-- ═══════════════════════════════════════════════════════════
         MODAL: History Sales (Riwayat Penjualan)
    ═══════════════════════════════════════════════════════════ --}}
  @if ($showHistoryModal)
      <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
          <div class="bg-white rounded-2xl shadow-xl w-full max-w-5xl overflow-hidden">
              {{-- Header --}}
              <div
                  class="p-5 bg-gray-50 border-b border-gray-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                  <div class="flex-1">
                      <h3 class="font-black text-gray-900 text-lg">Riwayat Transaksi POS :
                          {{ Auth::user()->warehouse->name ?? 'Unknown' }}</h3>
                      <p class="text-xs text-gray-400">Daftar penjualan yang berhasil diproses lewat kasir</p>
                  </div>

                  {{-- Filters --}}
                  <div class="flex items-center gap-3 w-full md:w-auto">
                      <input type="date" wire:model.live.debounce.300ms="searchHistoryDate"
                          class="text-sm p-2 border-gray-200 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-gray-700">
                      <div class="relative flex-1 md:w-64">
                          <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                              <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24"
                                  stroke="currentColor">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                              </svg>
                          </div>
                          <input type="text" wire:model.live.debounce.300ms="searchHistory"
                              class="w-full pl-10 p-2 text-sm border-gray-200 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-gray-700"
                              placeholder="Cari No. Order / Pelanggan...">
                      </div>
                      <button wire:click="$set('showHistoryModal', false)"
                          class="text-gray-400 hover:text-gray-600 ml-2">
                          <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                              stroke-width="2">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                          </svg>
                      </button>
                  </div>
              </div>
              {{-- Table/Content --}}
              <div class="p-5 max-h-[450px] overflow-y-auto">
                  @if (count($historyOrders) > 0)
                      <div class="overflow-x-auto">
                          <table class="w-full text-left text-xs border-collapse">
                              <thead>
                                  <tr
                                      class="border-b border-gray-200 text-gray-400 uppercase font-black tracking-wider bg-gray-50/50">
                                      <th class="p-3">Waktu / No. Order</th>
                                      <th class="p-3">Kasir</th>
                                      <th class="p-3">Customer</th>
                                      <th class="p-3">Metode</th>
                                      <th class="p-3 text-right">Total Akhir</th>
                                      <th class="p-3 text-center">Status</th>
                                      <th class="p-3 text-center">Aksi</th>
                                  </tr>
                              </thead>
                              <tbody class="divide-y divide-gray-100 font-medium text-gray-700">
                                  @foreach ($historyOrders as $order)
                                      <tr class="hover:bg-gray-50/80 transition-colors">
                                          <td class="p-3">
                                              <p class="font-bold text-gray-900">{{ $order->order_number }}</p>
                                              <p class="text-[10px] text-gray-400 font-mono">
                                                  {{ $order->created_at->format('d M Y H:i') }}
                                              </p>
                                          </td>
                                          <td class="p-3 text-gray-600">
                                              {{ $order->handledBy->name ?? 'Umum/Cash' }}
                                          </td>
                                          <td class="p-3 text-gray-600">
                                              {{ $order->user->name ?? 'Umum/Cash' }}
                                          </td>
                                          <td class="p-3">
                                              <span
                                                  class="px-2 py-0.5 bg-blue-50 text-blue-700 text-[10px] font-bold rounded-md uppercase">
                                                  {{ $order->paymentMethod->name ?? 'Cash' }}
                                              </span>
                                          </td>
                                          <td class="p-3 text-right font-bold text-gray-900">
                                              Rp {{ number_format($order->grand_total, 0, ',', '.') }}
                                          </td>

                                          <td class="p-3 text-center whitespace-nowrap">
                                              @if ($order->order_status === 'DELETED')
                                                  <span
                                                      class="px-2 py-0.5 bg-red-50 text-red-700 text-[9px] font-bold rounded border border-red-200 uppercase">
                                                      🗑️ Dosa
                                                  </span>
                                              @elseif (!empty($order->accurate_invoice_no) || !empty($order->accurate_receipt_no))
                                                  <div class="inline-flex flex-col gap-1 items-center">
                                                      <span
                                                          class="px-2 py-0.5 bg-emerald-50 text-emerald-700 text-[9px] font-bold rounded border border-emerald-200 uppercase">
                                                          ✓ Tersinkron
                                                      </span>
                                                      @if ($order->accurate_invoice_no)
                                                          <span class="text-[9px] text-gray-400 font-mono"
                                                              title="Invoice No">Inv:
                                                              {{ $order->accurate_invoice_no }}</span>
                                                      @endif
                                                  </div>
                                              @else
                                                  <span
                                                      class="px-2 py-0.5 bg-amber-50 text-amber-700 text-[9px] font-bold rounded border border-amber-200 uppercase">
                                                      ⏳ Pending
                                                  </span>
                                              @endif
                                          </td>

                                          <td class="p-3 text-center">
                                              @if ($order->order_status != 'DELETED')
                                                  <button wire:click="reprintOrder({{ $order->id }})"
                                                      class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-50 text-emerald-600 hover:bg-emerald-100 rounded-md text-[11px] font-bold transition-all">
                                                      <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"
                                                          stroke="currentColor" stroke-width="2">
                                                          <path stroke-linecap="round" stroke-linejoin="round"
                                                              d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                                      </svg>
                                                      Struk
                                                  </button>
                                              @endif
                                          </td>
                                      </tr>
                                  @endforeach
                              </tbody>
                          </table>
                      </div>
                  @else
                      <div class="flex flex-col items-center justify-center py-12 text-gray-300">
                          <svg class="w-12 h-12 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                              stroke-width="1">
                              <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                          </svg>
                          <p class="text-sm font-bold text-gray-400">Belum ada riwayat transaksi hari ini</p>
                      </div>
                  @endif
              </div>

              {{-- Footer --}}
              <div class="p-4 bg-gray-50 border-t border-gray-100 flex justify-end">
                  <button wire:click="$set('showHistoryModal', false)"
                      class="px-4 py-2 bg-gray-200 text-gray-700 hover:bg-gray-300 rounded-xl text-xs font-bold transition">
                      Tutup
                  </button>
              </div>
          </div>
      </div>
  @endif

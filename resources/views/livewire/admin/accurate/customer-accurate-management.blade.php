<div>
    {{-- Header Section --}}
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Sinkronisasi Pelanggan Accurate</h1>
            <p class="text-sm text-gray-500 mt-1">Tarik data pelanggan dari Accurate Online untuk migrasi awal.</p>
        </div>
        <div class="flex items-center gap-3">
            @if ($syncStatus !== 'running')
                <div class="flex items-center gap-3 mr-2">
                    <div class="flex items-center gap-2">
                        <label for="dbSource" class="text-sm font-medium text-gray-700">Database:</label>
                        <select id="dbSource" wire:model="databaseSource" 
                            class="w-28 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] bg-white cursor-pointer appearance-none">
                            <option value="syihab">Syihab</option>
                            <option value="second">GSK</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2">
                        <label for="syncPage" class="text-sm font-medium text-gray-700">Mulai Hal:</label>
                        <input type="number" id="syncPage" wire:model="syncCurrentPage" min="1"
                            class="w-20 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4]">
                    </div>
                </div>
            @endif

            <button wire:click="startSync" wire:loading.attr="disabled"
                class="flex items-center justify-center gap-2 bg-[#1c69d4] hover:bg-[#1556b0] text-white px-5 py-2.5 rounded-lg text-sm font-semibold transition-all disabled:opacity-50 disabled:cursor-not-allowed shadow-sm">
                <svg wire:loading wire:target="startSync" class="animate-spin h-4 w-4 text-white" viewBox="0 0 24 24"
                    fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>

                <span wire:loading.remove wire:target="startSync">
                    Mulai Tarik Pelanggan
                </span>
                <span wire:loading wire:target="startSync">
                    Memproses...
                </span>
            </button>
            @if ($syncStatus === 'running' || $syncStatus === 'completed')
                <div
                    class="mt-4 p-4 bg-indigo-50 border border-indigo-100 rounded-xl flex items-center justify-between {{ $syncStatus === 'running' ? 'animate-pulse' : '' }}">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center">
                            @if ($syncStatus === 'running')
                                <svg class="w-5 h-5 text-indigo-600 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            @else
                                <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                            @endif
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-indigo-900">{{ $syncMessage }}</h4>
                            <p class="text-xs text-indigo-700 mt-0.5">Dilewati (Tidak Lengkap/Error):
                                {{ $syncSkippedCount }}</p>
                        </div>
                    </div>
                    <div class="text-right ml-4">
                        <span class="text-2xl font-black text-indigo-600">{{ $syncImportedCount }}</span>
                        <span
                            class="text-xs text-indigo-500 font-medium block uppercase tracking-wider">Tersimpan</span>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6 overflow-hidden">
        <div class="p-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
            <h3 class="font-bold text-gray-700">Daftar Pelanggan Tersinkronisasi</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="py-3 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">No Pelanggan
                            Accurate</th>
                        <th class="py-3 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Nama
                            Pelanggan</th>
                        <th class="py-3 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Nomor HP</th>
                        <th class="py-3 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Email (ZPOS
                            Login)</th>
                        <th class="py-3 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">
                            Tgl Dibuat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse ($customers as $customer)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="py-4 px-6 font-medium text-gray-900">
                                @if($customer->accurateCustomers->count() > 0)
                                    @foreach($customer->accurateCustomers as $ac)
                                        <div class="mb-1 text-xs">
                                            <span class="font-bold text-[#1c69d4]">{{ $ac->accurate_customer_no }}</span>
                                            <span class="bg-gray-100 text-gray-500 rounded px-1.5 py-0.5 ml-1">{{ $ac->businessUnit->code ?? '' }}</span>
                                        </div>
                                    @endforeach
                                @else
                                    -
                                @endif
                            </td>
                            <td class="py-4 px-6 font-medium text-gray-800">
                                {{ $customer->name }}
                            </td>
                            <td class="py-4 px-6 text-gray-600">
                                {{ $customer->profile->phone_number ?? '-' }}
                            </td>
                            <td class="py-4 px-6 text-gray-600">
                                {{ $customer->email }}
                            </td>
                            <td class="py-4 px-6 text-right text-xs text-gray-500">
                                {{ $customer->created_at ? $customer->created_at->format('d/m/Y H:i') : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center">
                                <p class="text-gray-500 font-medium">Belum ada pelanggan hasil integrasi Accurate</p>
                                <p class="text-sm text-gray-400 mt-1">Klik tombol Mulai Tarik Pelanggan di atas.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($customers->hasPages())
            <div class="border-t border-gray-100 p-4">
                {{ $customers->links() }}
            </div>
        @endif
    </div>
</div>

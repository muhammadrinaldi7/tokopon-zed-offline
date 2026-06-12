<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Sinkronisasi Serial Number Accurate</h1>
        <p class="text-gray-500 text-sm mt-1">Tarik dan sinkronkan Serial Number/IMEI dari Accurate ke sistem lokal</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-6 border-b border-gray-200 bg-gray-50/50 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h3 class="text-lg font-bold text-gray-800">Status Sinkronisasi</h3>
                <p class="text-sm text-gray-500 mt-1">
                    @if($isSyncing)
                        Sedang menyinkronkan data... Jangan tutup halaman ini.
                    @elseif($isSyncingVendor)
                        Sedang menyinkronkan data Vendor & HPP... Jangan tutup halaman ini.
                    @elseif($isSyncingHpp)
                        Sedang menyinkronkan nilai HPP (Nearest Cost)... Jangan tutup halaman ini.
                    @else
                        Siap untuk menyinkronkan. Klik tombol di samping untuk memulai.
                    @endif
                </p>
            </div>
            <div class="flex items-center gap-2 flex-wrap md:flex-nowrap">
                <select wire:model.live="businessUnitId"
                    class="border-gray-200 text-sm font-medium focus:ring-[#1c69d4] focus:border-[#1c69d4] text-gray-700 bg-white py-2 pl-3 pr-8 rounded-lg cursor-pointer">
                    <option value="">Semua Unit Usaha</option>
                    @foreach($businessUnits as $bu)
                        <option value="{{ $bu->id }}">{{ $bu->name }}</option>
                    @endforeach
                </select>
                <div class="h-6 w-px bg-gray-300 hidden md:block"></div>

                <button 
                    wire:click="startSync" 
                    @if($isSyncing || $isSyncingVendor || $isSyncingHpp) disabled @endif
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-medium rounded-lg transition-colors">
                    
                    @if($isSyncing)
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Sinkronisasi Berjalan...
                    @else
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Mulai Sinkronisasi
                    @endif
                </button>

                <button 
                    wire:click="startSyncVendor" 
                    @if($isSyncing || $isSyncingVendor || $isSyncingHpp) disabled @endif
                    class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-medium rounded-lg transition-colors">
                    
                    @if($isSyncingVendor)
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Sinkron Vendor Berjalan...
                    @else
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        Sinkron Vendor & HPP
                    @endif
                </button>

                <button 
                    wire:click="startSyncHpp" 
                    @if($isSyncing || $isSyncingVendor || $isSyncingHpp) disabled @endif
                    class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-medium rounded-lg transition-colors">
                    
                    @if($isSyncingHpp)
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Get HPP Berjalan...
                    @else
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Get HPP
                    @endif
                </button>
            </div>
        </div>

        <div class="p-6">
            @if($totalItems > 0)
                <div class="mb-6">
                    <div class="flex justify-between items-end mb-2">
                        <div>
                            <span class="text-sm font-medium text-gray-700">Progres Keseluruhan</span>
                        </div>
                        <div class="text-right">
                            <span class="text-sm font-bold text-blue-600">{{ $processedItems }} / {{ $totalItems }}</span>
                            <span class="text-xs text-gray-500">Item Tersinkronisasi</span>
                        </div>
                    </div>
                    
                    @php
                        $percentage = $totalItems > 0 ? round(($processedItems / $totalItems) * 100) : 0;
                    @endphp
                    
                    <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                        <div class="bg-blue-600 h-2.5 rounded-full transition-all duration-300 ease-in-out" style="width: {{ $percentage }}%"></div>
                    </div>
                    
                    @if($isSyncing || $isSyncingVendor || $isSyncingHpp)
                        <p class="text-xs text-gray-500 mt-2 font-mono flex items-center gap-2">
                            <span class="relative flex h-2 w-2">
                              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                              <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
                            </span>
                            {{ $currentItem }}
                        </p>
                    @endif
                </div>
            @endif

            <div class="mt-4">
                <h4 class="text-sm font-bold text-gray-700 mb-2">Log Sinkronisasi</h4>
                <div class="bg-gray-900 rounded-lg p-4 h-64 overflow-y-auto font-mono text-xs shadow-inner">
                    @forelse($logs as $log)
                        <div class="mb-1 text-green-400 border-b border-gray-800 pb-1">
                            {{ $log }}
                        </div>
                    @empty
                        <div class="text-gray-500 text-center italic mt-24">Belum ada log sinkronisasi</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

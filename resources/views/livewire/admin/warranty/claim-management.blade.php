<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
        <div>
            <h2 class="text-2xl font-black text-gray-800">Manajemen Klaim Garansi</h2>
            <p class="text-gray-500 text-sm mt-1">Kelola dan proses pengajuan klaim garansi dari pelanggan.</p>
        </div>
    </div>

    <!-- Filters & List -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="relative w-full md:w-96">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari No Klaim, SN, Pelanggan..."
                    class="w-full bg-gray-50 border-gray-200 rounded-xl pl-10 pr-4 py-2.5 text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] transition-colors">
            </div>
            
            <select wire:model.live="statusFilter" class="bg-gray-50 border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] text-gray-700">
                <option value="">Semua Status</option>
                <option value="pending">Menunggu Persetujuan (Pending)</option>
                <option value="approved">Disetujui (Approved)</option>
                <option value="in_repair">Dalam Perbaikan (In Repair)</option>
                <option value="completed">Selesai (Completed)</option>
                <option value="rejected">Ditolak (Rejected)</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="py-4 px-6 text-xs font-bold text-gray-400 uppercase tracking-wider">No Klaim / Tgl</th>
                        <th class="py-4 px-6 text-xs font-bold text-gray-400 uppercase tracking-wider">Garansi & SN</th>
                        <th class="py-4 px-6 text-xs font-bold text-gray-400 uppercase tracking-wider">Pelanggan</th>
                        <th class="py-4 px-6 text-xs font-bold text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="py-4 px-6 text-xs font-bold text-gray-400 uppercase tracking-wider text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($claims as $claim)
                        <tr wire:key="claim-{{ $claim->id }}" class="hover:bg-blue-50/30 transition-colors duration-200">
                            <td class="py-4 px-6">
                                <div class="font-bold text-blue-600">{{ $claim->claim_number }}</div>
                                <div class="text-xs text-gray-500 mt-1">{{ $claim->claimed_at->format('d M Y H:i') }}</div>
                            </td>
                            <td class="py-4 px-6">
                                <div class="font-bold text-gray-800">{{ $claim->warranty->policy->name ?? 'Unknown' }}</div>
                                <div class="text-xs font-mono text-gray-500 mt-1">SN: {{ $claim->serial_number }}</div>
                            </td>
                            <td class="py-4 px-6">
                                <div class="font-bold text-gray-800">{{ $claim->customer->name ?? 'Unknown' }}</div>
                                <div class="text-xs text-gray-500 mt-1">{{ $claim->customer->phone_number ?? '-' }}</div>
                            </td>
                            <td class="py-4 px-6">
                                @if($claim->status === 'pending')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-amber-100 text-amber-700 rounded-full text-xs font-bold">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Pending
                                    </span>
                                @elseif($claim->status === 'approved')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-bold">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span> Disetujui
                                    </span>
                                @elseif($claim->status === 'in_repair')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-xs font-bold">
                                        <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span> Diproses
                                    </span>
                                @elseif($claim->status === 'completed')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs font-bold">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Selesai
                                    </span>
                                @elseif($claim->status === 'rejected')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-rose-100 text-rose-700 rounded-full text-xs font-bold">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Ditolak
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-right">
                                <button wire:click="openProcessModal({{ $claim->id }})"
                                    class="px-4 py-2 bg-white border border-gray-200 text-gray-700 rounded-lg hover:bg-gray-50 text-xs font-bold transition-colors">
                                    Lihat & Proses
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-12">
                                <div class="flex flex-col items-center justify-center text-gray-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mb-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <p class="text-lg font-medium text-gray-500">Belum ada klaim garansi</p>
                                </div>
                            </td>
                        </tr>
                    @endempty
                </tbody>
            </table>
        </div>
        @if ($claims->hasPages())
            <div class="p-4 border-t border-gray-100 bg-gray-50">
                {{ $claims->links() }}
            </div>
        @endif
    </div>

    <!-- Process Modal -->
    @if ($showModal)
        <div class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" wire:click="closeModal"></div>

            <div class="relative bg-gray-50 rounded-2xl w-full max-w-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
                <div class="bg-white px-6 py-4 border-b border-gray-100 flex items-center justify-between shrink-0">
                    <h3 class="font-bold text-xl text-gray-800">Proses Klaim Garansi</h3>
                    <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-full p-2 transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                @php
                    $selectedClaim = $claims->firstWhere('id', $selectedClaimId);
                @endphp

                @if($selectedClaim)
                <div class="p-6 overflow-y-auto flex-1 space-y-6">
                    <!-- Claim Detail -->
                    <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm">
                        <h4 class="text-xs font-black text-gray-400 uppercase tracking-wider mb-3 border-b border-gray-100 pb-2">Informasi Klaim</h4>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <p class="text-gray-500 mb-1">No Klaim</p>
                                <p class="font-bold text-blue-600">{{ $selectedClaim->claim_number }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500 mb-1">Status Saat Ini</p>
                                <p class="font-bold text-gray-900 uppercase">{{ $selectedClaim->status }}</p>
                            </div>
                            <div class="col-span-2">
                                <p class="text-gray-500 mb-1">Keluhan Pelanggan</p>
                                <div class="bg-red-50 text-red-800 p-3 rounded-lg border border-red-100 text-sm">
                                    {{ $selectedClaim->issue_description }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Process Form -->
                    <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm">
                        <h4 class="text-xs font-black text-gray-400 uppercase tracking-wider mb-3 border-b border-gray-100 pb-2">Tindakan Admin</h4>
                        <div class="form-control mb-4">
                            <label class="block text-sm font-bold text-gray-700 mb-1.5">Catatan Resolusi (Opsional)</label>
                            <textarea wire:model="resolution_notes" rows="3" class="w-full rounded-lg border-gray-200 px-4 py-2.5 focus:ring-[#1c69d4] focus:border-[#1c69d4] text-sm text-gray-800 bg-gray-50 shadow-sm" placeholder="Tulis catatan persetujuan, penolakan, atau hasil perbaikan teknisi..."></textarea>
                            @error('resolution_notes') <span class="text-rose-500 text-xs mt-1 font-medium">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex flex-wrap gap-2 mt-4">
                            @if($selectedClaim->status === 'pending')
                                <button wire:click="updateStatus('approved')" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg text-sm transition-colors shadow-sm">
                                    Setujui Klaim (Approve)
                                </button>
                                <button wire:click="updateStatus('rejected')" class="px-4 py-2 bg-rose-100 hover:bg-rose-200 text-rose-700 font-bold rounded-lg text-sm transition-colors">
                                    Tolak Klaim (Reject)
                                </button>
                            @endif

                            @if($selectedClaim->status === 'approved')
                                <button wire:click="updateStatus('in_repair')" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-lg text-sm transition-colors shadow-sm">
                                    Proses Perbaikan (In Repair)
                                </button>
                            @endif

                            @if(in_array($selectedClaim->status, ['approved', 'in_repair']))
                                <button wire:click="updateStatus('completed')" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg text-sm transition-colors shadow-sm">
                                    Selesai (Completed)
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    @endif
</div>

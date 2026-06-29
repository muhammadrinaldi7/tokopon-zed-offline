<div class="p-8">
    <div
        class="bg-linear-to-r from-[#4E44DB] via-[#6355F6] to-[#766bf2] rounded-4xl p-8 text-white mb-8 shadow-xl shadow-[#4E44DB]/15 relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 w-44 h-44 bg-white/5 rounded-full blur-2xl"></div>
        <div class="absolute -left-10 -top-10 w-44 h-44 bg-white/5 rounded-full blur-2xl"></div>
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <h1 class="text-3xl font-black tracking-tight mb-2">Aturan Persetujuan Transaksi</h1>
                <p class="text-indigo-100 text-sm font-medium">Tentukan level berjenjang dan Role (Peran) yang wajib
                    memberikan persetujuan (Approval) untuk setiap modul.</p>
            </div>
            <div
                class="bg-white/10 backdrop-blur-md border border-white/20 px-5 py-3 rounded-2xl text-sm font-bold flex items-center gap-3 h-fit">
                Modul:
                <select wire:model.live="module"
                    class="bg-transparent border-none text-white focus:ring-0 cursor-pointer font-bold outline-none">
                    @foreach ($availableModules as $key => $label)
                        <option value="{{ $key }}" class="text-gray-800">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden mb-8 p-8">
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
            <div>
                <h3 class="font-extrabold text-gray-800 text-lg">Alur Persetujuan:
                    {{ $availableModules[$module] ?? ucfirst($module) }}</h3>
                <p class="text-xs text-gray-400 mt-0.5 font-medium">Pengajuan akan ditinjau secara berjenjang mulai dari
                    Level 1 hingga level tertinggi.</p>
            </div>
            <button wire:click="addLevel"
                class="bg-indigo-50 text-indigo-600 hover:bg-indigo-100 font-bold px-4 py-2 rounded-xl transition-colors text-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Tambah Level
            </button>
        </div>

        <div class="space-y-4 relative">
            @if (count($rules) > 1)
                <div class="absolute left-6 top-8 bottom-8 w-0.5 bg-indigo-100 rounded-full hidden md:block"></div>
            @endif

            @forelse($rules as $index => $rule)
                <div
                    class="flex flex-col md:flex-row md:items-center gap-4 bg-gray-50/50 p-4 rounded-2xl border border-gray-100 relative z-10 hover:border-indigo-200 transition-colors">
                    <div
                        class="w-12 h-12 rounded-full bg-white border-2 border-indigo-500 text-indigo-600 flex items-center justify-center font-black text-lg shadow-sm shrink-0">
                        {{ $rule['level'] }}
                    </div>
                    <div class="flex-1 w-full space-y-1">
                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Role Penyetuju
                            (Level {{ $rule['level'] }})</label>
                        <select wire:model="rules.{{ $index }}.role_id"
                            class="w-full bg-white border border-gray-200 rounded-xl px-4 py-2 text-sm font-semibold focus:border-[#4E44DB] focus:ring-0 transition-all cursor-pointer">
                            <option value="">-- Pilih Role --</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}">{{ strtoupper($role->name) }}</option>
                            @endforeach
                        </select>
                        @error('rules.' . $index . '.role_id')
                            <span class="text-xs text-red-500 font-bold">{{ $message }}</span>
                        @enderror
                    </div>
                    <button wire:click="removeLevel({{ $index }})"
                        class="shrink-0 p-3 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-xl transition-colors self-end md:self-center"
                        title="Hapus Level">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>
            @empty
                <div class="text-center py-12 bg-gray-50/50 rounded-2xl border border-dashed border-gray-200">
                    <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    <p class="text-sm font-bold text-gray-500">Belum ada jenjang persetujuan (Otomatis langsung batal).
                    </p>
                    <button wire:click="addLevel" class="mt-4 text-indigo-600 font-bold text-sm hover:underline">Klik di
                        sini untuk menambah level 1</button>
                </div>
            @endforelse
        </div>

        <div class="border-t border-gray-50 mt-8 pt-6 flex justify-end">
            <button wire:click="save"
                class="bg-[#4E44DB] hover:bg-blue-700 text-white font-bold px-8 py-3 rounded-2xl transition-all shadow-md shadow-blue-500/10 cursor-pointer flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                </svg>
                Simpan Aturan Persetujuan
            </button>
        </div>
    </div>
</div>

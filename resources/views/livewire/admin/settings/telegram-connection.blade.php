<div class="px-6 py-8 w-full max-w-7xl mx-auto" x-data="{ alert: null }"
    @admin-alert.window="
        alert = $event.detail;
        setTimeout(() => alert = null, 3000);
    ">

    <!-- Alpine Notification Setup -->
    <div x-show="alert" x-transition.opacity.duration.300ms style="display: none;"
        class="mb-6 px-4 py-3 rounded-xl border flex items-center gap-3 text-sm font-medium shadow-sm transition-all"
        :class="alert?.type === 'success' ? 'bg-emerald-50 border-emerald-100 text-emerald-800' :
            'bg-red-50 border-red-100 text-red-800'">
        <svg x-show="alert?.type === 'success'" class="w-5 h-5 text-emerald-500 shrink-0" fill="none"
            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span x-text="alert?.message"></span>
    </div>

    <!-- Header -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 mb-1">
                <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-500" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69.01-.03.01-.14-.07-.18-.08-.05-.19-.02-.27 0-.11.03-1.84 1.18-5.18 3.44-.49.34-.93.5-1.33.49-.44-.01-1.28-.24-1.9-.44-.77-.24-1.38-.37-1.33-.79.03-.22.34-.45.92-.69 3.6-1.57 6.01-2.6 7.24-3.12 3.44-1.43 4.15-1.68 4.62-1.68.1 0 .33.02.48.13.12.1.16.23.17.34.01.12.01.23 0 .32z"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">Integrasi Telegram Bot</h1>
            </div>
            <p class="text-sm text-gray-500 mt-1">Sambungkan akun staf dengan Bot Telegram untuk menerima notifikasi Approval.</p>
        </div>
        <div class="flex items-center gap-3">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama atau email..."
                class="w-full md:w-72 bg-white border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600">
                <thead class="bg-gray-50/50 text-gray-500 font-semibold border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-4">Pengguna</th>
                        <th class="px-6 py-4">Jabatan</th>
                        <th class="px-6 py-4">Status Koneksi</th>
                        <th class="px-6 py-4">Telegram Chat ID</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($users as $user)
                        <tr class="hover:bg-gray-50/50 transition-colors" wire:key="user-{{ $user->id }}">
                            <td class="px-6 py-4 font-medium text-gray-900 flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-linear-to-br from-blue-500 to-indigo-500 text-white flex items-center justify-center font-bold text-xs shrink-0">
                                    {{ strtoupper(substr($user->name, 0, 2)) }}
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">{{ $user->name }}</p>
                                    <p class="text-xs text-gray-500 font-normal">{{ $user->email }}</p>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1.5">
                                    @forelse($user->roles as $role)
                                        <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-semibold bg-gray-100 text-gray-700 capitalize">
                                            {{ $role->name }}
                                        </span>
                                    @empty
                                        <span class="text-gray-400 italic text-xs">User Biasa</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @if($user->telegram_chat_id)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Terhubung
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-gray-50 text-gray-600 border border-gray-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                        Belum Terhubung
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-mono text-sm text-gray-700">
                                {{ $user->telegram_chat_id ?: '-' }}
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                @if($user->telegram_chat_id)
                                    <button wire:click="openEditModal({{ $user->id }})" class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 text-xs font-bold text-blue-600 bg-blue-50 hover:bg-blue-600 hover:text-white rounded-lg transition-colors cursor-pointer border border-blue-100">
                                        Ubah ID
                                    </button>
                                    <button wire:click="disconnectTelegram({{ $user->id }})" wire:confirm="Apakah Anda yakin ingin memutuskan koneksi Telegram untuk {{ $user->name }}?" class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 text-xs font-bold text-rose-600 bg-rose-50 hover:bg-rose-600 hover:text-white rounded-lg transition-colors cursor-pointer border border-rose-100">
                                        Putuskan
                                    </button>
                                @else
                                    <button wire:click="openEditModal({{ $user->id }})" class="inline-flex items-center justify-center gap-1.5 px-4 py-1.5 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors shadow-sm shadow-blue-500/30 cursor-pointer">
                                        + Koneksikan
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center text-gray-400">
                                <p>Tidak ada pengguna yang ditemukan.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/30">
            {{ $users->links() }}
        </div>
    </div>

    <!-- Edit Telegram Modal -->
    @if ($isEditModalOpen && $editingUser)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm transition-opacity" wire:click="closeModal"></div>

            <!-- Modal Box -->
            <div class="relative bg-white rounded-3xl w-full max-w-md shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
                <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-900">Pengaturan Telegram</h3>
                    <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600 transition-colors p-1 bg-gray-50 rounded-full hover:bg-gray-100 border border-gray-200 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form wire:submit="saveTelegramId">
                    <div class="p-6">
                        <div class="mb-5 p-4 bg-gray-50 rounded-2xl flex items-center gap-4 border border-gray-100">
                            <div class="w-10 h-10 rounded-full bg-linear-to-br from-blue-500 to-indigo-500 text-white flex items-center justify-center font-bold text-sm shrink-0">
                                {{ strtoupper(substr($editingUser->name, 0, 2)) }}
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 text-sm">{{ $editingUser->name }}</p>
                                <p class="text-xs text-gray-500">{{ $editingUser->email }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Telegram Chat ID</label>
                            <input type="text" wire:model="telegramChatId" placeholder="Contoh: 123456789" class="w-full bg-white border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-mono">
                            @error('telegramChatId') <span class="text-xs text-rose-500 font-medium mt-1.5 block">{{ $message }}</span> @enderror
                            
                            <div class="mt-4 p-4 bg-blue-50 border border-blue-100 rounded-xl flex gap-3 text-blue-800">
                                <svg class="w-5 h-5 shrink-0 text-blue-500" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>
                                </svg>
                                <p class="text-xs font-medium leading-relaxed">Untuk mendapatkan ID, minta {{ $editingUser->name }} membuka aplikasi Telegram dan mengirim pesan apa saja ke bot <span class="font-bold">@userinfobot</span>.</p>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end gap-3 rounded-b-3xl">
                        <button type="button" wire:click="closeModal" class="px-5 py-2.5 text-sm font-bold text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 hover:text-gray-900 transition-colors shadow-sm cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2.5 text-sm font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700 shadow-md shadow-blue-500/20 transition-all flex items-center gap-2 cursor-pointer">
                            Simpan ID Telegram
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>

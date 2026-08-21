<div>
    <x-slot:title>
        Pengaturan AI Assistant
    </x-slot:title>

    <div class="max-w-5xl mx-auto space-y-8 pb-12">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-neutral-800 tracking-tight">Pengaturan AI</h1>
                <p class="text-neutral-500 mt-1">Konfigurasi koneksi webhook n8n dan instruksi sistem (Prompt).</p>
            </div>
            <a href="{{ route('admin.ai-assistant.index') }}" wire:navigate
                class="px-5 py-2.5 bg-white border border-neutral-300 hover:bg-neutral-50 text-neutral-700 rounded-xl text-sm font-medium transition-colors shadow-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Kembali ke Chat
            </a>
        </div>

        @if (session('success'))
            <div class="bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-r-xl shadow-sm">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-emerald-800">
                            {{ session('success') }}
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <div class="bg-white border border-neutral-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-neutral-200 bg-neutral-50/50">
                <h3 class="text-lg font-medium leading-6 text-neutral-900">Konfigurasi n8n Webhook</h3>
            </div>
            
            <div class="p-6">
                <form wire:submit="saveSettings" class="space-y-8">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="webhook_url" class="block text-sm font-semibold text-neutral-700 mb-2">Endpoint Webhook URL</label>
                            <input type="url" id="webhook_url" wire:model="webhook_url" 
                                placeholder="https://n8n.tokopon.com/webhook/..." 
                                class="w-full rounded-xl border-neutral-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-base py-3 px-4 transition-colors">
                            <p class="mt-2 text-xs text-neutral-500">URL ini akan dipanggil otomatis setiap kali ada pesan masuk.</p>
                        </div>

                        <div>
                            <label for="webhook_token" class="block text-sm font-semibold text-neutral-700 mb-2">Header Token (X-Zedpos-Agent-Token)</label>
                            <input type="password" id="webhook_token" wire:model="webhook_token" 
                                placeholder="Token otentikasi opsional" 
                                class="w-full rounded-xl border-neutral-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-base py-3 px-4 transition-colors">
                            <p class="mt-2 text-xs text-neutral-500">Gunakan ini jika node Webhook n8n Anda membutuhkan otentikasi Header.</p>
                        </div>
                    </div>

                    <hr class="border-neutral-200">

                    <div>
                        <div class="flex justify-between items-end mb-2">
                            <label for="system_prompt" class="block text-sm font-semibold text-neutral-700">
                                System Message (Instruksi Dasar AI)
                            </label>
                        </div>
                        <textarea id="system_prompt" wire:model="system_prompt" rows="18"
                            class="w-full rounded-xl border-neutral-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-[15px] leading-relaxed py-4 px-5 font-mono transition-colors"
                            placeholder="Tuliskan instruksi sistem di sini..." required></textarea>
                        <p class="mt-3 text-sm text-neutral-500 bg-neutral-50 p-4 rounded-xl border border-neutral-200">
                            <strong>💡 Tips:</strong> Salin (Paste) seluruh teks dari dokumen <em>System Prompt Final</em> ke dalam kotak ini agar AI memahami seluruh struktur database dan logika pelaporan Z-Offline.
                        </p>
                    </div>

                    <div class="flex justify-end pt-4 border-t border-neutral-100">
                        <button type="submit"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-8 py-3 rounded-xl transition-all shadow-sm flex items-center gap-3 active:scale-95">
                            <svg wire:loading.remove wire:target="saveSettings" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <svg wire:loading wire:target="saveSettings" class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span wire:loading.remove wire:target="saveSettings">Simpan Konfigurasi</span>
                            <span wire:loading wire:target="saveSettings">Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

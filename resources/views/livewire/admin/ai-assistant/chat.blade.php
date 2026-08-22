<div>
    <x-slot:title>
        AI Assistant Chat
    </x-slot:title>

    <div class="max-w-4xl mx-auto space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-neutral-800">AI Database Assistant</h1>
            <a href="{{ route('admin.ai-assistant.settings') }}" wire:navigate
                class="px-4 py-2 bg-neutral-200 hover:bg-neutral-300 text-neutral-800 rounded-lg text-sm font-medium transition-colors">
                Pengaturan AI
            </a>
            <button wire:click="resetChat"
                class="px-4 py-2 bg-red-100 hover:bg-red-200 text-red-700 rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                Hapus Histori & Mulai Baru
            </button>
        </div>

        <div class="bg-white border border-neutral-200 rounded-2xl flex flex-col overflow-hidden h-[700px] shadow-sm relative">
            <!-- Chat Area -->
            <div id="chat-container" class="flex-1 overflow-y-auto p-6 space-y-6 bg-white" x-data="{ scrollToBottom() { this.$el.scrollTop = this.$el.scrollHeight; } }"
                x-init="scrollToBottom()" @message-sent.window="setTimeout(() => scrollToBottom(), 100)">
                @forelse($histories as $history)
                    @if ($history->role === 'user')
                        <div class="flex justify-end">
                            <div class="bg-neutral-100 text-neutral-900 max-w-[75%] rounded-3xl px-5 py-3 text-[15px] leading-relaxed">
                                <p class="whitespace-pre-wrap">{{ $history->message }}</p>
                            </div>
                        </div>
                    @else
                        <div class="flex justify-start">
                            <div class="flex gap-4 max-w-[85%]">
                                <div class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center flex-shrink-0 mt-1">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                </div>
                                <div class="text-neutral-800 markdown-body text-[15px] leading-relaxed w-full overflow-x-auto">
                                    {!! \Illuminate\Support\Str::markdown($history->message) !!}
                                </div>
                            </div>
                        </div>
                    @endif
                @empty
                    <div class="flex flex-col items-center justify-center h-full text-neutral-400 space-y-3">
                        <div class="w-16 h-16 bg-neutral-100 rounded-full flex items-center justify-center mb-2">
                            <svg class="w-8 h-8 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z">
                                </path>
                            </svg>
                        </div>
                        <p class="text-base font-medium text-neutral-600">Ada yang bisa saya bantu hari ini?</p>
                        <p class="text-sm">Tanyakan seputar penjualan, laba rugi, maupun stok produk.</p>
                    </div>
                @endforelse

                <!-- Loading Indicator -->
                <div wire:loading wire:target="fetchAiResponse" class="flex justify-start w-full">
                    <div class="flex gap-4 max-w-[85%]">
                        <div class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center flex-shrink-0 mt-1">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <div class="flex items-center space-x-2 mt-3">
                            <div class="w-2 h-2 bg-neutral-300 rounded-full animate-bounce"></div>
                            <div class="w-2 h-2 bg-neutral-300 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                            <div class="w-2 h-2 bg-neutral-300 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Input Area -->
            <div class="p-4 bg-white border-t border-neutral-100">
                <form wire:submit="sendMessage" class="relative max-w-3xl mx-auto flex items-end shadow-sm border border-neutral-300 rounded-2xl bg-white focus-within:ring-2 focus-within:ring-indigo-500/20 focus-within:border-indigo-500 overflow-hidden">
                    <textarea wire:model="message" wire:keydown.enter.prevent="sendMessage" rows="1"
                        class="w-full border-0 focus:ring-0 resize-none py-4 pl-4 pr-14 text-[15px] max-h-32 min-h-[56px] bg-transparent"
                        placeholder="Tanyakan sesuatu tentang database..." required></textarea>
                        
                    <button type="submit" wire:loading.attr="disabled" wire:target="sendMessage, fetchAiResponse"
                        class="absolute right-2 bottom-2 w-10 h-10 bg-indigo-600 hover:bg-indigo-700 text-white rounded-full flex items-center justify-center transition-colors disabled:bg-neutral-300 disabled:text-neutral-500 disabled:cursor-not-allowed">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                        </svg>
                    </button>
                </form>
                <div class="text-center mt-3 text-xs text-neutral-400">AI mungkin dapat melakukan kesalahan. Harap periksa kembali informasi penting.</div>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('callFetchAi', () => {
            @this.call('fetchAiResponse');
        });

        Livewire.on('triggerDownload', (event) => {
            window.open(event.url, '_blank');
        });
    });
</script>
<style>
    /* Styling khusus untuk format Markdown dari AI */
    .markdown-body {
        font-size: 0.9375rem; /* 15px */
        line-height: 1.6;
    }
    .markdown-body p { margin-bottom: 0.85rem; }
    .markdown-body p:last-child { margin-bottom: 0; }
    .markdown-body strong { font-weight: 600; color: #111827; }
    .markdown-body ul { list-style-type: disc; margin-left: 1.5rem; margin-bottom: 0.85rem; }
    .markdown-body ol { list-style-type: decimal; margin-left: 1.5rem; margin-bottom: 0.85rem; }
    .markdown-body h1, .markdown-body h2, .markdown-body h3 { font-weight: bold; margin-top: 1.2rem; margin-bottom: 0.5rem; color: #111827; }
    .markdown-body code { background-color: #f3f4f6; padding: 0.2rem 0.4rem; border-radius: 0.25rem; font-family: monospace; font-size: 0.85em; color: #ef4444; }
    .markdown-body pre { background-color: #1f2937; color: #f9fafb; padding: 1rem; border-radius: 0.5rem; overflow-x: auto; margin-bottom: 0.85rem; }
    .markdown-body pre code { background-color: transparent; padding: 0; color: inherit; }
    .markdown-body table { width: 100%; border-collapse: collapse; margin-bottom: 0.85rem; font-size: 0.875rem; }
    .markdown-body th, .markdown-body td { border: 1px solid #e5e7eb; padding: 0.6rem; text-align: left; }
    .markdown-body th { background-color: #f9fafb; font-weight: 600; color: #374151; }
</style>

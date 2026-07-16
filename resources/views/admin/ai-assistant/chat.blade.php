<x-layouts.admin>
    <x-slot:title>
        AI Assistant Chat
    </x-slot:title>

    <div class="max-w-4xl mx-auto space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-neutral-800">AI Database Assistant</h1>
            <a href="{{ route('admin.ai-assistant.settings') }}" class="px-4 py-2 bg-neutral-200 hover:bg-neutral-300 text-neutral-800 rounded-lg text-sm font-medium transition-colors">
                Pengaturan AI
            </a>
        </div>

        <div class="bg-white border border-neutral-200 rounded-xl flex flex-col overflow-hidden h-[600px] shadow-sm">
            <!-- Chat Area -->
            <div id="chat-container" class="flex-1 overflow-y-auto p-4 space-y-4 bg-neutral-50">
                @forelse($histories as $history)
                    @if($history->role === 'user')
                        <div class="flex justify-end">
                            <div class="bg-indigo-600 text-white max-w-[80%] rounded-2xl rounded-tr-sm px-4 py-3 shadow-sm">
                                <p class="whitespace-pre-wrap text-sm">{{ $history->message }}</p>
                            </div>
                        </div>
                    @else
                        <div class="flex justify-start">
                            <div class="bg-white border border-neutral-200 text-neutral-800 max-w-[80%] rounded-2xl rounded-tl-sm px-4 py-3 shadow-sm">
                                <p class="whitespace-pre-wrap text-sm">{!! str_replace("\n", '<br>', htmlspecialchars($history->message)) !!}</p>
                            </div>
                        </div>
                    @endif
                @empty
                    <div class="flex flex-col items-center justify-center h-full text-neutral-400 space-y-2">
                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                        <p class="text-sm">Belum ada percakapan. Mulai bertanya kepada AI!</p>
                    </div>
                @endforelse
                
                <!-- Loading Indicator (Hidden by default) -->
                <div id="loading-indicator" class="flex justify-start hidden">
                    <div class="bg-white border border-neutral-200 text-neutral-500 max-w-[80%] rounded-2xl rounded-tl-sm px-4 py-3 shadow-sm flex items-center space-x-2">
                        <svg class="animate-spin h-4 w-4 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-sm font-medium">AI sedang berpikir...</span>
                    </div>
                </div>
            </div>

            <!-- Input Area -->
            <div class="p-4 bg-white border-t border-neutral-200">
                <form id="chat-form" class="flex items-end space-x-2">
                    @csrf
                    <div class="flex-1">
                        <textarea id="message-input" name="message" rows="1" class="w-full rounded-xl border-neutral-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 resize-none py-3 px-4 text-sm" placeholder="Tanyakan sesuatu tentang database... misal: Berapa jumlah transaksi bulan ini?" required></textarea>
                    </div>
                    <button type="submit" id="send-button" class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl px-4 py-3 flex items-center justify-center transition-colors shadow-sm disabled:opacity-50">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chatContainer = document.getElementById('chat-container');
            const chatForm = document.getElementById('chat-form');
            const messageInput = document.getElementById('message-input');
            const sendButton = document.getElementById('send-button');
            const loadingIndicator = document.getElementById('loading-indicator');

            // Scroll to bottom on load
            chatContainer.scrollTop = chatContainer.scrollHeight;

            // Auto resize textarea
            messageInput.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight < 120 ? this.scrollHeight : 120) + 'px';
            });

            messageInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    if(this.value.trim() !== '') {
                        chatForm.dispatchEvent(new Event('submit'));
                    }
                }
            });

            chatForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                const message = messageInput.value.trim();
                if (!message) return;

                // Disable input and button
                messageInput.value = '';
                messageInput.style.height = 'auto';
                messageInput.disabled = true;
                sendButton.disabled = true;

                // Append user message to UI
                appendMessage(message, 'user');

                // Show loading
                loadingIndicator.classList.remove('hidden');
                chatContainer.scrollTop = chatContainer.scrollHeight;

                try {
                    const response = await fetch('{{ route('admin.ai-assistant.send') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('input[name="_csrf"]').value
                        },
                        body: JSON.stringify({ message: message })
                    });

                    const data = await response.json();
                    
                    // Hide loading
                    loadingIndicator.classList.add('hidden');

                    if (data.status === 'success') {
                        appendMessage(data.message, 'assistant');
                    } else {
                        appendMessage('Error: ' + data.message, 'assistant', true);
                    }
                } catch (error) {
                    loadingIndicator.classList.add('hidden');
                    appendMessage('Gagal terhubung ke server. Silakan coba lagi.', 'assistant', true);
                } finally {
                    messageInput.disabled = false;
                    sendButton.disabled = false;
                    messageInput.focus();
                    chatContainer.scrollTop = chatContainer.scrollHeight;
                }
            });

            function appendMessage(text, role, isError = false) {
                const div = document.createElement('div');
                div.className = role === 'user' ? 'flex justify-end' : 'flex justify-start';
                
                const innerDiv = document.createElement('div');
                let bgClass = '';
                if (role === 'user') {
                    bgClass = 'bg-indigo-600 text-white rounded-2xl rounded-tr-sm shadow-sm';
                } else {
                    bgClass = isError 
                        ? 'bg-red-50 border border-red-200 text-red-800 rounded-2xl rounded-tl-sm shadow-sm' 
                        : 'bg-white border border-neutral-200 text-neutral-800 rounded-2xl rounded-tl-sm shadow-sm';
                }
                
                innerDiv.className = `max-w-[80%] px-4 py-3 ${bgClass}`;
                
                const p = document.createElement('p');
                p.className = 'whitespace-pre-wrap text-sm';
                p.innerHTML = text.replace(/\n/g, '<br>');
                
                innerDiv.appendChild(p);
                div.appendChild(innerDiv);
                
                chatContainer.insertBefore(div, loadingIndicator);
                chatContainer.scrollTop = chatContainer.scrollHeight;
            }
        });
    </script>
</x-layouts.admin>

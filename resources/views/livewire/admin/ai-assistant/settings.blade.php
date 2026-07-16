<div>
    <x-slot:title>
        Pengaturan AI Assistant
    </x-slot:title>

    <div class="max-w-4xl mx-auto space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-neutral-800">Pengaturan AI Assistant</h1>
            <a href="{{ route('admin.ai-assistant.index') }}" wire:navigate
                class="px-4 py-2 bg-neutral-200 hover:bg-neutral-300 text-neutral-800 rounded-lg text-sm font-medium transition-colors">
                Kembali ke Chat
            </a>
        </div>

        @if (session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl p-4">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white border border-neutral-200 rounded-xl p-6 shadow-sm">
            <form wire:submit="saveSettings" class="space-y-6">

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="provider" class="block text-sm font-medium text-neutral-700 mb-2">Penyedia AI (Provider)</label>
                        <select id="provider" wire:model="provider" class="w-full rounded-lg border-neutral-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="gemini">Google Gemini</option>
                            <option value="openai">OpenAI (ChatGPT)</option>
                            <option value="groq">Groq (Gratis & Cepat)</option>
                            <option value="ollama">Ollama (Server Lokal/VPS)</option>
                        </select>
                    </div>

                    <div>
                        <label for="model" class="block text-sm font-medium text-neutral-700 mb-2">Model AI</label>
                        <select id="model" wire:model="model" class="w-full rounded-lg border-neutral-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <optgroup label="Google Gemini">
                                <option value="gemini-3.5-flash">Gemini 3.5 Flash (Super Cepat)</option>
                                <option value="gemini-2.5-pro">Gemini 2.5 Pro (Sangat Akurat)</option>
                            </optgroup>
                            <optgroup label="OpenAI">
                                <option value="gpt-4o-mini">GPT-4o Mini (Lebih Murah & Cepat)</option>
                                <option value="gpt-4o">GPT-4o (Sangat Akurat)</option>
                            </optgroup>
                            <optgroup label="Ollama (Local/VPS)">
                                <option value="llama3.1">Llama 3.1 (8B)</option>
                                <option value="llama3.2:3b">Llama 3.2 (3B - Sweet Spot CPU)</option>
                                <option value="qwen2.5:1.5b">Qwen 2.5 (1.5B - Sangat Ringan)</option>
                                <option value="qwen2.5:3b">Qwen 2.5 (3B - Ringan)</option>
                                <option value="qwen2.5:7b">Qwen 2.5 (7B - Menengah)</option>
                                <option value="qwen2.5">Qwen 2.5 (Latest)</option>
                                <option value="deepseek-r1">DeepSeek R1</option>
                            </optgroup>
                            <optgroup label="Groq (Gratis & Tercepat)">
                                <option value="llama-3.3-70b-versatile">Llama 3.3 70B (Sangat Akurat)</option>
                                <option value="llama-3.1-8b-instant">Llama 3.1 8B (Super Cepat)</option>
                                <option value="mixtral-8x7b-32768">Mixtral 8x7B</option>
                            </optgroup>
                        </select>
                    </div>
                </div>
                <p class="mt-2 text-xs text-neutral-500">Pastikan Anda telah mengisi API Key/URL yang sesuai di file <code>.env</code> (seperti <code>OPENAI_URL</code> atau <code>OLLAMA_URL</code> jika menggunakan VPS lokal).</p>

                <div>
                    <label for="system_prompt" class="block text-sm font-medium text-neutral-700 mb-2">Instruksi Dasar
                        (System Prompt)</label>
                    <textarea id="system_prompt" wire:model="system_prompt" rows="6"
                        class="w-full rounded-lg border-neutral-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        required></textarea>
                    <p class="mt-2 text-xs text-neutral-500">Instruksi ini akan ditambahkan dengan aturan default dari
                        sistem (aturan bahwa ini database MySQL, tool GetSchema, dll).</p>
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-6 py-2 rounded-lg transition-colors shadow-sm flex items-center gap-2">
                        <span wire:loading.remove wire:target="saveSettings">Simpan Pengaturan</span>
                        <span wire:loading wire:target="saveSettings">Menyimpan...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

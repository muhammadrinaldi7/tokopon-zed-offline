<x-layouts.admin>
    <x-slot:title>
        Pengaturan AI Assistant
    </x-slot:title>

    <div class="max-w-4xl mx-auto space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-neutral-800">Pengaturan AI Assistant</h1>
            <a href="{{ route('admin.ai-assistant.index') }}" class="px-4 py-2 bg-neutral-200 hover:bg-neutral-300 text-neutral-800 rounded-lg text-sm font-medium transition-colors">
                Kembali ke Chat
            </a>
        </div>

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl p-4">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white border border-neutral-200 rounded-xl p-6 shadow-sm">
            <form action="{{ route('admin.ai-assistant.settings.update') }}" method="POST" class="space-y-6">
                @csrf
                
                <div>
                    <label for="model" class="block text-sm font-medium text-neutral-700 mb-2">Model AI</label>
                    <select id="model" name="model" class="w-full rounded-lg border-neutral-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="gemini-1.5-flash" {{ $setting->model === 'gemini-1.5-flash' ? 'selected' : '' }}>Gemini 1.5 Flash (Cepat)</option>
                        <option value="gemini-1.5-pro" {{ $setting->model === 'gemini-1.5-pro' ? 'selected' : '' }}>Gemini 1.5 Pro (Akurat)</option>
                    </select>
                    <p class="mt-2 text-xs text-neutral-500">Pilih model AI yang akan digunakan untuk asisten. Flash lebih cepat, Pro lebih teliti dalam analisis.</p>
                </div>

                <div>
                    <label for="system_prompt" class="block text-sm font-medium text-neutral-700 mb-2">Instruksi Dasar (System Prompt)</label>
                    <textarea id="system_prompt" name="system_prompt" rows="6" class="w-full rounded-lg border-neutral-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>{{ $setting->system_prompt }}</textarea>
                    <p class="mt-2 text-xs text-neutral-500">Instruksi ini akan ditambahkan dengan aturan default dari sistem (aturan bahwa ini database MySQL, tool GetSchema, dll).</p>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-6 py-2 rounded-lg transition-colors shadow-sm">
                        Simpan Pengaturan
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.admin>

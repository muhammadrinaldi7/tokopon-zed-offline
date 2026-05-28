<div class="flex items-center justify-center min-h-[calc(100vh-72px)] px-4 py-12 bg-gray-50">
    <div class="w-full max-w-md">
        {{-- Card --}}
        <div class="bg-white rounded-2xl shadow-lg shadow-gray-200/50 border border-gray-100 p-8">
            {{-- Logo --}}
            <div class="text-center mb-8">
                <a href="/" wire:navigate class="inline-block">
                    <span
                        class="text-3xl font-bold bg-linear-to-r from-[#0097FF] via-[#4E44DB] to-[#013559] bg-clip-text text-transparent">Tokopon</span>
                </a>
                <p class="mt-2 text-sm text-gray-500">Atur ulang password akun Anda</p>
            </div>

            {{-- Form --}}
            <form wire:submit="resetPassword">
                {{-- Hidden Token & Email --}}
                <input type="hidden" wire:model="token">

                {{-- Email (Readonly karena dari URL, tapi tetap bisa diubah jika error) --}}
                <div class="mb-5">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
                    <input wire:model="email" type="email" id="email" placeholder="nama@email.com"
                        class="w-full px-4 py-3 text-sm border border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition">
                    @error('email')
                        <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Password Baru --}}
                <div class="mb-5">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">Password Baru</label>
                    <input wire:model.blur="password" type="password" id="password" placeholder="Minimal 8 karakter"
                        class="w-full px-4 py-3 text-sm border border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition">
                    @error('password')
                        <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Confirm Password --}}
                <div class="mb-6">
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1.5">Konfirmasi
                        Password Baru</label>
                    <input wire:model.blur="password_confirmation" type="password" id="password_confirmation"
                        placeholder="Ulangi password baru"
                        class="w-full px-4 py-3 text-sm border border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition">
                    @error('password_confirmation')
                        <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Submit --}}
                <button type="submit"
                    class="w-full py-3 text-sm font-semibold text-white bg-blue-500 rounded-xl shadow-md shadow-blue-500/30 transition hover:bg-blue-600 hover:shadow-lg hover:shadow-blue-500/40 disabled:opacity-50"
                    wire:loading.attr="disabled">
                    <span wire:loading.remove>Simpan Password Baru</span>
                    <span wire:loading>Memproses...</span>
                </button>
            </form>
        </div>
    </div>
</div>

<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Password;
use Laravel\Fortify\Contracts\ResetsUserPasswords;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Reset Password - TokoPun')]
class ResetPassword extends Component
{
    public string $token = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function mount(): void
    {
        $this->token = request()->route('token') ?? '';
        $this->email = request()->query('email') ?? '';
    }

    public function resetPassword(): void
    {
        // 1. Validasi lokal di Livewire terlebih dahulu (Sama seperti Register)
        $this->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed', // Menggunakan 'confirmed' kembali aman di sini
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        // 2. Cari user berdasarkan email
        $user = User::where('email', $this->email)->first();

        if (! $user) {
            $this->addError('email', 'Email tidak ditemukan.');
            return;
        }

        // 3. Validasi token kecocokan ke Broker Laravel
        $tokenIsValid = Password::broker(config('fortify.passwords'))->tokenExists($user, $this->token);

        if (! $tokenIsValid) {
            $this->addError('email', 'Token reset password tidak valid atau sudah kedaluwarsa.');
            return;
        }

        // 4. Eksekusi reset password langsung menggunakan Action Fortify tanpa lewat Broker request
        app(ResetsUserPasswords::class)->reset($user, [
            'password' => $this->password,
            'password_confirmation' => $this->password_confirmation,
        ]);

        // 5. Berikan flash session sukses dan redirect ke login
        session()->flash('status', 'Password Anda berhasil diperbarui. Silakan masuk.');
        $this->redirect('/login', navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.reset-password');
    }
}

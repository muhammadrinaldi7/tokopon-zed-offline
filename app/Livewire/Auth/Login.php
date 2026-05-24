<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Login - TokoPun')]
class Login extends Component
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    protected array $rules = [
        'email' => 'required|email',
        'password' => 'required|min:6',
    ];

    protected array $messages = [
        'email.required' => 'Email wajib diisi.',
        'email.email' => 'Format email tidak valid.',
        'password.required' => 'Password wajib diisi.',
        'password.min' => 'Password minimal 6 karakter.',
    ];

    public function login(): void
    {
        $this->validate();

        if (!Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            $this->addError('email', 'Email atau password salah.');
            return;
        }

        session()->regenerate();

        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        if ($user->roles->count() > 0 && !$user->hasRole('user')) {
            $this->redirect('/', navigate: true);
        } else {
            Auth::logout();
            $this->addError('email', 'Akses ditolak. Aplikasi ini hanya untuk operasional.');
            return;
        }
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}

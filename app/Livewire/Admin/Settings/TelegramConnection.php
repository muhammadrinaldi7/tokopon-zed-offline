<?php

namespace App\Livewire\Admin\Settings;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin', ['title' => 'Integrasi Telegram - TokoPun'])]
class TelegramConnection extends Component
{
    use WithPagination;

    public $search = '';
    
    // Modal Edit State
    public $isEditModalOpen = false;
    public $editingUser = null;
    public $telegramChatId = '';

    public function mount()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user->hasAnyRole(['admin', 'superadmin'])) {
            return redirect('/admin/dashboard');
        }
    }

    public function with()
    {
        return [
            'users' => User::with(['roles', 'branch'])
                ->whereHas('roles', function ($q) {
                    $q->whereNotIn('name', ['customer', 'user']);
                })
                ->when($this->search, function ($q) {
                    $q->where(function ($query) {
                        $query->where('name', 'like', '%' . $this->search . '%')
                            ->orWhere('email', 'like', '%' . $this->search . '%')
                            ->orWhere('telegram_chat_id', 'like', '%' . $this->search . '%');
                    });
                })
                ->orderByDesc('id')
                ->paginate(15),
        ];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function openEditModal($userId)
    {
        $this->editingUser = User::findOrFail($userId);
        $this->telegramChatId = $this->editingUser->telegram_chat_id;
        $this->isEditModalOpen = true;
    }

    public function closeModal()
    {
        $this->isEditModalOpen = false;
        $this->editingUser = null;
        $this->telegramChatId = '';
    }

    public function saveTelegramId()
    {
        if ($this->editingUser) {
            $this->validate([
                'telegramChatId' => 'nullable|string|max:255|unique:users,telegram_chat_id,' . $this->editingUser->id,
            ], [
                'telegramChatId.unique' => 'Chat ID Telegram ini sudah terdaftar pada pengguna lain.',
            ]);

            $this->editingUser->update([
                'telegram_chat_id' => $this->telegramChatId ?: null,
            ]);

            $this->dispatch('admin-alert', type: 'success', message: 'Koneksi Telegram untuk ' . $this->editingUser->name . ' berhasil diperbarui!');
            $this->closeModal();
        }
    }

    public function disconnectTelegram($userId)
    {
        $user = User::findOrFail($userId);
        $user->update(['telegram_chat_id' => null]);
        
        $this->dispatch('admin-alert', type: 'success', message: 'Koneksi Telegram untuk ' . $user->name . ' berhasil diputus.');
    }

    public function render()
    {
        return view('livewire.admin.settings.telegram-connection');
    }
}

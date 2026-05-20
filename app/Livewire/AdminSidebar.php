<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;

class AdminSidebar extends Component
{
    public $unreadCount = 0;
    public $userId;

    public function mount()
    {
        $this->userId = Auth::id();
        $this->updateUnreadCount();
    }

    #[On('echo-private:user.{userId},MessageSent')]
    public function updateUnreadCount($event = null)
    {
        // if ($this->userId) {
        //     $this->unreadCount = Message::where('user_id', $this->userId)
        //         ->where('is_read', false)
        //         ->count();
        // }
    }

    public function render()
    {
        return view('livewire.admin-sidebar');
    }
}

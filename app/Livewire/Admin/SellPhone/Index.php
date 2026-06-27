<?php

namespace App\Livewire\Admin\SellPhone;

use App\Models\SellPhone;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = '';
    public string $status_inspeksi = 'pass';
    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatus()
    {
        $this->resetPage();
    }

    #[Layout('layouts.z')]
    public function render()
    {
        $activeUnitId = \App\Models\User::findOrFail(\Illuminate\Support\Facades\Auth::id())->getActiveBusinessUnitId();

        $query = SellPhone::with(['user', 'handledBy', 'businessUnit', 'inspections'])
            ->where('business_unit_id', $activeUnitId)
            ->latest();

        if ($this->search) {
            $query->where(function ($q) {
                $q->whereHas('user', function ($q2) {
                    $q2->where('name', 'like', '%' . $this->search . '%');
                })->orWhere('phone_model', 'like', '%' . $this->search . '%')
                    ->orWhere('phone_brand', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->status_inspeksi) {
            $query->whereHas('inspections', function ($q) {
                $q->where('verdict', $this->status_inspeksi);
            });
        }

        // dd($query->get());
        return view('livewire.admin.sell-phone.index', [
            'sellPhones' => $query->paginate(10)
        ]);
    }
}

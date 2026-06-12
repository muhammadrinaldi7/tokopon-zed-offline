<?php

namespace App\Livewire\Admin\Components;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\BusinessUnit;

class BusinessUnitSwitcher extends Component
{
    public $activeUnitId;
    public $businessUnits = [];

    public function mount()
    {
        $user = Auth::user();
        if ($user && ($user->hasRole('superadmin') || $user->hasRole('admin') || $user->hasRole('director'))) {
            $this->businessUnits = BusinessUnit::where('is_active', true)->get();
            // Default to user's unit if session is empty
            $this->activeUnitId = session('active_business_unit_id', $user->business_unit_id);
        }
    }

    public function updatedActiveUnitId($value)
    {
        if ($value) {
            session(['active_business_unit_id' => $value]);
            
            // Reload the page to apply the new active business unit everywhere
            return redirect(request()->header('Referer'));
        }
    }

    public function render()
    {
        return view('livewire.admin.components.business-unit-switcher');
    }
}

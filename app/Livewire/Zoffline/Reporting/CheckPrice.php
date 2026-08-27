<?php

namespace App\Livewire\Zoffline\Reporting;

use App\Models\ProductAccurate;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;

#[Layout('layouts.z')]
class CheckPrice extends Component
{
    use WithPagination;

    public $search = '';
    public $filterBrandName = '';
    public $filterCategoryName = '';
    public $filterProyek = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterBrandName()
    {
        $this->resetPage();
    }

    public function updatingFilterCategoryName()
    {
        $this->resetPage();
    }

    public function updatingFilterProyek()
    {
        $this->resetPage();
    }
    
    public function back()
    {
        return $this->redirectRoute('zoffline', navigate: true);
    }

    public function render()
    {
        $baseQuery = ProductAccurate::where('business_unit_id', 2);

        // Ambil data untuk dropdown filters
        $availableBrands = (clone $baseQuery)->whereNotNull('brandName')->where('brandName', '!=', '')->distinct()->pluck('brandName')->sort()->values();
        $availableCategories = (clone $baseQuery)->whereNotNull('categoryName')->where('categoryName', '!=', '')->distinct()->pluck('categoryName')->sort()->values();
        $availableProyeks = (clone $baseQuery)->whereNotNull('proyek')->where('proyek', '!=', '')->distinct()->pluck('proyek')->sort()->values();

        $query = clone $baseQuery;

        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('item_no', 'like', '%' . $this->search . '%');
            });
        }

        if (!empty($this->filterBrandName)) {
            $query->where('brandName', $this->filterBrandName);
        }

        if (!empty($this->filterCategoryName)) {
            $query->where('categoryName', $this->filterCategoryName);
        }

        if (!empty($this->filterProyek)) {
            $query->where('proyek', $this->filterProyek);
        }

        $devices = $query->orderBy('name')
            ->paginate(15);

        return view('livewire.zoffline.reporting.check-price', compact('devices', 'availableBrands', 'availableCategories', 'availableProyeks'));
    }
}

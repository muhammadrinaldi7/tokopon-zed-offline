<?php

namespace App\Livewire\Zoffline\Ecommerce;

use App\Models\BusinessUnit;
use App\Models\ProductAccurate;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.z')]
class ProductCuration extends Component
{
    use WithPagination;

    public string $search = '';
    public ?int $selectedBusinessUnitId = null;
    public string $selectedCategory = '';
    public $businessUnits = [];
    public $categories = [];

    public function mount()
    {
        $this->businessUnits = BusinessUnit::where('is_active', true)->where('is_visible_mobile', true)->get();
        $this->categories = ProductAccurate::whereNotNull('categoryName')
            ->where('categoryName', '!=', '')
            ->distinct()
            ->pluck('categoryName')
            ->values();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSelectedBusinessUnitId()
    {
        $this->resetPage();
    }

    public function updatingSelectedCategory()
    {
        $this->resetPage();
    }

    public function render()
    {
        $onlineWarehouseIds = Warehouse::where('is_online_store', true)->pluck('id')->toArray();

        $query = ProductAccurate::query()
            ->where('base_price', '>', 0)
            ->with([
                'businessUnit',
                'product.media',
                'productVariants.media',
                'warehouseStocks' => function ($q) use ($onlineWarehouseIds) {
                    $q->whereIn('warehouse_id', $onlineWarehouseIds);
                }
            ]);

        if ($this->selectedBusinessUnitId) {
            $query->where('business_unit_id', $this->selectedBusinessUnitId);
        } else {
            $query->whereHas('businessUnit', function ($bu) {
                $bu->where('is_active', true)->where('is_visible_mobile', true);
            });
        }

        if (!empty($this->search)) {
            $s = trim($this->search);
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('item_no', 'like', "%{$s}%");
            });
        }

        if (!empty($this->selectedCategory)) {
            $query->where('categoryName', $this->selectedCategory);
        }

        $products = $query->paginate(24);

        return view('livewire.zoffline.ecommerce.product-curation', [
            'products' => $products,
            'onlineWarehouseCount' => count($onlineWarehouseIds),
        ]);
    }
}

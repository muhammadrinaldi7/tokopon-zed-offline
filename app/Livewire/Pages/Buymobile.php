<?php

namespace App\Livewire\Pages;

use App\Models\Brand; // Pastikan Model Brand di-import
use App\Models\Product;
use Livewire\Component;

class Buymobile extends Component
{
    public $selectedBrand = null;

    public function setBrand($name)
    {
        $this->selectedBrand = $name;
    }

    public function goBack()
    {
        return redirect()->to('/');
    }

    public function render()
    {
        // 1. Ambil data brands dari database (urutkan sesuai kebutuhan, misal by nama atau ID)
        $brands = Brand::orderBy('id', 'asc')->get();

        $query1 = Product::with(['variants', 'brand'])->availableForCustomer();
        $query2 = \App\Models\SecondProduct::with(['variants', 'brand'])->availableForCustomer();

        // 2. Filter berdasarkan brand jika selectedBrand tidak null
        if ($this->selectedBrand) {
            $query1->whereHas('brand', function ($q) {
                $q->where('name', $this->selectedBrand);
            });
            $query2->whereHas('brand', function ($q) {
                $q->where('name', $this->selectedBrand);
            });
        }

        $products1 = $query1->get()->map(function ($item) {
            $item->is_second_catalog = false;
            return $item;
        });

        $products2 = $query2->get()->map(function ($item) {
            $item->is_second_catalog = true;
            return $item;
        });

        $products = $products1->concat($products2);

        // 3. Mengelompokkan produk berdasarkan nama brand
        $groupedProducts = $products->groupBy(function ($item) {
            return $item->brand->name ?? 'Lainnya';
        });
        // 4. Kirim $brands ke view
        return view('livewire.pages.buymobile', [
            'brands' => $brands,
            'products' => $products,
            'groupedProducts' => $groupedProducts
        ]);
    }
}

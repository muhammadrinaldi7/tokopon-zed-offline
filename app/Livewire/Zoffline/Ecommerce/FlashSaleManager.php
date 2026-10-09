<?php

namespace App\Livewire\Zoffline\Ecommerce;

use App\Models\BusinessUnit;
use App\Models\EcommerceFlashSale;
use App\Models\EcommerceFlashSaleItem;
use App\Models\ProductAccurate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.z')]
class FlashSaleManager extends Component
{
    public $flashSales = [];
    public $businessUnits = [];

    // Form Event State
    public bool $showEventModal = false;
    public ?int $eventId = null;
    public string $title = 'Flash Sale Spesial';
    public string $start_time = '';
    public string $end_time = '';
    public ?int $business_unit_id = null;
    public bool $is_active = true;

    // Form Item Modal State
    public bool $showItemModal = false;
    public ?int $selectedFlashSaleId = null;
    public ?int $selectedProductId = null;
    public string $productSearch = '';
    public $productSearchResults = [];
    public ?string $flashSalePrice = null;
    public ?string $originalPrice = null;
    public int $quotaStock = 10;

    public function mount()
    {
        $this->businessUnits = BusinessUnit::where('is_active', true)->where('is_visible_mobile', true)->get();
        $this->loadData();
    }

    public function loadData()
    {
        $this->flashSales = EcommerceFlashSale::with(['businessUnit', 'items.productAccurate'])
            ->orderByDesc('id')
            ->get();
    }

    public function openEventModal()
    {
        $this->eventId = null;
        $this->title = 'Flash Sale ' . now()->format('d M');
        $this->start_time = now()->format('Y-m-d\TH:i');
        $this->end_time = now()->addHours(6)->format('Y-m-d\TH:i');
        $this->business_unit_id = null;
        $this->is_active = true;
        $this->showEventModal = true;
    }

    public function saveEvent()
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
        ]);

        EcommerceFlashSale::updateOrCreate(
            ['id' => $this->eventId],
            [
                'title' => $this->title,
                'business_unit_id' => $this->business_unit_id ?: null,
                'start_time' => $this->start_time,
                'end_time' => $this->end_time,
                'is_active' => $this->is_active,
            ]
        );

        $this->showEventModal = false;
        $this->loadData();
        $this->dispatch('toast', title: 'Berhasil', message: 'Sesi Flash Sale berhasil disimpan.', type: 'success');
    }

    public function toggleEventActive(int $id)
    {
        $fs = EcommerceFlashSale::findOrFail($id);
        $fs->update(['is_active' => !$fs->is_active]);
        $this->loadData();
    }

    public function deleteEvent(int $id)
    {
        EcommerceFlashSale::findOrFail($id)->delete();
        $this->loadData();
        $this->dispatch('toast', title: 'Berhasil', message: 'Sesi Flash Sale dihapus.', type: 'success');
    }

    // ITEM MANAGEMENT
    public function openAddItemModal(int $flashSaleId)
    {
        $this->selectedFlashSaleId = $flashSaleId;
        $this->selectedProductId = null;
        $this->productSearch = '';
        $this->productSearchResults = [];
        $this->flashSalePrice = '';
        $this->originalPrice = '';
        $this->quotaStock = 10;
        $this->showItemModal = true;
    }

    public function updatedProductSearch($value)
    {
        if (strlen($value) < 2) {
            $this->productSearchResults = [];
            return;
        }

        $this->productSearchResults = ProductAccurate::where('name', 'like', "%{$value}%")
            ->orWhere('item_no', 'like', "%{$value}%")
            ->take(8)
            ->get();
    }

    public function selectProduct(int $productId)
    {
        $prod = ProductAccurate::find($productId);
        if (!$prod) return;

        $this->selectedProductId = $prod->id;
        $this->productSearch = $prod->name;
        $this->originalPrice = (string) ((int) $prod->base_price);
        $this->flashSalePrice = (string) ((int) ($prod->base_price * 0.9)); // Default diskon 10%
        $this->productSearchResults = [];
    }

    public function saveItem()
    {
        $this->validate([
            'selectedFlashSaleId' => 'required|exists:ecommerce_flash_sales,id',
            'selectedProductId' => 'required|exists:product_accurates,id',
            'flashSalePrice' => 'required|numeric|min:1',
            'quotaStock' => 'required|integer|min:1',
        ]);

        EcommerceFlashSaleItem::updateOrCreate(
            [
                'flash_sale_id' => $this->selectedFlashSaleId,
                'product_accurate_id' => $this->selectedProductId,
            ],
            [
                'flash_sale_price' => (float) $this->flashSalePrice,
                'original_price' => $this->originalPrice ? (float) $this->originalPrice : null,
                'quota_stock' => $this->quotaStock,
                'is_active' => true,
            ]
        );

        $this->showItemModal = false;
        $this->loadData();
        $this->dispatch('toast', title: 'Berhasil', message: 'Produk berhasil ditambahkan ke Flash Sale.', type: 'success');
    }

    public function removeItem(int $itemId)
    {
        EcommerceFlashSaleItem::findOrFail($itemId)->delete();
        $this->loadData();
        $this->dispatch('toast', title: 'Berhasil', message: 'Item Flash Sale dihapus.', type: 'success');
    }

    public function render()
    {
        return view('livewire.zoffline.ecommerce.flash-sale-manager');
    }
}

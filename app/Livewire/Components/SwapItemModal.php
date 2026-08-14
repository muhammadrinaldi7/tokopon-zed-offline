<?php

namespace App\Livewire\Components;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\SecondProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

class SwapItemModal extends Component
{
    public $showModal = false;
    public $orderId;
    public $order;
    public $swappingItemId = null;
    public $searchQuery = '';
    public $searchResults = [];

    #[On('openSwapModal')]
    public function openModal($orderId)
    {
        $this->orderId = $orderId;
        $this->order = Order::with('items.variant.product')->find($orderId);
        $this->showModal = true;
        $this->resetSwapState();
    }

    public function resetSwapState()
    {
        $this->swappingItemId = null;
        $this->searchQuery = '';
        $this->searchResults = [];
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->orderId = null;
        $this->order = null;
        $this->resetSwapState();
    }

    public function startSwap($itemId)
    {
        $this->swappingItemId = $itemId;
        $this->searchQuery = '';
        $this->searchResults = [];
    }

    public function cancelSwap()
    {
        $this->resetSwapState();
    }

    public function updatedSearchQuery()
    {
        if (strlen($this->searchQuery) < 3) {
            $this->searchResults = [];
            return;
        }

        $warehouseId = \Illuminate\Support\Facades\Auth::user()->warehouse_id;

        // Cari varian baru dari ProductAccurate yang stoknya tersedia
        $results = \App\Models\ProductAccurate::with('product')
            ->where(function ($q) {
                $q->where('item_no', 'like', '%' . $this->searchQuery . '%')
                    ->orWhere('name', 'like', '%' . $this->searchQuery . '%');
            })
            ->whereHas('warehouseStocks', function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId); // Hapus filter stock > 0 agar bisa pilih item indent (stok 0)
            })
            ->get()->map(function ($v) {
                return [
                    'id' => $v->id,
                    'type' => \App\Models\ProductAccurate::class,
                    'name' => $v->name,
                    'price' => $v->base_price,
                    'stock' => $v->warehouseStocks->first()->stock ?? 0,
                    'sku' => $v->item_no
                ];
            });

        $this->searchResults = $results->toArray();
    }

    public function executeSwap($newVariantId, $newVariantType, $newPrice)
    {
        $oldItem = OrderItem::find($this->swappingItemId);
        if (!$oldItem || !$this->order) return;

        DB::beginTransaction();
        try {
            // Dapatkan nama/SKU item lama dan baru untuk keperluan log notes
            $oldName = $oldItem->product_name ?? ($oldItem->variant->name ?? 'Unknown');
            if (empty($oldName) && $oldItem->variant) {
                $oldName = $oldItem->variant->item_no ?? $oldItem->variant->sku ?? 'Unknown';
            }

            $newVariant = $newVariantType::find($newVariantId);
            $newName = $newVariant ? ($newVariant->name ?? $newVariant->item_no ?? 'Unknown') : 'Unknown';

            // Update the Order Item
            $oldItem->product_variant_id = $newVariantId;
            $oldItem->product_variant_type = $newVariantType;
            $oldItem->price_at_checkout = $newPrice;
            $oldItem->subtotal = $newPrice * $oldItem->qty;
            // Reset SN karena item berubah
            $oldItem->serial_number = null; 
            
            // Reset diskon karena item diganti (tidak boleh mewarisi diskon barang lama)
            $oldItem->discount_amount = 0;
            $oldItem->promo_discount_amount = 0;

            // Recalculate Order Global Discount
            $newOrderDiscount = $this->order->items()
                ->where('id', '!=', $oldItem->id)
                ->sum(DB::raw('discount_amount + promo_discount_amount'));

            // Validasi: Grand Total baru tidak boleh lebih kecil dari DP yang sudah dibayar
            $newGrandTotal = $this->order->items()
                ->where('id', '!=', $oldItem->id)
                ->sum('subtotal') + ($newPrice * $oldItem->qty) - $newOrderDiscount;
            $totalDp = $this->order->payments()->where('status', 'PAID')->sum('amount');
            if ($newGrandTotal < $totalDp) {
                throw new \Exception(
                    'Grand Total baru (Rp ' . number_format($newGrandTotal, 0, ',', '.') . 
                    ') tidak boleh lebih kecil dari DP yang sudah dibayar (Rp ' . 
                    number_format($totalDp, 0, ',', '.') . ').'
                );
            }

            $oldItem->save();

            // Recalculate Order Totals
            $this->order->total_amount = $this->order->items()->sum('subtotal');
            $this->order->discount_amount = $newOrderDiscount;
            $this->order->grand_total = $this->order->total_amount - $this->order->discount_amount;

            // Catat history swap di notes (sementara tanpa approval)
            $user = \Illuminate\Support\Facades\Auth::user()->name ?? 'System';
            $swapNote = "\n[" . now()->format('Y-m-d H:i') . "] $user melakukan Swap Item dari \"$oldName\" menjadi \"$newName\".";
            $this->order->notes = ($this->order->notes ?? '') . $swapNote;

            $this->order->save();

            // TODO: SYNC TO ACCURATE (Phase 3 Backend Logic)
            $this->syncSwapToAccurate();

            DB::commit();
            $this->dispatch('toast', title: 'Berhasil', message: 'Item berhasil ditukar!', type: 'success');
            $this->closeModal();
            $this->dispatch('refreshOrderDetails');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal melakukan Swap Item: " . $e->getMessage());
            $this->dispatch('toast', title: 'Gagal', message: 'Terjadi kesalahan sistem.', type: 'error');
        }
    }

    private function syncSwapToAccurate()
    {
        // 1. Get the current Sales Order Accurate ID
        $soDoc = $this->order->accurateDocs()->where('doc_type', 'SALES_ORDER')->where('status', 'SUCCESS')->first();
        if (!$soDoc || !$soDoc->accurate_id) return;

        $dbSource = strtolower($this->order->businessUnit->code ?? 'syihab');
        $accurateService = app(\App\Services\AccurateService::class);

        // Fetch existing SO details from Accurate to get detailItem IDs
        // Ini wajib dilakukan agar saat dikirim ulang, Accurate akan UPDATE (menimpa) baris yang sudah ada, BUKAN menambah baris baru.
        $existingSo = $accurateService->getSalesOrderDetail($soDoc->accurate_id, $dbSource);
        $existingDetailItems = $existingSo['detailItem'] ?? [];

        // 2. Re-build the entire detailItem payload for the SO based on the new items
        $detailItem = [];
        $index = 0;
        foreach ($this->order->items as $item) {
            // Fetch Accurate Item No
            $variant = $item->variant;

            if ($variant instanceof \App\Models\ProductAccurate) {
                $itemNo = $variant->item_no;
            } else {
                $itemNo = $variant->accurate_item_no ?? $variant->sku ?? $variant->item_no;
            }

            $dItem = [
                'itemNo' => $itemNo,
                'unitPrice' => (float)$item->price_at_checkout,
                'quantity' => (float)$item->qty,
                'detailName' => $item->product_name ?? ($variant->name ?? 'Unknown'),
                'itemCashDiscount' => (float)($item->discount_amount + $item->promo_discount_amount),
            ];

            // 2A. Tambahkan Serial Number (jika ada)
            if (!empty($item->serial_number)) {
                $sns = array_filter(array_map('trim', explode(',', $item->serial_number)));
                if (count($sns) > 0) {
                    $detailSNs = [];
                    foreach ($sns as $sn) {
                        $detailSNs[] = ['serialNumberNo' => $sn, 'quantity' => 1];
                    }
                    $dItem['detailSerialNumber'] = $detailSNs;
                }
            }

            // 2B. Tambahkan Salesman (jika ada)
            $salesIds = $item->sales_ids;
            if (is_string($salesIds)) {
                $salesIds = json_decode($salesIds, true);
            }
            if (!empty($salesIds) && is_array($salesIds)) {
                $employeeNos = \App\Models\Employe::whereIn('id', $salesIds)
                    ->pluck('employee_no')
                    ->filter()
                    ->values()
                    ->toArray();
                    
                if (!empty($employeeNos)) {
                    $dItem['salesmanListNumber'] = $employeeNos;
                }
            }

            if (isset($existingDetailItems[$index]['id'])) {
                $dItem['id'] = $existingDetailItems[$index]['id'];
            }

            $detailItem[] = $dItem;
            $index++;
        }

        $branchName = $this->order->branch->name ?? (\Illuminate\Support\Facades\Auth::user()->branch->name ?? 'Banjarbaru');

        $payload = [
            'id' => $soDoc->accurate_id,
            'branchName' => $branchName,
            'detailItem' => $detailItem
        ];

        // 3. Send update to Accurate
        try {
            $accurateService->postSalesOrder($payload, $dbSource);
        } catch (\Exception $e) {
            Log::warning("Gagal sync Swap Item ke Accurate SO: " . $e->getMessage());
            throw $e;
        }
    }

    public function render()
    {
        return view('livewire.components.swap-item-modal');
    }
}

<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\BusinessUnit;
use App\Models\EcommerceFlashSale;
use App\Models\EcommerceFlashSaleItem;
use App\Models\ProductAccurate;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FlashSaleController extends Controller
{
    /**
     * Tampilan utama Manajemen Flash Sale Kilat (Inertia React)
     */
    public function index(Request $request): Response
    {
        $now = Carbon::now();

        $flashSales = EcommerceFlashSale::with([
            'businessUnit:id,name,code',
            'items' => function ($q) {
                $q->with(['productAccurate.product']);
            }
        ])
        ->orderByDesc('id')
        ->get()
        ->map(function ($fs) use ($now) {
            $isOngoing = $fs->is_active && $now->between($fs->start_time, $fs->end_time);
            $isUpcoming = $now->lt($fs->start_time);
            $isEnded = $now->gt($fs->end_time);

            $statusKey = $isOngoing ? 'ongoing' : ($isUpcoming ? 'upcoming' : 'ended');
            $statusLabel = $isOngoing ? 'Sedang Berlangsung' : ($isUpcoming ? 'Akan Datang' : 'Telah Berakhir');

            // Format items
            $items = $fs->items->map(function ($item) {
                $prod = $item->productAccurate;
                $imageUrl = 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=300&q=80';
                if ($prod?->product && $prod->product->hasMedia('cover')) {
                    $imageUrl = $prod->product->getFirstMediaUrl('cover');
                } elseif ($prod?->productVariants && $prod->productVariants->isNotEmpty()) {
                    $first = $prod->productVariants->first();
                    if ($first && $first->hasMedia('variant_image')) {
                        $imageUrl = $first->getFirstMediaUrl('variant_image');
                    }
                }

                $discountPercent = 0;
                if ($item->original_price && $item->original_price > $item->flash_sale_price) {
                    $discountPercent = (int) round((($item->original_price - $item->flash_sale_price) / $item->original_price) * 100);
                }

                return [
                    'id' => $item->id,
                    'product_accurate_id' => $item->product_accurate_id,
                    'name' => $prod?->name ?? 'Produk',
                    'item_no' => $prod?->item_no ?? '',
                    'thumbnail' => $imageUrl,
                    'flash_sale_price' => (float) $item->flash_sale_price,
                    'formatted_flash_sale_price' => 'Rp ' . number_format($item->flash_sale_price, 0, ',', '.'),
                    'original_price' => $item->original_price ? (float) $item->original_price : null,
                    'formatted_original_price' => $item->original_price ? 'Rp ' . number_format($item->original_price, 0, ',', '.') : null,
                    'discount_percent' => $discountPercent,
                    'quota_stock' => (int) $item->quota_stock,
                    'sold_stock' => (int) ($item->sold_stock ?? 0),
                    'is_active' => (bool) $item->is_active,
                ];
            });

            return [
                'id' => $fs->id,
                'title' => $fs->title,
                'start_time' => $fs->start_time?->format('d M Y, H:i'),
                'end_time' => $fs->end_time?->format('d M Y, H:i'),
                'start_time_iso' => $fs->start_time?->format('Y-m-d\TH:i'),
                'end_time_iso' => $fs->end_time?->format('Y-m-d\TH:i'),
                'is_active' => (bool) $fs->is_active,
                'status' => $statusKey,
                'status_label' => $statusLabel,
                'business_unit_id' => $fs->business_unit_id,
                'business_unit_name' => $fs->businessUnit?->name ?? 'Semua Store',
                'items_count' => $items->count(),
                'items' => $items,
            ];
        });

        // Metrik Ringkasan
        $stats = [
            'total_events' => $flashSales->count(),
            'ongoing_count' => $flashSales->where('status', 'ongoing')->count(),
            'upcoming_count' => $flashSales->where('status', 'upcoming')->count(),
            'total_products' => $flashSales->sum('items_count'),
        ];

        return Inertia::render('FlashSale/Index', [
            'flashSales' => $flashSales,
            'stats' => $stats,
        ]);
    }

    /**
     * Buat atau perbarui sesi event Flash Sale
     */
    public function storeEvent(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id' => 'nullable|exists:ecommerce_flash_sales,id',
            'title' => 'required|string|max:255',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'business_unit_id' => 'nullable|exists:business_units,id',
            'is_active' => 'boolean',
        ]);

        $id = $validated['id'] ?? null;
        unset($validated['id']);

        EcommerceFlashSale::updateOrCreate(['id' => $id], $validated);

        $action = $id ? 'diperbarui' : 'dibuat';
        return redirect()->back()->with('success', "Sesi Flash Sale berhasil {$action}.");
    }

    /**
     * Toggle status aktif sesi Flash Sale
     */
    public function toggleEvent(EcommerceFlashSale $flashSale): JsonResponse
    {
        $flashSale->update(['is_active' => !$flashSale->is_active]);

        $statusStr = $flashSale->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return response()->json([
            'success' => true,
            'is_active' => $flashSale->is_active,
            'message' => "Sesi Flash Sale '{$flashSale->title}' berhasil {$statusStr}.",
        ]);
    }

    /**
     * Hapus sesi Flash Sale beserta produk di dalamnya
     */
    public function destroyEvent(EcommerceFlashSale $flashSale): RedirectResponse
    {
        $title = $flashSale->title;
        $flashSale->items()->delete();
        $flashSale->delete();

        return redirect()->back()->with('success', "Sesi Flash Sale '{$title}' dan seluruh produk promo di dalamnya berhasil dihapus.");
    }

    /**
     * Tambah atau perbarui produk item dalam Flash Sale
     */
    public function storeItem(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'flash_sale_id' => 'required|exists:ecommerce_flash_sales,id',
            'product_accurate_id' => 'required|exists:product_accurates,id',
            'flash_sale_price' => 'required|numeric|min:1',
            'original_price' => 'nullable|numeric|min:0',
            'quota_stock' => 'required|integer|min:1',
        ]);

        EcommerceFlashSaleItem::updateOrCreate(
            [
                'flash_sale_id' => $validated['flash_sale_id'],
                'product_accurate_id' => $validated['product_accurate_id'],
            ],
            [
                'flash_sale_price' => (float) $validated['flash_sale_price'],
                'original_price' => !empty($validated['original_price']) ? (float) $validated['original_price'] : null,
                'quota_stock' => (int) $validated['quota_stock'],
                'is_active' => true,
            ]
        );

        return redirect()->back()->with('success', 'Produk promo Flash Sale berhasil disimpan.');
    }

    /**
     * Hapus produk item dari Flash Sale
     */
    public function destroyItem(EcommerceFlashSaleItem $item): RedirectResponse
    {
        $item->delete();
        return redirect()->back()->with('success', 'Produk promo berhasil dihapus dari sesi Flash Sale.');
    }

    /**
     * API pencarian produk untuk form item Flash Sale
     */
    public function searchProducts(Request $request): JsonResponse
    {
        $query = $request->input('q', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $products = ProductAccurate::where('name', 'like', "%{$query}%")
            ->orWhere('item_no', 'like', "%{$query}%")
            ->with(['product'])
            ->take(8)
            ->get()
            ->map(function ($prod) {
                $imageUrl = 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=200&q=80';
                if ($prod->product && $prod->product->hasMedia('cover')) {
                    $imageUrl = $prod->product->getFirstMediaUrl('cover');
                }

                return [
                    'id' => $prod->id,
                    'name' => $prod->name,
                    'item_no' => $prod->item_no,
                    'base_price' => (float) $prod->base_price,
                    'formatted_price' => 'Rp ' . number_format($prod->base_price, 0, ',', '.'),
                    'thumbnail' => $imageUrl,
                ];
            });

        return response()->json($products);
    }
}

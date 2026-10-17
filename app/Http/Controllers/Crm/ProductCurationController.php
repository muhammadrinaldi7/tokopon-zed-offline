<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\BusinessUnit;
use App\Models\ProductAccurate;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProductCurationController extends Controller
{
    /**
     * Tampilan utama Kurasi Produk Mobile & Upload Foto (Inertia React)
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search', '');
        $selectedBu = $request->input('bu', null);
        $selectedCategory = $request->input('category', '');
        $selectedBrand = $request->input('brand', '');
        $onlyInStock = $request->boolean('in_stock', true);
        $mediaFilter = $request->input('media', 'all'); // all, has_media, no_media

        $onlineWarehouseIds = Warehouse::where('is_online_store', true)->pluck('id')->toArray();

        $query = ProductAccurate::query()
            ->where('base_price', '>', 0)
            ->with([
                'businessUnit:id,name,code',
                'media',
                'product.media',
                'productVariants.media',
                'secondProductVariants.media',
                'warehouseStocks' => function ($q) use ($onlineWarehouseIds) {
                    $q->whereIn('warehouse_id', $onlineWarehouseIds);
                }
            ]);

        // Filter stok ready di gudang online (Stok > 0)
        if ($onlyInStock) {
            if (!empty($onlineWarehouseIds)) {
                $query->whereExists(function ($sub) use ($onlineWarehouseIds) {
                    $sub->select(DB::raw(1))
                        ->from('warehouse_stocks')
                        ->whereColumn('warehouse_stocks.variant_id', 'product_accurates.id')
                        ->whereIn('warehouse_stocks.variant_type', [
                            ProductAccurate::class,
                            'App\\Models\\ProductAccurate',
                            'ProductAccurate'
                        ])
                        ->whereIn('warehouse_stocks.warehouse_id', $onlineWarehouseIds)
                        ->where('warehouse_stocks.stock', '>', 0);
                });
            } else {
                $query->where('stock', '>', 0);
            }
        }

        if (!empty($selectedBu)) {
            $query->where('business_unit_id', $selectedBu);
        } else {
            $query->whereHas('businessUnit', function ($bu) {
                $bu->where('is_active', true)->where('is_visible_mobile', true);
            });
        }

        if (!empty($search)) {
            $s = trim($search);
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('item_no', 'like', "%{$s}%");
            });
        }

        if (!empty($selectedCategory)) {
            $query->where('categoryName', $selectedCategory);
        }

        if (!empty($selectedBrand)) {
            $query->where('brandName', $selectedBrand);
        }

        if ($mediaFilter === 'has_media') {
            $query->where(function ($q) {
                $q->whereHas('media')
                    ->orWhereHas('product.media')
                    ->orWhereHas('productVariants.media')
                    ->orWhereHas('secondProductVariants.media');
            });
        } elseif ($mediaFilter === 'no_media') {
            $query->whereDoesntHave('media')
                ->whereDoesntHave('product.media')
                ->whereDoesntHave('productVariants.media')
                ->whereDoesntHave('secondProductVariants.media');
        }

        $products = $query->paginate(24)->withQueryString()->through(function ($prod) {
            $imageUrl = 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=300&q=80';
            $hasCover = false;

            if ($prod->hasMedia('cover')) {
                $imageUrl = $prod->getFirstMediaUrl('cover');
                $hasCover = true;
            } elseif ($prod->product && $prod->product->hasMedia('cover')) {
                $imageUrl = $prod->product->getFirstMediaUrl('cover');
                $hasCover = true;
            } elseif ($prod->productVariants && $prod->productVariants->isNotEmpty()) {
                $first = $prod->productVariants->first();
                if ($first && $first->hasMedia('variant_image')) {
                    $imageUrl = $first->getFirstMediaUrl('variant_image');
                    $hasCover = true;
                }
            }

            $gallery = [];
            foreach ($prod->getMedia('gallery') as $m) {
                $gallery[] = [
                    'id' => $m->id,
                    'url' => $m->getFullUrl(),
                ];
            }

            $totalStock = $prod->warehouseStocks->sum('stock');

            return [
                'id' => $prod->id,
                'name' => $prod->name,
                'item_no' => $prod->item_no,
                'category' => $prod->categoryName,
                'brand' => $prod->brandName,
                'base_price' => (float) $prod->base_price,
                'formatted_price' => 'Rp ' . number_format($prod->base_price, 0, ',', '.'),
                'stock' => (int) $totalStock,
                'thumbnail' => $imageUrl,
                'has_custom_cover' => $hasCover,
                'gallery' => $gallery,
                'business_unit_name' => $prod->businessUnit?->name,
                'business_unit_id' => $prod->business_unit_id,
            ];
        });

        // Dropdown filter options
        $categories = ProductAccurate::whereNotNull('categoryName')
            ->where('categoryName', '!=', '')
            ->distinct()
            ->orderBy('categoryName')
            ->pluck('categoryName')
            ->values();

        $brands = ProductAccurate::whereNotNull('brandName')
            ->where('brandName', '!=', '')
            ->distinct()
            ->orderBy('brandName')
            ->pluck('brandName')
            ->values();

        return Inertia::render('Products/Index', [
            'products' => $products,
            'categories' => $categories,
            'brands' => $brands,
            'filters' => [
                'search' => $search,
                'bu' => $selectedBu,
                'category' => $selectedCategory,
                'brand' => $selectedBrand,
                'in_stock' => $onlyInStock,
                'media' => $mediaFilter,
            ],
            'onlineWarehouseCount' => count($onlineWarehouseIds),
        ]);
    }

    /**
     * Upload foto cover atau galeri produk
     */
    public function uploadPhotos(Request $request, ProductAccurate $product): RedirectResponse
    {
        $request->validate([
            'coverPhoto' => 'nullable|image|max:10240',
            'galleryPhotos.*' => 'nullable|image|max:10240',
        ]);

        if ($request->hasFile('coverPhoto')) {
            $product->addMedia($request->file('coverPhoto')->getRealPath())
                ->usingFileName($request->file('coverPhoto')->getClientOriginalName())
                ->toMediaCollection('cover');
        }

        if ($request->hasFile('galleryPhotos')) {
            foreach ($request->file('galleryPhotos') as $photo) {
                $product->addMedia($photo->getRealPath())
                    ->usingFileName($photo->getClientOriginalName())
                    ->toMediaCollection('gallery');
            }
        }

        return redirect()->back()->with('success', "Foto produk '{$product->name}' berhasil diunggah.");
    }

    /**
     * Hapus foto cover
     */
    public function removeCoverPhoto(ProductAccurate $product): RedirectResponse
    {
        $product->clearMediaCollection('cover');
        return redirect()->back()->with('success', "Foto sampul produk '{$product->name}' berhasil dihapus.");
    }
}

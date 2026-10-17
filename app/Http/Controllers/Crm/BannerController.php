<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\BusinessUnit;
use App\Models\EcommerceBanner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class BannerController extends Controller
{
    /**
     * Tampilan utama Manajemen Banner Promo Mobile (Inertia React)
     */
    public function index(Request $request): Response
    {
        $banners = EcommerceBanner::with('businessUnit:id,name,code')
            ->orderBy('sort_order', 'asc')
            ->orderByDesc('id')
            ->get()
            ->map(function ($banner) {
                return [
                    'id' => $banner->id,
                    'title' => $banner->title,
                    'image_url' => $banner->image_url,
                    'target_type' => $banner->target_type,
                    'target_value' => $banner->target_value,
                    'business_unit_id' => $banner->business_unit_id,
                    'business_unit_name' => $banner->businessUnit?->name ?? 'Semua Store',
                    'sort_order' => (int) $banner->sort_order,
                    'is_active' => (bool) $banner->is_active,
                    'created_at' => $banner->created_at?->format('d M Y'),
                ];
            });

        // Metrik Ringkasan
        $stats = [
            'total_banners' => $banners->count(),
            'active_banners' => $banners->where('is_active', true)->count(),
            'inactive_banners' => $banners->where('is_active', false)->count(),
        ];

        return Inertia::render('Banners/Index', [
            'banners' => $banners,
            'stats' => $stats,
            'targetTypes' => [
                ['value' => 'none', 'label' => 'Tanpa Aksi (Hanya Visual)'],
                ['value' => 'category', 'label' => 'Buka Kategori Produk'],
                ['value' => 'brand', 'label' => 'Buka Merek Produk'],
                ['value' => 'product', 'label' => 'Buka Detail Produk'],
                ['value' => 'url', 'label' => 'Buka Tautan Luar (URL Web)'],
            ],
        ]);
    }

    /**
     * Simpan banner promo baru
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'imageFile' => 'required|image|max:4096', // Max 4MB
            'target_type' => 'required|in:none,category,brand,product,url',
            'target_value' => 'nullable|string|max:500',
            'business_unit_id' => 'nullable|exists:business_units,id',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $diskName = config('media-library.disk_name', 'public');
        $fileName = 'banner-' . time() . '-' . uniqid() . '.' . $request->file('imageFile')->getClientOriginalExtension();
        $path = $request->file('imageFile')->storeAs('banners', $fileName, $diskName);
        $imageUrl = Storage::disk($diskName)->url($path);

        EcommerceBanner::create([
            'title' => $validated['title'],
            'image_url' => $imageUrl,
            'target_type' => $validated['target_type'],
            'target_value' => $validated['target_value'] ?? null,
            'business_unit_id' => $validated['business_unit_id'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->back()->with('success', 'Banner promo baru berhasil diunggah.');
    }

    /**
     * Perbarui banner promo yang ada
     */
    public function update(Request $request, EcommerceBanner $banner): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'imageFile' => 'nullable|image|max:4096',
            'target_type' => 'required|in:none,category,brand,product,url',
            'target_value' => 'nullable|string|max:500',
            'business_unit_id' => 'nullable|exists:business_units,id',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $imageUrl = $banner->image_url;

        if ($request->hasFile('imageFile')) {
            $diskName = config('media-library.disk_name', 'public');
            $fileName = 'banner-' . time() . '-' . uniqid() . '.' . $request->file('imageFile')->getClientOriginalExtension();
            $path = $request->file('imageFile')->storeAs('banners', $fileName, $diskName);
            $imageUrl = Storage::disk($diskName)->url($path);
        }

        $banner->update([
            'title' => $validated['title'],
            'image_url' => $imageUrl,
            'target_type' => $validated['target_type'],
            'target_value' => $validated['target_value'] ?? null,
            'business_unit_id' => $validated['business_unit_id'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->back()->with('success', "Banner '{$banner->title}' berhasil diperbarui.");
    }

    /**
     * Toggle status aktif banner
     */
    public function toggleActive(EcommerceBanner $banner): JsonResponse
    {
        $banner->update(['is_active' => !$banner->is_active]);

        $statusStr = $banner->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return response()->json([
            'success' => true,
            'is_active' => $banner->is_active,
            'message' => "Banner '{$banner->title}' berhasil {$statusStr}.",
        ]);
    }

    /**
     * Hapus banner
     */
    public function destroy(EcommerceBanner $banner): RedirectResponse
    {
        $title = $banner->title;
        $banner->delete();

        return redirect()->back()->with('success', "Banner '{$title}' berhasil dihapus.");
    }

    /**
     * Atur ulang urutan urut (reorder) banner
     */
    public function reorder(Request $request): JsonResponse
    {
        $orders = $request->input('orders', []); // [[id => 1, sort_order => 0], ...]

        foreach ($orders as $item) {
            EcommerceBanner::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json(['success' => true]);
    }
}

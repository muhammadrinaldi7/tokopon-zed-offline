<?php

namespace App\Livewire\Zoffline\Qc;

use App\Models\DeviceInspection;
use App\Models\OrderItem;
use App\Models\QcTemplate;
use App\Models\Warranty;
use App\Models\WarrantyPolicy;
use App\Models\Brand;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\WithFileUploads;

#[Layout('layouts.z')]
class WarrantyActivation extends Component
{
    use WithFileUploads;

    #[Url(as: 'sn')]
    public $searchQuery = '';
    
    public $foundItem = null;
    public $isInspecting = false;
    public $errorMessage = '';

    // Photos
    public $photo_depan;
    public $photo_belakang;
    public $photo_kiri;
    public $photo_kanan;
    public $photo_kelengkapan;

    // QC State
    public $qc_results = [];
    public $qc_notes = '';
    public $template;
    public $isSaved = false;
    
    // Warranty Result State
    public $generatedWarranties = [];

    public function mount()
    {
        if (!empty($this->searchQuery)) {
            $this->searchItem();
        }
    }

    public function searchItem()
    {
        $this->validate([
            'searchQuery' => 'required|string|min:3'
        ], [
            'searchQuery.required' => 'Serial Number wajib diisi',
            'searchQuery.min' => 'Serial Number minimal 3 karakter'
        ]);

        $this->errorMessage = '';
        $this->foundItem = null;
        $this->isInspecting = false;
        $this->isSaved = false;
        $this->generatedWarranties = [];

        $activeUnitId = Auth::user()->getActiveBusinessUnitId();

        $items = OrderItem::with(['order.user', 'order.businessUnit', 'variant'])
            ->where('serial_number', 'LIKE', '%' . $this->searchQuery . '%')
            ->get();

        $search = strtolower(trim($this->searchQuery));
        $item = $items->first(function ($i) use ($search) {
            $sns = array_map(function($s) {
                return strtolower(trim($s));
            }, explode(',', $i->serial_number));
            return in_array($search, $sns);
        });

        if (!$item) {
            $this->errorMessage = 'Barang dengan Serial Number tersebut tidak ditemukan di sistem.';
            return;
        }

        if ($item->order->business_unit_id != $activeUnitId) {
            $this->errorMessage = 'Barang ini dibeli dari cabang lain. Anda hanya dapat melakukan aktivasi garansi untuk transaksi dari cabang Anda.';
            return;
        }

        // Pastikan pengecekan inspeksi hanya untuk transaksi (OrderItem) ini saja,
        // karena HP bekas mungkin sudah punya riwayat inspeksi buyback sebelumnya.
        $hasInspection = \App\Models\DeviceInspection::where('imei', $this->searchQuery)
            ->where('inspectable_type', get_class($item))
            ->where('inspectable_id', $item->id)
            ->exists();
            
        if ($hasInspection) {
            $this->errorMessage = 'Barang pada transaksi ini sudah pernah diinspeksi (Aktivasi Garansi Selesai).';
            return;
        }

        $this->foundItem = $item;
    }

    public function startInspection()
    {
        if ($this->foundItem) {
            $this->isInspecting = true;
            $this->loadTemplate();
        }
    }

    private function loadTemplate()
    {
        $this->qc_results = [];
        
        $brandId = null;
        $deviceCategory = null;
        $variant = $this->foundItem->variant ?? null;
        
        // Cek brand_name dari ProductAccurate
        if ($variant) {
            $brandName = null;
            if ($variant instanceof \App\Models\ProductAccurate) {
                $brandName = $variant->brandName;
                $deviceCategory = \App\Models\QcTemplate::normalizeDeviceCategory($variant->categoryName);
            } elseif (method_exists($variant, 'accurateData') && $variant->accurateData) {
                $brandName = $variant->accurateData->brandName;
                $deviceCategory = \App\Models\QcTemplate::normalizeDeviceCategory($variant->accurateData->categoryName);
            }

            if ($brandName) {
                $brand = \App\Models\Brand::where('name', 'like', '%' . $brandName . '%')->first();
                $brandId = $brand->id ?? null;
            }
        }
        
        // Fallback: coba product->brand_id jika belum dapat
        if (!$brandId && isset($variant->product->brand_id)) {
            $brandId = $variant->product->brand_id;
        }

        $this->template = QcTemplate::findForBrandAndCategory($brandId, $deviceCategory);

        if ($this->template) {
            $items = $this->template->items;
            if (!is_array($items)) {
                $items = json_decode($items, true) ?? [];
            }

            foreach ($items as $item) {
                $this->qc_results[] = [
                    'name' => $item['name'] ?? 'Unknown',
                    'type' => $item['type'] ?? 'boolean',
                    'value' => ($item['type'] ?? 'boolean') === 'boolean' ? 1 : '',
                    'category' => $item['category'] ?? 'Lainnya'
                ];
            }
        }
    }

    public function submit()
    {
        $this->validate([
            'photo_depan' => 'required|image|max:5120',
            'photo_belakang' => 'required|image|max:5120',
            'photo_kiri' => 'required|image|max:5120',
            'photo_kanan' => 'required|image|max:5120',
            'photo_kelengkapan' => 'required|image|max:5120',
        ]);

        $inspection = new DeviceInspection([
            'imei' => $this->searchQuery,
            'second_product_variant_id' => null, // Not a second hand device
            'qc_template_id' => $this->template?->id,
            'inspectable_type' => get_class($this->foundItem),
            'inspectable_id' => $this->foundItem->id,
            'label' => 'Aktivasi Garansi / Unboxing',
            'checklist_results' => $this->qc_results,
            'verdict' => 'pass', // Unboxing is always pass
            'inspector_notes' => $this->qc_notes,
            'inspected_by' => Auth::id(),
        ]);

        $inspection->calculateCounts();
        $inspection->save();

        // Save Photos
        $photos = [
            'photo_depan' => $this->photo_depan,
            'photo_belakang' => $this->photo_belakang,
            'photo_kiri' => $this->photo_kiri,
            'photo_kanan' => $this->photo_kanan,
            'photo_kelengkapan' => $this->photo_kelengkapan,
        ];

        foreach ($photos as $key => $photo) {
            $inspection->addMedia($photo->getRealPath())
                ->usingName($photo->getClientOriginalName())
                ->usingFileName($photo->getClientOriginalName())
                ->toMediaCollection('qc_photos');
        }

        $this->generateWarranties($inspection);

        $this->isSaved = true;
        $this->isInspecting = false;
        
        $this->dispatch('toast', title: 'Berhasil', message: 'Aktivasi Garansi berhasil disimpan!', type: 'success');
    }

    private function generateWarranties(DeviceInspection $inspection)
    {
        $order = $this->foundItem->order;
        $now = \Carbon\Carbon::now();
        $this->generatedWarranties = [];

        // 1. Calculate Warranties using Service (Returns Collection of WarrantyPolicy)
        $calculator = new \App\Services\WarrantyCalculatorService();
        $policies = $calculator->calculateWarranties($order, $this->foundItem);

        foreach ($policies as $policy) {
            $warranty = \App\Models\Warranty::create([
                'warranty_policy_id' => $policy->id, 
                'order_item_id' => $this->foundItem->id,
                'serial_number' => $this->searchQuery,
                'customer_user_id' => $order->user_id,
                'type' => $policy->coverage_type, // full_cover atau ganti_unit
                'duration_days' => $policy->duration_days,
                'activated_at' => $now,
                'expires_at' => $now->copy()->addDays($policy->duration_days),
                'status' => 'active',
                'claims_used' => 0,
                'device_inspection_id' => $inspection->id,
                'source' => $policy->type === 'addon_warranty' ? 'purchase' : 'activation', // bedakan source
            ]);
            
            $this->generatedWarranties[] = $warranty;
        }
    }

    public function goBack()
    {
        if ($this->isSaved) {
            $this->isSaved = false;
            $this->foundItem = null;
            $this->searchQuery = '';
        } else if ($this->isInspecting) {
            $this->isInspecting = false;
        } else if ($this->foundItem) {
            $this->foundItem = null;
            $this->searchQuery = '';
        } else {
            return $this->redirectRoute('zoffline', navigate: true);
        }
    }

    public function render()
    {
        return view('livewire.zoffline.qc.warranty-activation');
    }
}

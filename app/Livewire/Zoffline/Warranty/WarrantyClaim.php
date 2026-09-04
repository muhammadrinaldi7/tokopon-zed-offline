<?php

namespace App\Livewire\Zoffline\Warranty;

use App\Models\Warranty;
use App\Models\WarrantyClaim as WarrantyClaimModel;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Livewire\WithFileUploads;
use App\Models\DeviceInspection;
use App\Models\QcTemplate;
use Livewire\Attributes\Computed;

#[Layout('layouts.z')]
class WarrantyClaim extends Component
{
    use WithFileUploads;

    public $searchQuery = '';
    public $foundWarranties;
    public $selectedWarrantyId = null;

    public function mount()
    {
        $this->foundWarranties = collect();
    }

    // Form fields
    public $issue_description = '';
    public $customer_name = '';
    public $customer_phone = '';

    public $hasPendingExtensionRequest = false;
    public $isSubmitted = false;

    // Service Center Form fields
    public $showServiceCenterForm = false;
    public $physical_condition = '';
    public $accessories = '';
    public $estimated_cost = null;

    // QC History
    public $showQcModal = false;
    public $qcInspection = null;

    // QC Claim (Receiving) State
    public $isInspecting = false;
    public $photo_depan;
    public $photo_belakang;
    public $photo_kiri;
    public $photo_kanan;
    public $photo_atas;
    public $photo_bawah;
    public $photo_box;
    public $photo_kelengkapan;

    public $qc_results = [];
    public $qc_notes = '';
    public $template;

    public function searchWarranties()
    {
        $this->validate([
            'searchQuery' => 'required|string|min:3'
        ], [
            'searchQuery.required' => 'Serial Number wajib diisi',
            'searchQuery.min' => 'Serial Number minimal 3 karakter'
        ]);

        $warranties = Warranty::with(['policy', 'orderItem.order.user.profile', 'orderItem.variant', 'customer.profile'])
            ->where('serial_number', $this->searchQuery)
            ->where('status', '!=', 'voided')
            ->orderByDesc('id')
            ->get();

        $this->selectedWarrantyId = null;
        $this->isSubmitted = false;

        if ($warranties->isEmpty()) {
            $this->foundWarranties = collect();
            $this->addError('searchQuery', 'Tidak ada garansi atau data perangkat ditemukan untuk Serial Number ini.');
            return;
        }

        // Cek apakah ada garansi aktif
        $activeWarranties = $warranties->filter(function ($w) {
            return $w->status === 'active' && $w->expires_at > Carbon::now();
        });

        if ($activeWarranties->isNotEmpty()) {
            // Jika ada garansi aktif:
            // Ambil order_item_id dari transaksi aktif TERBARU (misal jika unit pernah dijual beberapa kali di masa lalu)
            $latestActiveOrderItemId = $activeWarranties->first()->order_item_id;

            // Ambil seluruh garansi yang terikat pada transaksi terbaru tersebut (misal policy toko + addon asuransi)
            $this->foundWarranties = $warranties->filter(function ($w) use ($latestActiveOrderItemId) {
                return $w->order_item_id === $latestActiveOrderItemId;
            })->values();
        } else {
            // Jika semua sudah expired, ambil garansi dari transaksi paling baru saja
            $latestOrderItemId = $warranties->first()->order_item_id;
            $this->foundWarranties = $warranties->filter(function ($w) use ($latestOrderItemId) {
                return $w->order_item_id === $latestOrderItemId;
            })->values();
        }

        // Auto-select jika hanya ada 1 garansi aktif di transaksi terbaru tersebut
        $activeInLatest = $this->foundWarranties->filter(function ($w) {
            return $w->status === 'active' && $w->expires_at > Carbon::now();
        });

        if ($activeInLatest->count() === 1) {
            $this->selectWarranty($activeInLatest->first()->id);
        } elseif ($activeInLatest->count() === 0) {
            // ALL warranties are expired. Auto-select the first one just to get customer info.
            $this->selectWarranty($this->foundWarranties->first()->id);
        }
    }

    public function selectWarranty($id)
    {
        $this->selectedWarrantyId = $id;

        // Re-query to ensure relations are loaded
        $warranty = Warranty::with(['orderItem.order.user.profile', 'customer.profile'])->find($id);
        if ($warranty) {
            $customer = $warranty->customer ?? $warranty->orderItem?->order?->user;
            if ($customer) {
                $this->customer_name = $customer->name;
                $this->customer_phone = $customer->profile->phone_number ?? '';
            }
        }

        $this->checkPendingRequest();
    }

    #[Computed]
    public function warranty()
    {
        if (!$this->selectedWarrantyId) return null;
        return Warranty::find($this->selectedWarrantyId);
    }

    public function checkPendingRequest()
    {
        $this->hasPendingExtensionRequest = false;
        if ($this->selectedWarrantyId) {
            $pending = \App\Models\ApprovalRequest::where('approvable_type', Warranty::class)
                ->where('approvable_id', $this->selectedWarrantyId)
                ->where('request_type', 'WARRANTY_EXTENSION')
                ->where('status', 'PENDING')
                ->exists();
            $this->hasPendingExtensionRequest = $pending;
        }
    }

    public function requestWarrantyExtension()
    {
        if (!$this->selectedWarrantyId) return;

        $warranty = $this->warranty;
        if (!$warranty) return;

        $user = Auth::user();

        // Check if there is already a pending request
        $existing = \App\Models\ApprovalRequest::where('approvable_type', Warranty::class)
            ->where('approvable_id', $warranty->id)
            ->where('request_type', 'WARRANTY_EXTENSION')
            ->where('status', 'PENDING')
            ->first();

        if ($existing) {
            $this->dispatch('toast', title: 'Info', message: 'Sudah ada pengajuan perpanjangan garansi yang menunggu persetujuan.', type: 'info');
            return;
        }

        $requiredLevel = \App\Models\ApprovalRule::where('module', 'WARRANTY_EXTENSION')->max('level') ?? 1;

        $request = \App\Models\ApprovalRequest::create([
            'requested_by' => $user->id,
            'approvable_type' => Warranty::class,
            'approvable_id' => $warranty->id,
            'request_type' => 'WARRANTY_EXTENSION',
            'reason' => 'Pengajuan toleransi perpanjangan garansi (otomatis).',
            'required_level' => $requiredLevel,
            'current_level' => 0,
            'status' => 'PENDING',
        ]);

        \App\Http\Controllers\ApprovalController::sendTelegramNotification($request);

        $this->checkPendingRequest();
        $this->dispatch('toast', title: 'Berhasil', message: 'Pengajuan perpanjangan garansi telah dikirim ke Manajer.', type: 'success');
    }

    public function openQcHistory()
    {
        if (!$this->selectedWarrantyId) return;

        $warranty = $this->foundWarranties->firstWhere('id', $this->selectedWarrantyId);
        // The Warranty model has a device_inspection_id attribute
        if ($warranty && $warranty->device_inspection_id) {
            $this->qcInspection = \App\Models\DeviceInspection::with(['qcTemplate', 'inspector'])
                ->find($warranty->device_inspection_id);

            if ($this->qcInspection) {
                $this->showQcModal = true;
            } else {
                $this->dispatch('toast', title: 'Info', message: 'Data QC Unboxing tidak ditemukan untuk perangkat ini.', type: 'info');
            }
        } else {
            $this->dispatch('toast', title: 'Info', message: 'Perangkat ini belum pernah melewati proses QC Unboxing.', type: 'info');
        }
    }

    public function startInspection()
    {
        $this->validate([
            'selectedWarrantyId' => 'required|exists:warranties,id',
        ]);

        $this->isInspecting = true;
        $this->loadTemplate();
    }

    private function loadTemplate()
    {
        $this->qc_results = [];
        $brandId = null;
        $deviceCategory = null;
        $variant = $this->warranty->orderItem->variant ?? null;

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

        // Fallback
        if (!$brandId && isset($variant->product->brand_id)) {
            $brandId = $variant->product->brand_id;
        }

        $this->template = \App\Models\QcTemplate::findForBrandAndCategory($brandId, $deviceCategory);

        if ($this->template) {
            $items = $this->template->items;
            if (!is_array($items)) {
                $items = json_decode($items, true) ?? [];
            }

            foreach ($items as $item) {
                $this->qc_results[] = [
                    'name' => $item['name'] ?? 'Unknown',
                    'type' => $item['type'] ?? 'boolean',
                    'value' => ($item['type'] ?? 'boolean') === 'boolean' ? 1 : '', // Default to 1 (Pass)
                    'category' => $item['category'] ?? 'Lainnya'
                ];
            }
        }
    }

    private function saveInspection()
    {
        $warranty = Warranty::find($this->selectedWarrantyId);
        $item = $warranty->orderItem;

        $inspection = new DeviceInspection([
            'imei' => $warranty->serial_number,
            'second_product_variant_id' => null,
            'qc_template_id' => $this->template?->id,
            'inspectable_type' => get_class($item),
            'inspectable_id' => $item->id,
            'label' => 'QC Penerimaan Klaim',
            'checklist_results' => $this->qc_results,
            'verdict' => 'pass', // Default pass, or we could calculate based on failed items
            'inspector_notes' => $this->qc_notes,
            'inspected_by' => Auth::id(),
        ]);

        $inspection->calculateCounts();

        // If there are failed boolean checks, verdict is fail
        $failedCount = collect($this->qc_results)->where('type', 'boolean')->where('value', 0)->count();
        if ($failedCount > 0) {
            $inspection->verdict = 'fail';
        }

        $inspection->save();

        // Save Photos
        $photos = [
            'photo_depan' => $this->photo_depan,
            'photo_belakang' => $this->photo_belakang,
            'photo_kiri' => $this->photo_kiri,
            'photo_kanan' => $this->photo_kanan,
            'photo_atas' => $this->photo_atas,
            'photo_bawah' => $this->photo_bawah,
            'photo_box' => $this->photo_box,
            'photo_kelengkapan' => $this->photo_kelengkapan,
        ];

        foreach ($photos as $key => $photo) {
            if ($photo) {
                $inspection->addMedia($photo->getRealPath())
                    ->usingName($photo->getClientOriginalName())
                    ->usingFileName($photo->getClientOriginalName())
                    ->toMediaCollection('qc_photos');
            }
        }

        return $inspection->id;
    }

    public function submitClaim()
    {
        $this->validate([
            'selectedWarrantyId' => 'required|exists:warranties,id',
            'issue_description' => 'required|string|min:10',
            'customer_name' => 'required|string|max:255',
            'photo_depan' => 'required|image|max:5120',
            'photo_belakang' => 'required|image|max:5120',
            'photo_kiri' => 'required|image|max:5120',
            'photo_kanan' => 'required|image|max:5120',
            'photo_atas' => 'required|image|max:5120',
            'photo_bawah' => 'required|image|max:5120',
            'photo_box' => 'required|image|max:5120',
            'photo_kelengkapan' => 'required|image|max:5120',
        ]);

        $warranty = Warranty::find($this->selectedWarrantyId);

        $isExpired = $warranty->expires_at < Carbon::now() || $warranty->status !== 'active';

        if ($isExpired) {
            // PROSES SEBAGAI TITIP SERVIS BERBAYAR
            $this->showServiceCenterForm = true;
            $this->dispatch('toast', title: 'Info', message: 'Masa garansi telah habis. Silakan lengkapi form Service Center.', type: 'warning');
            return;
        }

        // Cek batas maksimal klaim
        $maxClaims = $warranty->policy->max_claims ?? 1;
        $claimsUsed = $warranty->claims_used ?? 0;

        if ($claimsUsed >= $maxClaims) {
            $this->showServiceCenterForm = true;
            $this->dispatch('toast', title: 'Batas Klaim Terlampaui', message: "Garansi ini sudah diklaim maksimal ({$maxClaims} kali). Silakan gunakan form Service Center berbayar.", type: 'warning');
            return;
        }

        // Cek apakah sudah ada klaim aktif
        $existingClaim = WarrantyClaimModel::where('warranty_id', $warranty->id)
            ->whereIn('status', ['pending', 'approved', 'in_repair'])
            ->first();

        if ($existingClaim) {
            $this->dispatch('toast', title: 'Gagal', message: 'Sudah ada klaim aktif (Nomor: ' . $existingClaim->claim_number . ') untuk perangkat ini.', type: 'error');
            return;
        }

        // Save QC Inspection First
        $inspectionId = $this->saveInspection();

        // Generate claim number for active warranty (timestamp + uniqid to prevent collision)
        $claimNumber = 'CLM-' . date('YmdHis') . '-' . strtoupper(substr(uniqid(), -4));

        $claim = WarrantyClaimModel::create([
            'claim_number' => $claimNumber,
            'warranty_id' => $warranty->id,
            'receiving_inspection_id' => $inspectionId,
            'customer_user_id' => $warranty->customer_user_id,
            'serial_number' => $warranty->serial_number,
            'issue_description' => $this->issue_description,
            'status' => 'pending',
            'claimed_by' => Auth::id(),
            'claimed_at' => Carbon::now(),
        ]);

        $this->isSubmitted = true;
        $this->isInspecting = false;
        $this->dispatch('toast', title: 'Klaim Berhasil', message: 'Klaim garansi berhasil diajukan dengan nomor: ' . $claimNumber, type: 'success');
    }

    public function submitServiceCenter()
    {
        $this->validate([
            'physical_condition' => 'required|string',
            'accessories' => 'nullable|string',
            'estimated_cost' => 'nullable|numeric',
        ]);

        // Cek apakah sudah ada tiket servis aktif
        $existingClaim = WarrantyClaimModel::where('warranty_id', $this->selectedWarrantyId)
            ->whereIn('status', ['out_of_warranty_service'])
            ->whereHas('serviceCenterTicket', function ($query) {
                $query->whereNotIn('status', ['completed', 'cancelled', 'returned']);
            })
            ->first();

        if ($existingClaim) {
            $this->dispatch('toast', title: 'Gagal', message: 'Sudah ada tiket Service Center aktif (Nomor: ' . $existingClaim->claim_number . ') untuk perangkat ini.', type: 'error');
            return;
        }

        // Save QC Inspection First
        $inspectionId = $this->saveInspection();

        $warranty = Warranty::with('orderItem.variant')->find($this->selectedWarrantyId);
        $claimNumber = 'SRV-' . date('YmdHis') . '-' . strtoupper(substr(uniqid(), -4));

        // Create Claim Header
        $claim = WarrantyClaimModel::create([
            'claim_number' => $claimNumber,
            'warranty_id' => $warranty->id,
            'receiving_inspection_id' => $inspectionId,
            'customer_user_id' => $warranty->customer_user_id,
            'serial_number' => $warranty->serial_number,
            'issue_description' => '[OUT OF WARRANTY - SERVICE CENTER] ' . $this->issue_description,
            'status' => 'out_of_warranty_service',
            'claimed_by' => Auth::id(),
            'claimed_at' => Carbon::now(),
        ]);

        // Create Service Center Ticket
        $deviceName = $warranty->orderItem->product_name ?? 'Unknown Device';

        \App\Models\ServiceCenterTicket::create([
            'warranty_claim_id' => $claim->id,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'device_type' => $deviceName,
            'imei_sn' => $warranty->serial_number,
            'physical_condition_on_receipt' => $this->physical_condition,
            'accessories_included' => $this->accessories,
            'reported_issue' => $this->issue_description,
            'estimated_cost' => $this->estimated_cost ?: 0,
            'status' => 'received'
        ]);

        $this->showServiceCenterForm = false;
        $this->isSubmitted = true;
        $this->dispatch('toast', title: 'Tiket Servis Dibuat', message: 'Pendaftaran Service Center berhasil: ' . $claimNumber, type: 'success');
    }

    public function resetForm()
    {
        $this->searchQuery = '';
        $this->foundWarranties = collect();
        $this->selectedWarrantyId = null;
        $this->issue_description = '';
        $this->customer_name = '';
        $this->customer_phone = '';
        $this->isSubmitted = false;
        $this->isInspecting = false;
        $this->showServiceCenterForm = false;
        $this->physical_condition = '';
        $this->accessories = '';
        $this->estimated_cost = null;
        $this->photo_depan = null;
        $this->photo_belakang = null;
        $this->photo_kiri = null;
        $this->photo_kanan = null;
        $this->photo_atas = null;
        $this->photo_bawah = null;
        $this->photo_box = null;
        $this->photo_kelengkapan = null;
        $this->qc_results = [];
        $this->qc_notes = '';
    }

    public function goBack()
    {
        if ($this->isInspecting) {
            $this->isInspecting = false;
            $this->reset(['photo_depan', 'photo_belakang', 'photo_kiri', 'photo_kanan', 'photo_atas', 'photo_bawah', 'photo_box', 'photo_kelengkapan', 'qc_results', 'qc_notes']);
        } else {
            return $this->redirectRoute('zoffline', navigate: true);
        }
    }

    public function render()
    {
        return view('livewire.zoffline.warranty.warranty-claim');
    }
}

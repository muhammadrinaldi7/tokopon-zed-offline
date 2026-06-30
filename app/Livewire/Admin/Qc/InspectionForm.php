<?php

namespace App\Livewire\Admin\Qc;

use App\Models\DeviceInspection;
use App\Models\QcTemplate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class InspectionForm extends Component
{
    use WithFileUploads;

    public $inspectableType;
    public $inspectableId;
    public $secondProductVariantId = null;

    // Form fields
    public $imei = '';
    public $label = 'QC Inbound';
    public $inspectorNotes = '';
    public $verdict = 'pass';
    public $photos = [];

    // State
    public $template;
    public $checklistResults = [];
    public $isSaved = false;
    public $hideVerdict = false;
    public $hideHeader = false;
    public $qc_max_weight_threshold = 3;

    public function mount($inspectableType = null, $inspectableId = null, $secondProductVariantId = null, $label = 'QC Inbound', $hideVerdict = false, $hideHeader = false, $imei = '')
    {
        // Handle route model binding for standalone page
        if ($inspectableType instanceof \App\Models\SecondProductVariant) {
            $this->secondProductVariantId = $inspectableType->id;
            $this->inspectableType = request()->query('type', 'App\Models\Order');
            $this->inspectableId = request()->query('id', null);
            $this->label = request()->query('label', 'QC Serah Terima');
            $this->imei = request()->query('imei', '');
        } else {
            $this->inspectableType = $inspectableType;
            $this->inspectableId = $inspectableId;
            $this->secondProductVariantId = $secondProductVariantId;
            $this->label = $label;
            $this->hideVerdict = $hideVerdict;
            $this->hideHeader = $hideHeader;
            $this->imei = $imei ?: '';
        }

        // Auto-detect variant ID if not provided
        if (!$this->secondProductVariantId) {
            $model = $this->getInspectableModel();
            if ($model) {
                if ($model instanceof \App\Models\ProductSerialNumber) {
                    $variant = $model->variant;
                    if ($variant instanceof \App\Models\SecondProductVariant) {
                        $this->secondProductVariantId = $variant->id;
                    }
                } elseif ($model instanceof \App\Models\SellPhone) {
                    $this->secondProductVariantId = $model->second_product_variant_id ?? null;
                } elseif ($model instanceof \App\Models\TradeIn) {
                    $this->secondProductVariantId = $model->second_product_variant_id ?? null;
                }
            }
        }

        $this->loadTemplate();
    }

    private function getInspectableModel(): ?Model
    {
        if ($this->inspectableType && $this->inspectableId && class_exists($this->inspectableType)) {
            return $this->inspectableType::find($this->inspectableId);
        }
        return null;
    }

    private function loadTemplate()
    {
        $model = $this->getInspectableModel();
        $brandId = null;

        if ($model) {
            if (method_exists($model, 'buybackDevice')) {
                $brandId = $model->buybackDevice?->brand_id;
            } elseif (method_exists($model, 'accurateData') && $model->accurateData) {
                // Untuk SecondProductVariant / ProductVariant
                $brandName = $model->accurateData->brandName;
                if ($brandName) {
                    $brand = \App\Models\Brand::where('name', 'like', '%' . $brandName . '%')->first();
                    $brandId = $brand->id ?? null;
                }
            } elseif ($model instanceof \App\Models\OrderItem) {
                $variant = $model->variant;
                if ($variant) {
                    $brandName = null;
                    if ($variant instanceof \App\Models\ProductAccurate) {
                        $brandName = $variant->brandName;
                    } elseif (method_exists($variant, 'accurateData') && $variant->accurateData) {
                        $brandName = $variant->accurateData->brandName;
                    }
                    if ($brandName) {
                        $brand = \App\Models\Brand::where('name', 'like', '%' . $brandName . '%')->first();
                        $brandId = $brand->id ?? null;
                    }
                    if (!$brandId && isset($variant->product->brand_id)) {
                        $brandId = $variant->product->brand_id;
                    }
                }
            }
        }

        $this->template = QcTemplate::findForBrand($brandId);

        if ($this->template) {
            $this->qc_max_weight_threshold = $this->template->max_weight_threshold ?? 3;
            
            foreach ($this->template->items as $item) {
                $this->checklistResults[] = [
                    'name' => $item['name'],
                    'type' => $item['type'],
                    'value' => $item['type'] === 'boolean' ? false : '', // default values
                    'weight' => $item['weight'] ?? 1,
                    'is_fatal' => $item['is_fatal'] ?? false,
                    'category' => $this->getQcCategory($item['name'])
                ];
            }
        }
    }

    public function getQcCategory($name)
    {
        $map = [
            'LCD' => 'Layar & Bodi',
            'Touch Screen' => 'Layar & Bodi',
            'BackGlass / Housing' => 'Layar & Bodi',
            'Health Battery' => 'Baterai',
            'Power On/Off' => 'Tombol & Fisik',
            'Volume' => 'Tombol & Fisik',
            'Mute Switch (Silent)' => 'Tombol & Fisik',
            'Home Button' => 'Tombol & Fisik',
            'Taptic / Vibrate' => 'Tombol & Fisik',
            'Tombol' => 'Tombol & Fisik',
            'Kamera Belakang' => 'Kamera & Biometrik',
            'Kamera Belakang 1/2/3' => 'Kamera & Biometrik',
            'Kamera Depan' => 'Kamera & Biometrik',
            'Flash Light' => 'Kamera & Biometrik',
            'Touch ID / Face ID' => 'Kamera & Biometrik',
            'Wifi / Bluetooth' => 'Konektivitas',
            'Signal' => 'Konektivitas',
            'Speaker Atas' => 'Audio & Suara',
            'Speaker Bawah' => 'Audio & Suara',
            'Mic' => 'Audio & Suara',
            'Layar' => 'Layar & Bodi',
            'Bodi' => 'Layar & Bodi',
            'Baterai' => 'Baterai',
            'Kamera' => 'Kamera & Biometrik',
        ];
        return $map[$name] ?? 'Fungsi Lainnya';
    }

    public function calculateAutoVerdict()
    {
        $totalWeightDeduction = 0;
        $hasFatalFailure = false;
        
        $this->inspectorNotes = ''; // Reset notes

        foreach ($this->checklistResults as $item) {
            $val = $item['value'];
            
            if ($item['name'] === 'Health Battery') {
                if ($val !== '' && is_numeric($val) && $val < 85) {
                    $weight = $item['weight'] ?? 1;
                    $totalWeightDeduction += $weight;
                    if (!empty($item['is_fatal'])) {
                        $hasFatalFailure = true;
                        $this->inspectorNotes .= "- FATAL: Battery Health (" . $val . "%) terdeteksi di bawah standar.\n";
                    } else {
                        $this->inspectorNotes .= "- Battery Health (" . $val . "%) terdeteksi di bawah standar (Bobot: {$weight}).\n";
                    }
                }
            } elseif ($item['type'] === 'boolean') {
                if ($val === '0' || $val === false || $val === 0) { // Failed (Not OK is falsy here because default is false, wait! In boolean type, value is true if PASS. If it's false, it means NOT OK / Failed.)
                    // Wait, in my UI: value ? 'OK' : 'TIDAK OK'.
                    // If the user doesn't check it, it is false (TIDAK OK). So false means failed.
                    $weight = $item['weight'] ?? 1;
                    $totalWeightDeduction += $weight;
                    
                    if (!empty($item['is_fatal'])) {
                        $hasFatalFailure = true;
                        $this->inspectorNotes .= "- FATAL: " . $item['name'] . " rusak/bermasalah.\n";
                    } else {
                        $this->inspectorNotes .= "- " . $item['name'] . " rusak/bermasalah (Bobot: {$weight}).\n";
                    }
                }
            }
        }

        if ($hasFatalFailure || $totalWeightDeduction > $this->qc_max_weight_threshold) {
            $this->verdict = 'fail';
        } elseif ($totalWeightDeduction > 0) {
            $this->verdict = 'conditional';
        } else {
            $this->verdict = 'pass';
            $this->inspectorNotes = "Semua komponen berfungsi normal.";
        }
    }

    public function getPassedCountProperty()
    {
        $passed = 0;
        foreach ($this->checklistResults as $item) {
            if ($item['type'] === 'boolean') {
                if ($item['value']) $passed++;
            } else {
                if (!empty($item['value'])) $passed++; // counts as recorded
            }
        }
        return $passed;
    }

    public function getTotalItemsProperty()
    {
        return count($this->checklistResults);
    }

    public function saveInspection()
    {
        $this->validate([
            'imei' => 'required|string|max:255',
            'verdict' => 'required|in:pass,fail,conditional',
            'photos.*' => 'image|max:5120', // max 5MB
        ]);

        $inspection = new DeviceInspection([
            'imei' => $this->imei,
            'second_product_variant_id' => $this->secondProductVariantId,
            'qc_template_id' => $this->template?->id,
            'inspectable_type' => $this->inspectableType,
            'inspectable_id' => $this->inspectableId,
            'label' => $this->label,
            'checklist_results' => $this->checklistResults,
            'verdict' => $this->verdict,
            'inspector_notes' => $this->inspectorNotes,
            'inspected_by' => Auth::id(),
        ]);

        $inspection->calculateCounts(); // Hitung counts sebelum save
        $inspection->save();

        // Update IMEI on SellPhone or TradeIn if applicable
        $model = $this->getInspectableModel();
        if ($model instanceof \App\Models\SellPhone || $model instanceof \App\Models\TradeIn) {
            $model->update(['imei' => $this->imei]);
        } elseif ($model instanceof \App\Models\ProductSerialNumber) {
            $newQcStatus = $this->verdict === 'pass' ? 'Passed Inbound' : 'Failed Inbound';
            $model->update(['qc_status' => $newQcStatus]);
        }

        // Save photos
        foreach ($this->photos as $photo) {
            $inspection->addMedia($photo->getRealPath())
                ->usingName($photo->getClientOriginalName())
                ->usingFileName($photo->getClientOriginalName())
                ->toMediaCollection('qc_photos');
        }

        $this->isSaved = true;

        $this->dispatch('qc-inspection-saved', verdict: $this->verdict);
        $this->dispatch('toast', title: 'Berhasil', message: 'Hasil QC berhasil disimpan!', type: 'success');
    }

    public function render()
    {
        return view('livewire.admin.qc.inspection-form')->layout('layouts.app');
    }
}

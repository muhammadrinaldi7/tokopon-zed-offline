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

    public function mount($inspectableType = null, $inspectableId = null, $secondProductVariantId = null, $label = 'QC Inbound')
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

        if ($model && method_exists($model, 'buybackDevice')) {
            $brandId = $model->buybackDevice?->brand_id;
        }

        $this->template = QcTemplate::findForBrand($brandId);

        if ($this->template) {
            foreach ($this->template->items as $item) {
                $this->checklistResults[] = [
                    'name' => $item['name'],
                    'type' => $item['type'],
                    'value' => $item['type'] === 'boolean' ? false : '', // default values
                ];
            }
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

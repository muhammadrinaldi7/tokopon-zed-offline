<?php

namespace App\Livewire\Admin\Settings\ApprovalRule;

use App\Models\ApprovalRule;
use App\Models\BusinessUnit;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class Index extends Component
{
    public $rules = [];
    public $roles = [];
    public $businessUnits = [];
    public $businessUnitId = null; // null = Aturan Global Default
    public $module = 'ORDER_CANCELLATION';

    public $availableModules = [
        'ORDER_CANCELLATION'   => 'Pembatalan Transaksi POS',
        'SELL_PHONE_APPROVAL'  => 'Pembelian Handphone (Buyback)',
        'WARRANTY_REPLACEMENT' => 'Ganti Unit Garansi',
        'WARRANTY_EXTENSION'   => 'Perpanjangan Garansi',
        'CUSTOM_CASHBACK'      => 'Persetujuan Cashback (PC)',
        'purchase_order'       => 'Persetujuan Pembelian (PO)',
    ];

    public function getIsFinancialProperty(): bool
    {
        return in_array($this->module, [
            'ORDER_CANCELLATION',
            'SELL_PHONE_APPROVAL',
            'CUSTOM_CASHBACK',
            'purchase_order',
        ]);
    }

    public function mount()
    {
        $this->roles = Role::whereNotIn('name', ['user', 'customer'])->get();
        $this->businessUnits = BusinessUnit::all();
        $this->loadRules();
    }

    public function updatedModule()
    {
        $this->loadRules();
    }

    public function updatedBusinessUnitId()
    {
        $this->loadRules();
    }

    public function setBusinessUnit($buId)
    {
        $this->businessUnitId = $buId ?: null;
        $this->loadRules();
    }

    public function loadRules()
    {
        $query = ApprovalRule::where('module', $this->module);

        if ($this->businessUnitId) {
            $query->where('business_unit_id', $this->businessUnitId);
        } else {
            $query->whereNull('business_unit_id');
        }

        $this->rules = $query->orderBy('level', 'asc')
            ->get()
            ->toArray();
    }

    public function addLevel()
    {
        $nextLevel = count($this->rules) + 1;
        $this->rules[] = [
            'id'               => null,
            'business_unit_id' => $this->businessUnitId,
            'module'           => $this->module,
            'level'            => $nextLevel,
            'role_id'          => '',
            'min_amount'       => 0,
            'max_amount'       => null,
        ];
    }

    public function removeLevel($index)
    {
        $rule = $this->rules[$index];

        if (isset($rule['id']) && $rule['id']) {
            ApprovalRule::find($rule['id'])?->delete();
        }

        unset($this->rules[$index]);
        $this->rules = array_values($this->rules);

        // Re-adjust levels
        foreach ($this->rules as $idx => &$r) {
            $r['level'] = $idx + 1;
        }
    }

    public function save()
    {
        $this->validate([
            'rules.*.role_id' => 'required',
        ], [
            'rules.*.role_id.required' => 'Role wajib dipilih untuk setiap level.',
        ]);

        foreach ($this->rules as $ruleData) {
            ApprovalRule::updateOrCreate(
                [
                    'business_unit_id' => $this->businessUnitId,
                    'module'           => $this->module,
                    'level'            => $ruleData['level'],
                ],
                [
                    'role_id'    => $ruleData['role_id'],
                    'min_amount' => $ruleData['min_amount'] ?? 0,
                    'max_amount' => !empty($ruleData['max_amount']) ? $ruleData['max_amount'] : null,
                ]
            );
        }

        $this->loadRules();
        $this->dispatch('toast', title: 'Berhasil', message: 'Aturan persetujuan berhasil disimpan.', type: 'success');
    }

    public function render()
    {
        // $layout = request()->routeIs('zoffline.*') ? 'layouts.z' : 'layouts.admin';
        return view('livewire.admin.settings.approval-rule.index', [
            'businessUnitId' => $this->businessUnitId,
            'businessUnits'  => $this->businessUnits,
            'roles'          => $this->roles,
            'rules'          => $this->rules,
            'module'         => $this->module,
        ])->layout('layouts.z', ['title' => 'Pengaturan Aturan Persetujuan']);
    }
}

<?php

namespace App\Livewire\Admin\Settings\ApprovalRule;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\ApprovalRule;
use Spatie\Permission\Models\Role;

class Index extends Component
{
    public $rules = [];
    public $roles = [];
    public $module = 'cancellation';
    public $availableModules = [
        'cancellation' => 'Pembatalan Transaksi POS',
        'special_discount' => 'Diskon Khusus',
        'purchase_order' => 'Persetujuan Pembelian (PO)',
    ];

    public function mount()
    {
        $this->roles = Role::whereNotIn('name', ['user', 'customer'])->get();
        $this->loadRules();
    }

    public function updatedModule()
    {
        $this->loadRules();
    }

    public function loadRules()
    {
        $this->rules = ApprovalRule::where('module', $this->module)
            ->orderBy('level', 'asc')
            ->get()
            ->toArray();
    }

    public function addLevel()
    {
        $nextLevel = count($this->rules) + 1;
        $this->rules[] = [
            'id' => null,
            'module' => $this->module,
            'level' => $nextLevel,
            'role_id' => ''
        ];
    }

    public function removeLevel($index)
    {
        $rule = $this->rules[$index];

        if (isset($rule['id']) && $rule['id']) {
            ApprovalRule::find($rule['id'])->delete();
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
            'rules.*.role_id' => 'required'
        ], [
            'rules.*.role_id.required' => 'Role wajib dipilih untuk setiap level.'
        ]);

        foreach ($this->rules as $ruleData) {
            ApprovalRule::updateOrCreate(
                ['module' => $this->module, 'level' => $ruleData['level']],
                ['role_id' => $ruleData['role_id']]
            );
        }

        $this->loadRules();
        $this->dispatch('toast', title: 'Berhasil', message: 'Aturan persetujuan berhasil disimpan.', type: 'success');
    }

    public function render()
    {
        $layout = request()->routeIs('zoffline.*') ? 'layouts.z' : 'layouts.admin';
        return view('livewire.admin.settings.approval-rule.index')->layout($layout, ['title' => 'Pengaturan Aturan Persetujuan']);
    }
}

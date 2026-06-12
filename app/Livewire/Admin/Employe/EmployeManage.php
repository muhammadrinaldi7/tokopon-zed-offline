<?php

namespace App\Livewire\Admin\Employe;

use App\Models\Employe;
use App\Models\User;
use App\Services\AccurateService;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin', ['title' => 'Kelola Pegawai - TokoPun'])]
class EmployeManage extends Component
{
    use WithPagination;

    public $search = '';
    public $isLoading = false;

    // State untuk Modal Link/Kelola User Login POS
    public $isEditModalOpen = false;
    public $editingEmployee = null;
    public $selectedUserId = null; // Menyimpan ID user yang dikaitkan ke karyawan

    public $syncBusinessUnitId = '';
    public $filterBusinessUnitId = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    /**
     * SINKRONISASI KARYAWAN LANGSUNG DARI ACCURATE VIA API
     */
    public function syncEmployees()
    {
        $this->isLoading = true;

        try {
            $service = app(AccurateService::class);
            
            $sources = [];
            if ($this->syncBusinessUnitId) {
                $bu = \App\Models\BusinessUnit::find($this->syncBusinessUnitId);
                if ($bu) $sources[] = $bu;
            } else {
                $sources = \App\Models\BusinessUnit::where('is_active', true)->get();
            }

            if (empty($sources)) {
                $this->dispatch('admin-alert', type: 'error', message: 'Tidak ada unit usaha yang dipilih atau aktif.');
                $this->isLoading = false;
                return;
            }

            $syncedCount = 0;

            foreach ($sources as $bu) {
                $response = $service->getEmployees($bu->code);

                if (empty($response)) {
                    continue;
                }

                foreach ($response as $emp) {
                    $localBranchId = null;
                    if (!empty($emp['branchId'])) {
                        $branch = \App\Models\Branch::where('branch_id', $emp['branchId'])->first();
                        if ($branch) {
                            $localBranchId = $branch->id;
                        }
                    } else {
                        $localBranchId = 6;
                    }

                    Employe::updateOrCreate(
                        [
                            'accurate_employee_id' => $emp['id'],
                            'business_unit_id'     => $bu->id,
                        ],
                        [
                            'employee_no'  => $emp['number'] ?? null,
                            'name'         => $emp['name'],
                            'email'        => $emp['email'] ?? null,
                            'phone_number' => $emp['mobilePhone'] ?? null,
                            'position'     => $emp['workPositionName'] ?? null,
                            'is_active'    => !($emp['suspended'] ?? false), // suspended true = tidak aktif
                            'branch_id'    => $localBranchId,
                        ]
                    );
                    $syncedCount++;
                }
            }

            $this->dispatch('admin-alert', type: 'success', message: "Berhasil menyelaraskan $syncedCount data karyawan dengan Accurate.");
        } catch (\Exception $e) {
            Log::error('Gagal Sinkronisasi Karyawan: ' . $e->getMessage());
            $this->dispatch('admin-alert', type: 'error', message: 'Gagal sinkronisasi: ' . $e->getMessage());
        }

        $this->isLoading = false;
    }

    /**
     * OPEN MODAL KELOLA AKUN LOGIN POS (LINK TO USERS TABLE)
     */
    public function editUser($employeeId)
    {
        $this->editingEmployee = Employe::findOrFail($employeeId);
        $this->selectedUserId = $this->editingEmployee->user_id;
        $this->isEditModalOpen = true;
    }

    public function closeModal()
    {
        $this->isEditModalOpen = false;
        $this->editingEmployee = null;
        $this->selectedUserId = null;
    }

    /**
     * SIMPAN LINK ACCOUNT LOGIN POS
     */
    public function saveRoles() // Tetap menggunakan nama method 'saveRoles' agar klop dengan wire:submit di template Anda
    {
        if (!$this->editingEmployee) return;

        $this->editingEmployee->update([
            'user_id' => $this->selectedUserId ?: null
        ]);

        $this->closeModal();
        $this->dispatch('admin-alert', type: 'success', message: 'Kaitan akun login POS karyawan berhasil diperbarui.');
    }

    public function render()
    {
        $query = Employe::with(['user', 'businessUnit'])->orderBy('name', 'asc');

        if ($this->filterBusinessUnitId) {
            $query->where('business_unit_id', $this->filterBusinessUnitId);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('employee_no', 'like', '%' . $this->search . '%')
                    ->orWhere('position', 'like', '%' . $this->search . '%');
            });
        }
        return view('livewire.admin.employe.employe-manage', [
            'employeesList' => $query->paginate(10),
            'businessUnits' => \App\Models\BusinessUnit::where('is_active', true)->get(),
            // Mengambil daftar user lokal yang belum dikaitkan ke karyawan manapun, atau user yang sedang dikaitkan saat ini
            'availableUsers' => User::where('id', $this->selectedUserId)
                ->orderBy('name')
                ->get()
        ]);
    }
}

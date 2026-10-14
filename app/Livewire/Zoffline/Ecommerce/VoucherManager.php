<?php

namespace App\Livewire\Zoffline\Ecommerce;

use App\Models\BusinessUnit;
use App\Models\EcommerceCoupon;
use App\Models\EcommerceCouponRedemption;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.z')]
class VoucherManager extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterType = '';
    public string $filterStatus = 'all';

    // Modal Form State
    public bool $showModal = false;
    public ?int $couponId = null;
    public string $code = '';
    public string $title = '';
    public ?string $description = null;
    public string $type = EcommerceCoupon::TYPE_PRODUCT_FIXED;
    public ?float $discount_value = null;
    public ?float $max_discount_amount = null;
    public ?float $min_purchase_amount = 0;
    public ?int $usage_limit_total = null;
    public ?int $usage_limit_per_user = 1;
    public ?string $start_date = null;
    public ?string $end_date = null;
    public bool $is_active = true;

    // Riwayat Redemptions Modal State
    public bool $showRedemptionsModal = false;
    public ?EcommerceCoupon $selectedCouponForRedemptions = null;

    protected $paginationTheme = 'tailwind';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterType()
    {
        $this->resetPage();
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->code = 'DISKON' . rand(10, 99);
        $this->type = EcommerceCoupon::TYPE_PRODUCT_FIXED;
        $this->discount_value = 10000;
        $this->min_purchase_amount = 50000;
        $this->usage_limit_per_user = 1;
        $this->is_active = true;
        $this->start_date = now()->format('Y-m-d\TH:i');
        $this->end_date = now()->addDays(30)->format('Y-m-d\TH:i');
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $coupon = EcommerceCoupon::findOrFail($id);
        $this->couponId = $coupon->id;
        $this->code = $coupon->code;
        $this->title = $coupon->title;
        $this->description = $coupon->description;
        $this->type = $coupon->type;
        $this->discount_value = (float) $coupon->discount_value;
        $this->max_discount_amount = $coupon->max_discount_amount !== null ? (float) $coupon->max_discount_amount : null;
        $this->min_purchase_amount = (float) $coupon->min_purchase_amount;
        $this->usage_limit_total = $coupon->usage_limit_total;
        $this->usage_limit_per_user = $coupon->usage_limit_per_user;
        $this->start_date = $coupon->start_date?->format('Y-m-d\TH:i');
        $this->end_date = $coupon->end_date?->format('Y-m-d\TH:i');
        $this->is_active = (bool) $coupon->is_active;

        $this->showModal = true;
    }

    public function save(): void
    {
        $this->code = strtoupper(trim($this->code));

        $rules = [
            'code' => 'required|string|max:50|unique:ecommerce_coupons,code,' . ($this->couponId ?? 'NULL') . ',id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|in:' . implode(',', [
                EcommerceCoupon::TYPE_PRODUCT_PERCENT,
                EcommerceCoupon::TYPE_PRODUCT_FIXED,
                EcommerceCoupon::TYPE_SHIPPING_PERCENT,
                EcommerceCoupon::TYPE_SHIPPING_FIXED,
            ]),
            'discount_value' => 'required|numeric|min:1',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'min_purchase_amount' => 'nullable|numeric|min:0',
            'usage_limit_total' => 'nullable|integer|min:1',
            'usage_limit_per_user' => 'nullable|integer|min:1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'boolean',
        ];

        $this->validate($rules);

        $payload = [
            'code' => $this->code,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'discount_value' => $this->discount_value,
            'max_discount_amount' => $this->max_discount_amount ?: null,
            'min_purchase_amount' => $this->min_purchase_amount ?: 0,
            'usage_limit_total' => $this->usage_limit_total ?: null,
            'usage_limit_per_user' => $this->usage_limit_per_user ?: null,
            'start_date' => $this->start_date ?: null,
            'end_date' => $this->end_date ?: null,
            'is_active' => $this->is_active,
        ];

        if ($this->couponId) {
            $coupon = EcommerceCoupon::findOrFail($this->couponId);
            $coupon->update($payload);
            $message = "Voucher #{$this->code} berhasil diperbarui.";
        } else {
            $payload['used_count'] = 0;
            EcommerceCoupon::create($payload);
            $message = "Voucher baru #{$this->code} berhasil dibuat.";
        }

        $this->showModal = false;
        $this->resetForm();
        $this->dispatch('toast', title: 'Berhasil', message: $message, type: 'success');
    }

    public function toggleActive(int $id): void
    {
        $coupon = EcommerceCoupon::findOrFail($id);
        $coupon->update(['is_active' => !$coupon->is_active]);

        $statusStr = $coupon->is_active ? 'diaktifkan' : 'dinonaktifkan';
        $this->dispatch('toast', title: 'Status Berubah', message: "Voucher {$coupon->code} berhasil {$statusStr}.", type: 'success');
    }

    public function delete(int $id): void
    {
        $coupon = EcommerceCoupon::findOrFail($id);

        if ($coupon->used_count > 0 || $coupon->redemptions()->exists()) {
            $this->dispatch('toast', title: 'Perhatian', message: 'Voucher yang telah memiliki riwayat transaksi tidak dapat dihapus. Anda dapat menonaktifkannya.', type: 'warning');
            return;
        }

        $coupon->delete();
        $this->dispatch('toast', title: 'Dihapus', message: 'Voucher berhasil dihapus.', type: 'success');
    }

    public function viewRedemptions(int $couponId): void
    {
        $this->selectedCouponForRedemptions = EcommerceCoupon::with(['redemptions.user', 'redemptions.order'])
            ->findOrFail($couponId);
        $this->showRedemptionsModal = true;
    }

    public function closeRedemptionsModal(): void
    {
        $this->showRedemptionsModal = false;
        $this->selectedCouponForRedemptions = null;
    }

    public function resetForm(): void
    {
        $this->couponId = null;
        $this->code = '';
        $this->title = '';
        $this->description = null;
        $this->type = EcommerceCoupon::TYPE_PRODUCT_FIXED;
        $this->discount_value = null;
        $this->max_discount_amount = null;
        $this->min_purchase_amount = 0;
        $this->usage_limit_total = null;
        $this->usage_limit_per_user = 1;
        $this->start_date = null;
        $this->end_date = null;
        $this->is_active = true;
        $this->resetValidation();
    }

    public function render()
    {
        $query = EcommerceCoupon::query()
            ->withCount('redemptions');

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('code', 'like', '%' . $this->search . '%')
                    ->orWhere('title', 'like', '%' . $this->search . '%')
                    ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        if (!empty($this->filterType)) {
            $query->where('type', $this->filterType);
        }

        if ($this->filterStatus === 'active') {
            $query->where('is_active', true);
        } elseif ($this->filterStatus === 'inactive') {
            $query->where('is_active', false);
        }

        $coupons = $query->orderByDesc('id')->paginate(12);

        // Metrik Ringkasan
        $stats = [
            'total_active' => EcommerceCoupon::where('is_active', true)->count(),
            'total_redemptions' => EcommerceCouponRedemption::count(),
            'total_savings' => (float) EcommerceCouponRedemption::sum('discount_applied'),
        ];

        return view('livewire.zoffline.ecommerce.voucher-manager', [
            'coupons' => $coupons,
            'stats' => $stats,
        ]);
    }
}

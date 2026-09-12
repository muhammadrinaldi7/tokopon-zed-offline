<?php

namespace App\Livewire\Admin\Promo;

use App\Models\Promo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin', ['title' => 'Manajemen Promo & Voucher'])]
class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $category = ''; // filter

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function toggleStatus(Promo $promo)
    {
        $promo->update(['is_active' => !$promo->is_active]);
        $this->dispatch('toast', title: 'Berhasil', message: 'Status promo diperbarui.', type: 'success');
    }

    public function duplicate(Promo $promo)
    {
        try {
            DB::transaction(function () use ($promo) {
                // Eager load relasi jika belum ter-load
                $promo->loadMissing(['skus', 'bundleSkus', 'branches', 'paymentMethods']);

                $newPromo = Promo::create([
                    'name'                   => Str::limit($promo->name . ' (Salinan)', 255, ''),
                    'business_unit_id'       => $promo->business_unit_id ?? \Illuminate\Support\Facades\Auth::user()->getActiveBusinessUnitId(),
                    'description'            => $promo->description,
                    'code'                   => null, // Dikosongkan agar dapat diatur ulang saat promo digunakan
                    'category'               => $promo->category,
                    'brand_id'               => $promo->category === 'brand' ? $promo->brand_id : null,
                    'accurate_account_no'    => $promo->accurate_account_no,
                    'discount_type'          => $promo->discount_type,
                    'discount_value'         => $promo->discount_value,
                    'max_discount'           => $promo->max_discount,
                    'start_date'             => $promo->start_date,
                    'end_date'               => $promo->end_date,
                    'is_active'              => false, // Langsung non-aktif untuk template
                    'is_multiply'            => $promo->is_multiply,
                    'is_combinable'          => $promo->is_combinable,
                    'quota'                  => $promo->quota,
                    'min_transaction_amount' => $promo->min_transaction_amount,
                    'min_qty'                => $promo->min_qty,
                    'max_qty'                => $promo->max_qty,
                    'apply_to_all_items'     => $promo->apply_to_all_items,
                    'is_bundle'              => $promo->is_bundle,
                    'bundle_discount_type'   => $promo->bundle_discount_type,
                    'bundle_discount_value'  => $promo->bundle_discount_value,
                    'bundle_max_discount'    => $promo->bundle_max_discount,
                    'bundle_max_qty'         => $promo->bundle_max_qty,
                ]);

                // Salin relasi Target SKUs (jika tidak berlaku untuk semua barang)
                if (!$promo->apply_to_all_items && $promo->skus->isNotEmpty()) {
                    $skusData = $promo->skus->map(fn($s) => ['sku' => $s->sku])->toArray();
                    $newPromo->skus()->createMany($skusData);
                }

                // Salin relasi Bundle SKUs (jika promo bundling)
                if ($promo->is_bundle && $promo->bundleSkus->isNotEmpty()) {
                    $bundleSkusData = $promo->bundleSkus->map(fn($b) => [
                        'sku'            => $b->sku,
                        'discount_type'  => $b->discount_type,
                        'discount_value' => $b->discount_value,
                        'max_discount'   => $b->max_discount,
                    ])->toArray();
                    $newPromo->bundleSkus()->createMany($bundleSkusData);
                }

                // Salin relasi cabang (branches pivot)
                if ($promo->branches->isNotEmpty()) {
                    $newPromo->branches()->sync($promo->branches->pluck('id')->toArray());
                }

                // Salin relasi metode pembayaran (payment methods pivot)
                if ($promo->paymentMethods->isNotEmpty()) {
                    $newPromo->paymentMethods()->sync($promo->paymentMethods->pluck('id')->toArray());
                }
            });

            $this->dispatch('toast', title: 'Berhasil', message: 'Promo berhasil disalin sebagai template (status non-aktif).', type: 'success');
        } catch (\Throwable $th) {
            $this->dispatch('toast', title: 'Gagal', message: 'Gagal menduplikasi promo: ' . $th->getMessage(), type: 'error');
        }
    }

    public function delete(Promo $promo)
    {
        // Cegah penghapusan jika sudah dipakai
        if ($promo->orders()->count() > 0) {
            $this->dispatch('toast', title: 'Gagal', message: 'Promo tidak bisa dihapus karena sudah memiliki riwayat pemakaian.', type: 'error');
            return;
        }

        $promo->delete();
        $this->dispatch('toast', title: 'Berhasil', message: 'Promo berhasil dihapus.', type: 'success');
    }

    public function render()
    {
        $buId = \Illuminate\Support\Facades\Auth::user()->getActiveBusinessUnitId();

        $promos = Promo::with('brand')
            ->where(function ($q) use ($buId) {
                $q->where('business_unit_id', $buId)
                  ->orWhereNull('business_unit_id');
            })
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('code', 'like', '%' . $this->search . '%');
            })
            ->when($this->category, function ($query) {
                $query->where('category', $this->category);
            })
            ->latest()
            ->paginate(10);

        return view('livewire.admin.promo.index', [
            'promos' => $promos
        ]);
    }
}

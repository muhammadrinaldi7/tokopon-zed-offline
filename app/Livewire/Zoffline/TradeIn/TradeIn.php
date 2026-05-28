<?php

namespace App\Livewire\Zoffline\TradeIn;

use Livewire\Component;
use App\Models\Brand;
use App\Models\SecondProduct;
use App\Models\SecondProductVariant;
use App\Models\TradeIn as TradeInModel;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;

#[Layout('layouts.z', ['title' => 'Trade-in'])]
class TradeIn extends Component
{
    use WithFileUploads;

    public $targetType = 'second'; // 'new' or 'second'
    public $selectedProductId;
    public $selectedTargetBrand = null;
    public $selectedTargetVariantId = null;
    public $availableTargetVariants = [];

    // Properti HP Lama (Fixed Price)
    public $selected_brand_id;
    public $selected_model_name;
    public $buyback_device_id;

    // For calculation
    public $device_rules = [];
    public $selected_rules = [];
    public $final_price = 0;

    // Fallback notes & UI state
    public $old_phone_additional_note;

    // Temporary properties for UI dropdowns
    public $available_models = [];
    public $available_storages = [];
    public $buyback_device = null;
    public $photos = [];
    public $photo_depan;
    public $photo_belakang;
    public $photo_kiri;
    public $photo_kanan;
    public $photo_kelengkapan;
    public $name;
    public $mobilePhone;
    public $email;
    public $nik;
    public $npwp;
    public $foto_ktp;
    public $account_number;
    public $account_name;
    public $bank_name;

    // FL Customer Search
    public $isNewCustomer = true;
    public $searchCustomer = '';
    public $selectedCustomerId = null;

    #[Computed]
    public function customerResults()
    {
        if (strlen($this->searchCustomer) < 2) return [];

        return User::whereHas('roles', function ($q) {
            $q->where('name', 'user');
        })->where(function ($q) {
            $q->where('name', 'like', '%' . $this->searchCustomer . '%')
                ->orWhere('email', 'like', '%' . $this->searchCustomer . '%')
                ->orWhereHas('profile', function ($q2) {
                    $q2->where('phone_number', 'like', '%' . $this->searchCustomer . '%');
                });
        })->take(5)->get();
    }

    public function selectCustomer($id)
    {
        $this->selectedCustomerId = $id;
        $this->searchCustomer = '';
    }

    public function clearSelectedCustomer()
    {
        $this->selectedCustomerId = null;
    }

    /**
     * Fungsi mount dijalankan sekali saat halaman pertama kali dibuka.
     * Kita masukkan parameter $product (diambil dari URL {product?})
     */
    public function mount($product = null)
    {
        if ($product && $product->exists) {
            $this->selectedProductId = $product->id;
            if (get_class($product) === \App\Models\Product::class) {
                $this->targetType = 'new';
            } else {
                $this->targetType = 'second';
            }
            if ($product->brand) {
                $this->selectedTargetBrand = $product->brand->name;
            }
            $this->updatedSelectedProductId();
        }
    }

    public function updatedTargetType()
    {
        $this->selectedTargetBrand = null;
        $this->selectedProductId = null;
        $this->selectedTargetVariantId = null;
        $this->availableTargetVariants = [];
    }

    public function updatedSelectedBrandId()
    {
        $this->selected_model_name = null;
        $this->buyback_device_id = null;
        $this->available_storages = [];
        $this->buyback_device = null;

        if ($this->selected_brand_id) {
            $this->available_models = \App\Models\BuybackDevice::where('brand_id', $this->selected_brand_id)
                ->where('is_active', true)
                ->select('model_name')
                ->distinct()
                ->pluck('model_name')
                ->toArray();
        } else {
            $this->available_models = [];
        }
    }

    public function updatedSelectedProductId()
    {
        $this->selectedTargetVariantId = null;
        $this->availableTargetVariants = [];

        if ($this->selectedProductId) {
            if ($this->targetType === 'new') {
                $variants = \App\Models\ProductVariant::where('product_id', $this->selectedProductId)
                    ->where('stock', '>', 0)
                    ->get();
            } else {
                $variants = SecondProductVariant::where('second_product_id', $this->selectedProductId)
                    ->where('stock', '>', 0)
                    ->get();
            }

            $this->availableTargetVariants = $variants->groupBy(function ($item) {
                return $item->color . ' - ' . $item->storage;
            })->map(function ($group) {
                return [
                    'id' => $group->first()->id,
                    'label' => $group->first()->color . ' - ' . $group->first()->storage,
                    'price' => $group->first()->price,
                    'stock' => $group->count()
                ];
            })->values()->toArray();
        }
    }

    public function updatedSelectedModelName()
    {
        $this->buyback_device_id = null;
        $this->buyback_device = null;

        if ($this->selected_brand_id && $this->selected_model_name) {
            $this->available_storages = \App\Models\BuybackDevice::where('brand_id', $this->selected_brand_id)
                ->where('model_name', $this->selected_model_name)
                ->where('is_active', true)
                ->get();
        } else {
            $this->available_storages = [];
        }
    }

    public function updatedBuybackDeviceId()
    {
        if ($this->buyback_device_id) {
            $this->buyback_device = \App\Models\BuybackDevice::with('tier')->find($this->buyback_device_id);
            $this->device_rules = $this->buyback_device ? $this->buyback_device->getFlatRules() : [];
            $this->selected_rules = [];

            // Invoke Livewire dependency injection for calculatePrice
            $this->calculatePrice(app(\App\Services\TradeInService::class));
        } else {
            $this->buyback_device = null;
            $this->device_rules = [];
            $this->final_price = 0;
        }
    }

    public function updatedSelectedRules()
    {
        $this->calculatePrice(app(\App\Services\TradeInService::class));
    }

    public function calculatePrice(\App\Services\TradeInService $tradeInService)
    {
        if (!$this->buyback_device) return;

        $this->final_price = $tradeInService->calculatePrice($this->buyback_device, $this->selected_rules);
    }

    protected function rules()
    {
        $rules = [
            'buyback_device_id'         => 'required|exists:buyback_devices,id',
            'selected_rules'            => 'required|array|min:1',
            'photo_depan'               => 'required|image|max:5120',
            'photo_belakang'            => 'required|image|max:5120',
            'photo_kiri'                => 'required|image|max:5120',
            'photo_kanan'               => 'required|image|max:5120',
            'photo_kelengkapan'         => 'required|image|max:5120',
            'old_phone_additional_note' => 'nullable|string|max:1000',
            'selectedProductId' => 'required',
            'selectedTargetVariantId' => 'required',
        ];
        if (Auth::check() && User::findOrFail(Auth::user()->id)->hasRole('fl')) {
            if ($this->isNewCustomer) {
                $rules['name']        = 'required|string|max:255';
                $rules['mobilePhone'] = 'required|string|max:15';
                $rules['email']       = 'required|email|unique:users,email';
                $rules['nik']         = 'required|numeric|digits:16|unique:users,nik';
                $rules['npwp']        = 'nullable|string|max:20';
                $rules['foto_ktp']    = 'required|image|max:2048';
                $rules['account_number'] = 'required|string|max:20';
                $rules['account_name'] = 'required|string|max:20';
                $rules['bank_name'] = 'required|string|max:20';
            } else {
                $rules['selectedCustomerId'] = 'required|exists:users,id';
            }
        }
        return $rules;
    }

    protected $messages = [
        'buyback_device_id.required'    => 'Silakan pilih model dan kapasitas penyimpanan terlebih dahulu.',
        'buyback_device_id.exists'      => 'Perangkat tidak ditemukan.',
        'selected_rules.required'       => 'Silakan pilih kondisi perangkat Anda.',
        'selected_rules.min'            => 'Setidaknya satu kondisi harus dipilih.',
        'photo_depan.required'          => 'Foto tampak depan wajib diunggah.',
        'photo_depan.image'             => 'File foto depan harus berupa gambar.',
        'photo_depan.max'               => 'Ukuran foto depan maksimal 5MB.',
        'photo_belakang.required'       => 'Foto tampak belakang wajib diunggah.',
        'photo_belakang.image'          => 'File foto belakang harus berupa gambar.',
        'photo_belakang.max'            => 'Ukuran foto belakang maksimal 5MB.',
        'photo_kiri.required'           => 'Foto samping kiri wajib diunggah.',
        'photo_kiri.image'              => 'File foto samping kiri harus berupa gambar.',
        'photo_kiri.max'                => 'Ukuran foto samping kiri maksimal 5MB.',
        'photo_kanan.required'          => 'Foto samping kanan wajib diunggah.',
        'photo_kanan.image'             => 'File foto samping kanan harus berupa gambar.',
        'photo_kanan.max'               => 'Ukuran foto samping kanan maksimal 5MB.',
        'photo_kelengkapan.required'    => 'Foto kelengkapan wajib diunggah.',
        'photo_kelengkapan.image'       => 'File foto kelengkapan harus berupa gambar.',
        'photo_kelengkapan.max'         => 'Ukuran foto kelengkapan maksimal 5MB.',
        'old_phone_additional_note.max' => 'Catatan tambahan maksimal 1000 karakter.',
        'selectedProductId.required'    => 'Produk wajib dipilih.',
        'selectedTargetVariantId.required' => 'Varian wajib dipilih.',
        'name.required'       => 'Nama lengkap wajib diisi.',
        'mobilePhone.required' => 'Nomor HP wajib diisi.',
        'email.required'      => 'Email wajib diisi.',
        'email.unique'        => 'Email sudah terdaftar di sistem.',
        'nik.required'        => 'NIK wajib diisi.',
        'nik.digits'          => 'NIK harus tepat 16 digit.',
        'nik.unique'          => 'NIK sudah terdaftar di sistem.',
        'foto_ktp.required'   => 'Foto KTP wajib diunggah oleh FL.',
        'account_number.required' => 'Nomor Rekening wajib diisi.',
        'account_name.required' => 'Nama Pemilik Rekening wajib diisi.',
        'bank_name.required' => 'Nama Bank wajib diisi.',
        'selectedCustomerId.required' => 'Silakan cari dan pilih pelanggan terlebih dahulu.',
    ];
    public function submit(\App\Services\TradeInService $tradeInService)
    {
        if (!Auth::check()) {
            return redirect()->to('/login');
        }

        $isFL = User::findOrFail(Auth::user()->id)->hasRole('fl');

        if (!$isFL) {
            $user = Auth::user();
            $isSellerReady = $user->profile && !empty($user->profile->full_name)
                && !empty($user->profile->phone_number)
                && !empty($user->identity)
                && !empty($user->npwp)
                && !empty($user->getFirstMediaUrl('ktp_photo'))
                && $user->bankAccounts()->where('is_primary', true)->exists();

            if (!$isSellerReady) {
                session()->flash('error', 'Silakan lengkapi Data Pribadi, KTP, NPWP, dan Rekening Bank di menu Profil.');
                return $this->redirect(route('profile'), navigate: true);
            }
        }

        $this->validate();

        try {
            $device = \App\Models\BuybackDevice::with('brand')->find($this->buyback_device_id);

            $minusDesc = $tradeInService->formatMinusDescription(
                $this->device_rules,
                $this->selected_rules,
                $this->old_phone_additional_note
            );

            $tradeInUserId = Auth::id();
            $handledBy = null;
            $status = 'WAITING_FOR_DEVICE';

            if ($isFL) {
                if ($this->isNewCustomer) {
                    $newUser = $tradeInService->registerOfflineCustomer([
                        'name' => $this->name,
                        'email' => $this->email,
                        'mobilePhone' => $this->mobilePhone,
                        'nik' => $this->nik,
                        'npwp' => $this->npwp,
                        'foto_ktp' => $this->foto_ktp,
                        'bank_name' => $this->bank_name,
                        'account_number' => $this->account_number,
                        'account_name' => $this->account_name,
                    ]);

                    $tradeInUserId = $newUser->id;
                } else {
                    $tradeInUserId = $this->selectedCustomerId;
                }

                $handledBy = Auth::id();
                $status = 'INSPECTING';
            } else {
            }

            $targetProductType = $this->targetType === 'new' ? \App\Models\Product::class : \App\Models\SecondProduct::class;
            $productVariantType = $this->targetType === 'new' ? \App\Models\ProductVariant::class : \App\Models\SecondProductVariant::class;

            $tradeInService->createTradeInRequest([
                'user_id' => $tradeInUserId,
                'target_product_type' => $targetProductType,
                'target_product_id' => $this->selectedProductId,
                'product_variant_type' => $productVariantType,
                'product_variant_id' => $this->selectedTargetVariantId,
                'buyback_device_id' => $device->id,
                'old_phone_brand'   => $device->brand->name,
                'old_phone_model'   => $device->model_name,
                'old_phone_ram'     => $device->ram,
                'old_phone_storage' => $device->storage,
                'old_phone_minus_desc' => $minusDesc,
                'appraised_value' => $this->final_price,
                'status' => $status,
                'handled_by' => $handledBy,
            ], [
                'photo_depan' => $this->photo_depan,
                'photo_belakang' => $this->photo_belakang,
                'photo_kiri' => $this->photo_kiri,
                'photo_kanan' => $this->photo_kanan,
                'photo_kelengkapan' => $this->photo_kelengkapan,
            ]);

            session()->flash('message', 'Pengajuan berhasil dikirim!');
            return redirect()->to('/trade-in-history');
        } catch (\Throwable $e) {
            session()->flash('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
            \Illuminate\Support\Facades\Log::error($e->getMessage());
        }
    }

    public function updatedSelectedTargetBrand()
    {
        $this->selectedProductId = null;
        $this->selectedTargetVariantId = null;
        $this->availableTargetVariants = [];
    }
    public function render()
    {
        if ($this->targetType === 'new') {
            $targetProducts = \App\Models\Product::where('is_active', true);
            $brandIds = \App\Models\Product::where('is_active', true)->select('brand_id')->distinct();
        } else {
            $targetProducts = SecondProduct::where('is_active', true);
            $brandIds = SecondProduct::where('is_active', true)->select('brand_id')->distinct();
        }

        if ($this->selectedTargetBrand) {
            $targetProducts->whereHas('brand', function ($q) {
                $q->where('name', $this->selectedTargetBrand);
            });
        } elseif ($this->selectedProductId) {
            $targetProducts->where('id', $this->selectedProductId);
        } else {
            $targetProducts->whereRaw('1 = 0');
        }
        return view('livewire.zoffline.trade-in.trade-in', [
            'products' => $targetProducts->get(),
            'brands' => \App\Models\Brand::whereIn('id', $brandIds)->orderBy('name')->get(),
        ]);
    }
}

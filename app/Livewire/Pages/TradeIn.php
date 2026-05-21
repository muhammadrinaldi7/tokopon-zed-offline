<?php

namespace App\Livewire\Pages;

use App\Models\Brand;
use App\Models\Product;
use App\Models\TradeIn as TradeInModel;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class TradeIn extends Component
{
    use WithFileUploads;

    public $selectedProductId;
    public $selectedTargetBrand = null;

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
    /**
     * Fungsi mount dijalankan sekali saat halaman pertama kali dibuka.
     * Kita masukkan parameter $product (diambil dari URL {product?})
     */
    public function mount(Product $product = null)
    {
        if ($product && $product->exists) {
            $this->selectedProductId = $product->id;

            // Tambahkan baris ini untuk otomatis mengisi brand incaran
            // Kita ambil nama brand dari relasi product->brand
            if ($product->brand) {
                $this->selectedTargetBrand = $product->brand->name;
            }
        }
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
            $this->calculatePrice();
        } else {
            $this->buyback_device = null;
            $this->device_rules = [];
            $this->final_price = 0;
        }
    }

    public function updatedSelectedRules()
    {
        $this->calculatePrice();
    }

    public function calculatePrice()
    {
        if (!$this->buyback_device) return;

        $price = $this->buyback_device->base_price;

        $rulesByKey = collect($this->device_rules)->keyBy('key');

        foreach ($this->selected_rules as $ruleKey => $isChecked) {
            if ($isChecked) {
                $rule = $rulesByKey->get($ruleKey);
                if ($rule) {
                    $type = $rule['type'];
                    $val = $rule['value'];

                    if ($type === 'fixed') {
                        $price -= $val;
                    } elseif ($type === 'percentage') {
                        $price -= ($this->buyback_device->base_price * ($val / 100));
                    }
                }
            }
        }

        $this->final_price = max(0, $price); // Ensure price doesn't go below 0
    }

    protected function rules()
    {
        $rules = [
            'buyback_device_id'         => 'required|exists:buyback_devices,id',
            'selected_rules'            => 'required|array|min:1',
            // Aturan Baru: Semua slot wajib berupa gambar dan maksimal 5MB (5120 KB)
            'photo_depan'               => 'required|image|max:5120',
            'photo_belakang'            => 'required|image|max:5120',
            'photo_kiri'                => 'required|image|max:5120',
            'photo_kanan'               => 'required|image|max:5120',
            'photo_kelengkapan'         => 'required|image|max:5120',

            'old_phone_additional_note' => 'nullable|string|max:1000',
            'selectedProductId' => 'required',
            // 'old_phone_battery_health'  => 'required_if:buyback_device->brand->name,Apple,APPLE|nullable|numeric|min:1|max:100',
            // Jika kamu masih memakai BH atau RAM secara manual, tambahkan di sini. 
            // Tapi jika sudah include di selected_rules, ini sudah cukup.
        ];
        // JIKA USER ADALAH FL, TAMBAHKAN VALIDASI REGISTRASI CUSTOMER OFFLINE
        if (Auth::check() && User::findOrFail(Auth::user()->id)->hasRole('fl')) {
            $rules['name']        = 'required|string|max:255';
            $rules['mobilePhone'] = 'required|string|max:15';
            $rules['email']       = 'required|email|unique:users,email';
            $rules['nik']         = 'required|numeric|digits:16|unique:users,nik'; // Sesuaikan field NIK di table user Anda
            $rules['npwp']        = 'nullable|string|max:20';
            $rules['foto_ktp']    = 'required|image|max:2048'; // Max 2MB
            $rules['account_number'] = 'required|string|max:20';
            $rules['account_name'] = 'required|string|max:20';
            $rules['bank_name'] = 'required|string|max:20';
        }
        return $rules;
    }

    protected $messages = [
        'buyback_device_id.required'    => 'Silakan pilih model dan kapasitas penyimpanan terlebih dahulu.',
        'buyback_device_id.exists'      => 'Perangkat tidak ditemukan.',
        'selected_rules.required'       => 'Silakan pilih kondisi perangkat Anda.',
        'selected_rules.min'            => 'Setidaknya satu kondisi harus dipilih.',
        // Pesan Error Baru Per Slot
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

        // Tambahkan baris ini di dalam array $messages Anda yang sudah ada
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
    ];
    public function submit()
    {
        // Cek Autentikasi
        if (!Auth::check()) {
            return redirect()->to('/login');
        }

        // Cek kelengkapan Profil Penjual
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

        // Validasi
        $this->validate();

        try {
            // --- PROSES DATA ---
            $device = \App\Models\BuybackDevice::with('brand')->find($this->buyback_device_id);

            $rulesByKey = collect($this->device_rules)->keyBy('key');

            // Array baru untuk menampung data yang sudah dikelompokkan per kategori
            $groupedSelections = [];

            foreach ($this->selected_rules as $key => $value) {
                $ruleId = null;

                // Logika pembacaan nilai dari checkbox (boolean) atau radio (string)
                if (is_bool($value) && $value) {
                    $ruleId = $key;
                } elseif (is_string($value) && !empty($value)) {
                    $ruleId = $value;
                }

                if ($ruleId) {
                    $rule = $rulesByKey->get($ruleId);
                    if ($rule) {
                        $categoryName = $rule['category']; // Ambil nama kategori (misal: "Kondisi Fisik", "Kelengkapan")

                        // Masukkan nama kondisi ke dalam kelompok kategorinya
                        $groupedSelections[$categoryName][] = $rule['name'];
                    }
                }
            }

            // Merakit array kelompok menjadi string kalimat yang rapi
            $formattedConditions = [];
            foreach ($groupedSelections as $category => $items) {
                // Gabungkan item-item dalam satu kategori dengan koma. Contoh: "Lecet Wajar, Layar Retak"
                $joinedItems = implode(', ', $items);

                // Gabungkan dengan nama kategorinya. Contoh: "Kondisi Fisik: Lecet Wajar, Layar Retak"
                $formattedConditions[] = "{$category}: {$joinedItems}";
            }

            // Gabungkan semua kategori yang sudah diformat dengan tanda pemisah " | " atau ", "
            $kondisi = !empty($formattedConditions)
                ? implode(' | ', $formattedConditions)
                : 'Mulus / Normal';

            $catatanText = $this->old_phone_additional_note
                ? ". Catatan Tambahan: {$this->old_phone_additional_note}"
                : "";

            // Hasil akhir: "Kondisi Fisik: Lecet Wajar | Kelengkapan: Fullset. Catatan Tambahan: Casing belakang agak kotor"
            $minusDesc = "{$kondisi}{$catatanText}";

            // 0. Hit API Accurate jika user belum punya ID Vendor
            try {
                app(\App\Services\AccurateService::class)->syncVendor(Auth::user());
            } catch (\Exception $e) {
                // Log error jika gagal hit accurate, tapi biarkan proses berlanjut
                \Illuminate\Support\Facades\Log::error('Failed to sync vendor to Accurate: ' . $e->getMessage());
            }

            // 1. Simpan Model
            $tradeIn = TradeInModel::create([
                'user_id' => Auth::id(),
                'target_product_id' => $this->selectedProductId,
                'buyback_device_id' => $device->id,
                // UBAH BAGIAN INI (Sesuaikan dengan nama kolom di tabel database)
                'old_phone_brand'   => $device->brand->name,
                'old_phone_model'   => $device->model_name,
                'old_phone_ram'     => $device->ram,
                'old_phone_storage' => $device->storage,
                'old_phone_minus_desc' => $minusDesc,
                'appraised_value' => $this->final_price,
                'status' => 'WAITING_FOR_DEVICE',
            ]);

            // 1. Petakan semua properti slot ke dalam array beserta label custom-nya
            $slots = [
                'photo_depan' => 'Tampak Depan',
                'photo_belakang' => 'Tampak Belakang',
                'photo_kiri' => 'Samping Kiri',
                'photo_kanan' => 'Samping Kanan',
                'photo_kelengkapan' => 'Kelengkapan',
            ];

            // 2. Loop tiap slot dan upload jika filenya ada
            foreach ($slots as $propertyName => $label) {
                if ($this->$propertyName) {
                    $photo = $this->$propertyName;

                    $tradeIn->addMedia($photo->getRealPath())
                        ->usingFileName($photo->getClientOriginalName())
                        // Menyimpan info posisi foto ke dalam custom property Spatie
                        ->withCustomProperties([
                            'position' => str_replace('photo_', '', $propertyName), // Hasilnya: 'depan', 'belakang', dll
                            'label' => $label
                        ])
                        ->toMediaCollection('photos');
                }
            }

            session()->flash('message', 'Pengajuan berhasil dikirim!');

            // Cek apakah route ini benar ada di web.php kamu?
            return redirect()->to('/trade-in-history');
        } catch (\Throwable $e) { // Throwable akan menangkap Exception DAN Error fatal
            session()->flash('error', 'Terjadi kesalahan sistem');
            \Illuminate\Support\Facades\Log::error($e->getMessage());
        }
    }

    public function updatedSelectedTargetBrand()
    {
        // Reset pilihan produk saat user mengganti brand
        $this->selectedProductId = null;
    }
    public function render()
    {
        $targetProducts = Product::query();

        if ($this->selectedTargetBrand) {
            $targetProducts->whereHas('brand', function ($q) {
                $q->where('name', $this->selectedTargetBrand);
            });
        } elseif ($this->selectedProductId) {
            // Fallback: Jika ada product ID tapi brand belum terpilih (misal saat inisiasi)
            // Tetap tampilkan setidaknya produk yang dipilih tersebut
            $targetProducts->where('id', $this->selectedProductId);
        } else {
            $targetProducts->whereRaw('1 = 0');
        }

        return view('livewire.pages.trade-in', [
            'products' => $targetProducts->get(),
            'brands' => \App\Models\Brand::whereIn('id', \App\Models\BuybackDevice::where('is_active', true)->select('brand_id')->distinct())->orderBy('name')->get(),
        ]);
    }
}

<?php

namespace App\Livewire\Pages;

use App\Models\Brand;
use App\Models\SellPhone as SellPhoneModel;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\WithFileUploads;

class SellPhone extends Component
{
    use WithFileUploads;

    // KEPERLUAN DATA USER DI FL
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

    // END KEPERLUAN DATA USER DI FL

    public $selected_brand_id;
    public $selected_model_name;
    public $buyback_device_id;

    // For calculation
    public $device_rules = [];
    public $selected_rules = [];
    public $final_price = 0;

    // Fallback notes
    public $old_phone_additional_note;
    public $photos = [];
    public $photo_depan;
    public $photo_belakang;
    public $photo_kiri;
    public $photo_kanan;
    public $photo_kelengkapan;

    // Temporary properties for UI dropdowns
    public $available_models = [];
    public $available_storages = [];
    public $buyback_device = null;

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
        // dd($this->selected_rules);
        $this->calculatePrice();
    }

    public function calculatePrice()
    {
        if (!$this->buyback_device) return;

        $price = $this->buyback_device->base_price;

        // Convert flat rules array to key-based collection for easy lookup
        $rulesByKey = collect($this->device_rules)->keyBy('key');

        foreach ($this->selected_rules as $key => $value) {
            $ruleId = null;
            if (is_bool($value) && $value) {
                // Checkbox checked
                $ruleId = $key;
            } elseif (is_string($value) && !empty($value)) {
                // Radio button selected
                $ruleId = $value;
            }

            if ($ruleId) {
                $rule = $rulesByKey->get($ruleId);

                if ($rule) {
                    $type = $rule['type'];
                    $val = $rule['value'];

                    // Hitung nominal perubahan (fixed atau persentase)
                    $adjustment = ($type == 'fixed')
                        ? $val
                        : ($this->buyback_device->base_price * ($val / 100));

                    // CEK DISINI: Jika key mengandung kata 'kelengkapan', maka ditambah (+)
                    // Selain itu (seperti layar/fisik), maka dikurangi (-)
                    if (str_contains($ruleId, 'kelengkapan')) {
                        $price += $adjustment;
                    } else {
                        $price -= $adjustment;
                    }
                }
            }
        }

        $this->final_price = max(0, $price); // Pastikan harga tidak minus
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
            // 'old_phone_battery_health'  => 'required_if:buyback_device->brand->name,Apple,APPLE|nullable|numeric|min:1|max:100',
            // Jika kamu masih memakai BH atau RAM secara manual, tambahkan di sini. 
            // Tapi jika sudah include di selected_rules, ini sudah cukup.
        ];
        // JIKA USER ADALAH FL, TAMBAHKAN VALIDASI REGISTRASI CUSTOMER OFFLINE
        if (Auth::check() && User::findOrFail(Auth::user()->id)->hasRole('fl')) {
            if ($this->isNewCustomer) {
                $rules['name']        = 'required|string|max:255';
                $rules['mobilePhone'] = 'required|string|max:15';
                $rules['email']       = 'required|email|unique:users,email';
                $rules['nik']         = 'required|numeric|digits:16|unique:users,nik'; // Sesuaikan field NIK di table user Anda
                $rules['npwp']        = 'nullable|string|max:20';
                $rules['foto_ktp']    = 'required|image|max:2048'; // Max 2MB
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
        'selectedCustomerId.required' => 'Silakan cari dan pilih pelanggan terlebih dahulu.',
    ];

    public function submit()
    {
        // Cek Autentikasi Utama
        if (!Auth::check()) {
            return redirect()->to('/login');
        }

        $userIdToSave = Auth::id(); // Default user login (untuk user online)

        // LOGIKA DIVIDASI BERDASARKAN ROLE
        if (User::findOrFail(Auth::user()->id)->hasRole('fl')) {

            // 1. Jalankan Validasi (Otomatis memuat rules tambahan milik FL)
            $this->validate();

            if ($this->isNewCustomer) {
                // 2. Buat User Baru untuk Customer Offline
                $customer = User::create([
                    'name'         => $this->name,
                    // 'mobile_phone' => $this->mobilePhone, // Sesuaikan nama kolom table users Anda
                    'email'        => $this->email,
                    'nik'          => $this->nik,
                    'npwp'         => $this->npwp,
                    'password'     => \Illuminate\Support\Facades\Hash::make($this->nik), // Hash otomatis dari NIK
                ]);
                if ($customer) {
                    $customer->assignRole('user');
                    $customer->profile()->create([
                        'user_id'      => $customer->id,
                        'full_name'    => $this->name,
                        'phone_number' => $this->mobilePhone,
                    ]);

                    $customer->bankAccounts()->create([
                        'account_number' => $this->account_number,
                        'account_name'   => $this->account_name,
                        'bank_name'      => $this->bank_name,
                    ]);
                }
                event(new Registered($customer));
                // 3. Upload Foto KTP Customer Baru menggunakan Spatie Media Library / Storage biasa
                // Jika User model menggunakan Spatie Media Library:
                if ($this->foto_ktp) {
                    $customer->addMedia($this->foto_ktp->getRealPath())
                        ->usingFileName($this->foto_ktp->getClientOriginalName())
                        ->toMediaCollection('ktp_photo'); // Sesuaikan nama collection Anda
                }

                // Alihkan ID user yang akan disimpan di SellPhone ke ID customer baru ini
                $userIdToSave = $customer->id;
                $userForAccurate = $customer;
            } else {
                $customer = User::findOrFail($this->selectedCustomerId);
                $userIdToSave = $customer->id;
                $userForAccurate = $customer;
            }
        } else {
            // LOGIKA LAMA UNTUK USER ONLINE UMUM
            $user = User::findOrFail(Auth::user()->id);

            $isSellerReady = $user->profile && !empty($user->profile->full_name)
                && !empty($user->profile->phone_number)
                && !empty($user->identity)
                && !empty($user->npwp)
                && !empty($user->getFirstMediaUrl('ktp_photo'))
                && $user->bankAccounts()->where('is_primary', true)->exists();

            if (!$isSellerReady) {
                $this->dispatch('show-toast', type: 'error', message: 'Silakan lengkapi Data Pribadi, KTP, NPWP, dan Rekening Bank di menu Profil.');
                return $this->redirect(route('profile'), navigate: true);
            }

            // Jalankan Validasi standar
            $this->validate();
            $userForAccurate = $user;
        }

        // -------------------------------------------------------------
        // PROSES INSERT DATA DEVICE & TRANSMISI KE ACCURATE
        // -------------------------------------------------------------

        $device = \App\Models\BuybackDevice::with('brand')->find($this->buyback_device_id);

        if (!$device) {
            $this->dispatch('show-toast', type: 'error', message: 'Data perangkat tidak valid.');
            return;
        }

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
        // Hit API Accurate dengan data user/customer yang sesuai
        try {
            // dd($userForAccurate);
            app(\App\Services\AccurateService::class)->syncVendor($userForAccurate);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to sync vendor to Accurate: ' . $e->getMessage());
        }

        // Simpan ke Database (Menggunakan $userIdToSave)
        $sellPhone = \App\Models\SellPhone::create([
            'user_id'           => $userIdToSave, // Berubah dinamis (Bisa ID FL atau ID Customer baru)
            'buyback_device_id' => $device->id,
            'phone_brand'       => $device->brand->name,
            'phone_model'       => $device->model_name,
            'phone_ram'         => $device->ram,
            'phone_storage'     => $device->storage,
            'minus_desc'        => $minusDesc,
            'appraised_value'   => $this->final_price,
            'status'            => User::findOrFail(Auth::user()->id)->hasRole('fl') ? 'PAYING' : 'WAITING_FOR_DEVICE', // Jika di toko oleh FL, status bisa langsung RECEIVED atau disesuaikan bisnis proses Anda
            'handled_by'        => User::findOrFail(Auth::user()->id)->hasRole('fl') ? Auth::id() : null,
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

                $sellPhone->addMedia($photo->getRealPath())
                    ->usingFileName($photo->getClientOriginalName())
                    // Menyimpan info posisi foto ke dalam custom property Spatie
                    ->withCustomProperties([
                        'position' => str_replace('photo_', '', $propertyName), // Hasilnya: 'depan', 'belakang', dll
                        'label' => $label
                    ])
                    ->toMediaCollection('photos');
            }
        }

        $this->dispatch('show-toast', type: 'success', message: 'Transaksi berhasil diproses!');

        // Reset semua form input termasuk input data user FL
        $this->reset([
            'name',
            'mobilePhone',
            'email',
            'nik',
            'npwp',
            'foto_ktp',
            'account_number',
            'account_name',
            'bank_name',
            'isNewCustomer',
            'searchCustomer',
            'selectedCustomerId',
            'selected_brand_id',
            'selected_model_name',
            'buyback_device_id',
            'selected_rules',
            'final_price',
            'old_phone_additional_note',
            // Bersihkan state file upload per slot di memory
            'photo_depan',
            'photo_belakang',
            'photo_kiri',
            'photo_kanan',
            'photo_kelengkapan',
            'available_models',
            'available_storages',
            'buyback_device'
        ]);

        return $this->redirect(route('sell-phone-history'), navigate: true);
    }

    #[Layout('layouts.app', ['title' => 'Sell Mobile Phone'])]
    public function render()
    {
        $brands = \App\Models\Brand::whereIn('id', \App\Models\BuybackDevice::where('is_active', true)->select('brand_id')->distinct())->orderBy('name')->get();
        return view('livewire.pages.sell-phone', [
            'brands' => $brands,
        ]);
    }
}

        // public function submit()
        // {
        //     // Cek Autentikasi
        //     if (!Auth::check()) {
        //         return redirect()->to('/login');
        //     }
    
        //     // Cek kelengkapan Profil Penjual
        //     $user = User::findOrFail(Auth::user()->id);
    
        //     $isSellerReady = $user->profile && !empty($user->profile->full_name)
        //         && !empty($user->profile->phone_number)
        //         && !empty($user->identity)
        //         && !empty($user->npwp)
        //         && !empty($user->getFirstMediaUrl('ktp_photo'))
        //         && $user->bankAccounts()->where('is_primary', true)->exists();
    
        //     if (!$isSellerReady) {
        //         $this->dispatch('show-toast', type: 'error', message: 'Silakan lengkapi Data Pribadi, KTP, NPWP, dan Rekening Bank di menu Profil.');
        //         return $this->redirect(route('profile'), navigate: true);
        //     }
    
        //     // Jalankan Validasi
        //     $this->validate();
    
        //     // 1. Ambil data device terbaru dari database
        //     $device = \App\Models\BuybackDevice::with('brand')->find($this->buyback_device_id);
    
        //     if (!$device) {
        //         $this->dispatch('show-toast', type: 'error', message: 'Data perangkat tidak valid.');
        //         return;
        //     }
    
        //     // 2. Identifikasi semua kondisi/minus yang dipilih (Checkbox & Radio)
        //     $checkedRulesNames = [];
        //     $rulesByKey = collect($this->device_rules)->keyBy('key');
    
        //     foreach ($this->selected_rules as $key => $value) {
        //         $ruleId = null;
    
        //         // Logic pendeteksian key (sesuai yang kita bahas tadi)
        //         if (is_bool($value) && $value) {
        //             $ruleId = $key; // Checkbox (Kelengkapan)
        //         } elseif (is_string($value) && !empty($value)) {
        //             $ruleId = $value; // Radio (Layar/Fisik)
        //         }
    
        //         if ($ruleId) {
        //             $rule = $rulesByKey->get($ruleId);
        //             if ($rule) {
        //                 $checkedRulesNames[] = $rule['name'];
        //             }
        //         }
        //     }
    
        //     // 3. Susun Deskripsi Minus
        //     $kondisi = !empty($checkedRulesNames) ? implode(', ', $checkedRulesNames) : 'Mulus / Normal';
        //     $catatanText = $this->old_phone_additional_note ? ". Catatan Tambahan: {$this->old_phone_additional_note}" : "";
        //     $minusDesc = "Kondisi: {$kondisi}{$catatanText}";
    
        //     // 4. Hit API Accurate jika user belum punya ID Vendor
        //     try {
        //         app(\App\Services\AccurateService::class)->syncVendor($user);
        //     } catch (\Exception $e) {
        //         // Log error jika gagal hit accurate, tapi biarkan proses berlanjut
        //         \Illuminate\Support\Facades\Log::error('Failed to sync vendor to Accurate: ' . $e->getMessage());
        //     }
    
        //     // 5. Simpan ke Database
        //     $sellPhone = \App\Models\SellPhone::create([
        //         'user_id'           => Auth::id(),
        //         'buyback_device_id' => $device->id,
        //         'phone_brand'       => $device->brand->name,
        //         'phone_model'       => $device->model_name,
        //         'phone_ram'         => $device->ram,
        //         'phone_storage'     => $device->storage,
        //         'minus_desc'        => $minusDesc,
        //         'appraised_value'   => $this->final_price,
        //         'status'            => 'WAITING_FOR_DEVICE',
        //     ]);
    
        //     // 6. Upload Media (Spatie Media Library)
        //     if (!empty($this->photos)) {
        //         foreach ($this->photos as $photo) {
        //             $sellPhone->addMedia($photo->getRealPath())
        //                 ->usingFileName($photo->getClientOriginalName())
        //                 ->toMediaCollection('photos');
        //         }
        //     }
    
        //     $this->dispatch('show-toast', type: 'success', message: 'Penawaran disetujui! Silakan kirim perangkat Anda.');
    
        //     // 7. Reset form ke keadaan semula
        //     $this->reset([
        //         'selected_brand_id',
        //         'selected_model_name',
        //         'buyback_device_id',
        //         'selected_rules',
        //         'final_price',
        //         'old_phone_additional_note',
        //         'photos',
        //         'available_models',
        //         'available_storages',
        //         'buyback_device'
        //     ]);
    
        //     return $this->redirect(route('sell-phone-history'), navigate: true);
        // }


        
           // $checkedRulesNames = [];
        // $rulesByKey = collect($this->device_rules)->keyBy('key');

        // foreach ($this->selected_rules as $key => $value) {
        //     $ruleId = null;
        //     if (is_bool($value) && $value) {
        //         $ruleId = $key;
        //     } elseif (is_string($value) && !empty($value)) {
        //         $ruleId = $value;
        //     }

        //     if ($ruleId) {
        //         $rule = $rulesByKey->get($ruleId);
        //         if ($rule) {
        //             $checkedRulesNames[] = $rule['name'];
        //         }
        //     }
        // }

        // $kondisi = !empty($checkedRulesNames) ? implode(', ', $checkedRulesNames) : 'Mulus / Normal';
        // $catatanText = $this->old_phone_additional_note ? ". Catatan Tambahan: {$this->old_phone_additional_note}" : "";
        // $minusDesc = "Kondisi: {$kondisi}{$catatanText}";
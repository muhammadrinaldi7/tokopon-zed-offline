<?php

namespace App\Livewire\Zoffline\SellPhone;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\WithFileUploads;

#[Layout('layouts.z', ['title' => 'Sell-Phone'])]
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

    // QC Kelayakan (Step 2 Baru)
    public $imei = '';
    public $qc_template = null;
    public $qc_results = [];
    public $qc_notes = '';
    public $qc_verdict = ''; // pass, conditional, fail
    public $qc_max_weight_threshold = 3;

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

        $this->imei = '';
        $this->qc_template = null;
        $this->qc_results = [];
        $this->qc_notes = '';
        $this->qc_max_weight_threshold = 3;

        if ($this->selected_brand_id) {
            $brand = \App\Models\Brand::find($this->selected_brand_id);
            $isApple = $brand && strtolower($brand->name) === 'apple';

            $this->available_models = \App\Models\BuybackDevice::where('brand_id', $this->selected_brand_id)
                ->where('is_active', true)
                ->select('model_name')
                ->distinct()
                ->pluck('model_name')
                ->toArray();

            // Load QC Template untuk Buyback (berdasarkan brand)
            $this->qc_template = \App\Models\QcTemplate::findForBrand($this->selected_brand_id);
            if ($this->qc_template) {
                $this->qc_max_weight_threshold = $this->qc_template->max_weight_threshold ?? 3;
                
                foreach ($this->qc_template->items as $item) {
                    if ($item['name'] === 'Health Battery' && !$isApple) {
                        continue; // Skip Health Battery for non-Apple brands
                    }

                    $this->qc_results[] = [
                        'name' => $item['name'],
                        'type' => $item['type'],
                        'value' => $item['type'] === 'boolean' ? null : '',
                        'weight' => $item['weight'] ?? 1,
                        'is_fatal' => $item['is_fatal'] ?? false,
                        'category' => $this->getQcCategory($item['name'])
                    ];
                }
            }
        } else {
        }
    }

    public function calculateAutoVerdict()
    {
        $totalWeightDeduction = 0;
        $hasFatalFailure = false;
        $allPass = true;
        
        $this->qc_notes = ''; // Reset notes

        foreach ($this->qc_results as $item) {
            $val = $item['value'];
            
            if ($item['name'] === 'Health Battery') {
                if ($val !== '' && is_numeric($val) && $val < 85) {
                    $allPass = false;
                    $weight = $item['weight'] ?? 1;
                    $totalWeightDeduction += $weight;
                    if (!empty($item['is_fatal'])) {
                        $hasFatalFailure = true;
                        $this->qc_notes .= "- FATAL: Battery Health (" . $val . "%) terdeteksi di bawah standar.\n";
                    } else {
                        $this->qc_notes .= "- Battery Health (" . $val . "%) terdeteksi di bawah standar (Bobot: {$weight}).\n";
                    }
                }
            } elseif ($item['type'] === 'boolean') {
                if ($val === '0' || $val === false || $val === 0) { // Failed
                    $allPass = false;
                    $weight = $item['weight'] ?? 1;
                    $totalWeightDeduction += $weight;
                    
                    if (!empty($item['is_fatal'])) {
                        $hasFatalFailure = true;
                        $this->qc_notes .= "- FATAL: " . $item['name'] . " rusak/bermasalah.\n";
                    } else {
                        $this->qc_notes .= "- " . $item['name'] . " rusak/bermasalah (Bobot: {$weight}).\n";
                    }
                }
            }
        }

        if ($hasFatalFailure || $totalWeightDeduction > $this->qc_max_weight_threshold) {
            $this->qc_verdict = 'fail';
        } elseif ($totalWeightDeduction > 0) {
            $this->qc_verdict = 'conditional';
        } else {
            $this->qc_verdict = 'pass';
            $this->qc_notes = "Semua komponen berfungsi normal.";
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
            'Microphone' => 'Audio & Suara',
            'Port Charging' => 'Port & Sensor',
            'Port Handsfree' => 'Port & Sensor',
            'Sensor Proximity' => 'Port & Sensor',
        ];

        return $map[$name] ?? 'Lainnya';
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
            'imei'                      => 'required|string|max:255',
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
                $rules['nik']         = 'required|numeric|digits:16|unique:users,identity'; // Sesuaikan field NIK di table user Anda
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
        'imei.required'                 => 'IMEI perangkat wajib diisi saat proses QC.',
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
                    'identity'     => $this->nik,
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
            $buId = Auth::user()->getActiveBusinessUnitId();
            $bu = \App\Models\BusinessUnit::find($buId);
            $dbSource = $bu ? $bu->code : 'syihab';

            app(\App\Services\AccurateService::class)->syncVendor($userForAccurate, $dbSource);
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
            'imei'              => $this->imei,
            'minus_desc'        => $minusDesc,
            'appraised_value'   => $this->final_price,
            'status'            => $this->qc_verdict === 'fail' ? 'CANCELLED' : (User::findOrFail(Auth::user()->id)->hasRole('fl') ? 'PAYING' : 'WAITING_FOR_DEVICE'),
            'handled_by'        => User::findOrFail(Auth::user()->id)->hasRole('fl') ? Auth::id() : null,
            'business_unit_id'  => User::findOrFail(Auth::user()->id)->getActiveBusinessUnitId(),
        ]);

        // Simpan Data QC Kelayakan (Device Inspection)
        if ($this->qc_template) {
            $inspection = new \App\Models\DeviceInspection([
                'imei' => $this->imei,
                'qc_template_id' => $this->qc_template->id,
                'inspectable_type' => \App\Models\SellPhone::class,
                'inspectable_id' => $sellPhone->id,
                'label' => 'QC Kelayakan Buyback',
                'checklist_results' => $this->qc_results,
                'verdict' => $this->qc_verdict ?: 'pass',
                'inspector_notes' => $this->qc_notes ?: 'QC Kelayakan dilakukan di depan pelanggan (Step 2).',
                'inspected_by' => Auth::id(),
            ]);
            $inspection->calculateCounts();
            $inspection->save();
        }

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
            'imei',
            'qc_template',
            'qc_results',
            'qc_notes',
            'qc_verdict',
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

        return $this->redirect(route('zoffline.sell-phone-history'), navigate: true);
    }
    public function render()
    {
        $brands = \App\Models\Brand::whereIn('id', \App\Models\BuybackDevice::where('is_active', true)->select('brand_id')->distinct())->orderBy('name')->get();
        return view('livewire.zoffline.sell-phone.sell-phone', [
            'brands' => $brands,
        ]);
    }
}

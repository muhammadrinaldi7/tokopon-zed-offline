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
    public $domisili;

    public $account_number;
    public $account_name;

    public $bank_name;

    // FL Customer Search
    public $isNewCustomer = true;
    public $searchCustomer = '';
    public $selectedCustomerId = null;
    public $needsBankInfo = false;

    // END KEPERLUAN DATA USER DI FL

    public $selected_brand_id;
    public $selected_categoryName;
    public $selected_proyek;
    public $selected_model_name;

    // For calculation
    public $device_rules = [];
    public $selected_rules = [];
    public $final_price = 0;
    public $calculated_price = 0;
    public $is_price_adjusted = false;

    // Fallback notes
    public $old_phone_additional_note;
    public $photos = [];
    public $photo_depan;
    public $photo_belakang;
    public $photo_kiri;
    public $photo_kanan;
    public $photo_atas;
    public $photo_bawah;
    public $photo_box;
    public $photo_kelengkapan;

    // Temporary properties for UI dropdowns
    public $available_categories = [];
    public $available_proyek = [];
    public $available_models = [];
    public $brands = []; // Cache brands agar tidak query ulang di render()
    public $base_price = 0;

    // QC Kelayakan (Step 2 Baru)
    public $imei = '';
    public $qc_template = null;
    public $qc_results = [];
    public $qc_notes = '';
    public $qc_verdict = ''; // pass, conditional, fail
    public $qc_max_weight_threshold = 3;

    public function mount()
    {
        // Cache brands sekali saja saat halaman pertama kali dimuat
        $accurateBrands = \App\Models\ProductAccurate::where('business_unit_id', 2)
            ->whereNotNull('brandName')
            ->where('brandName', '!=', '')
            ->select('brandName')
            ->distinct()
            ->orderBy('brandName')
            ->pluck('brandName');

        $this->brands = $accurateBrands->map(function ($name) {
            return (object) ['id' => $name, 'name' => $name];
        });
    }

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
        $customer = User::with('bankAccounts')->find($id);

        $this->searchCustomer = $customer->name;

        if ($customer->bankAccounts->isEmpty()) {
            $this->needsBankInfo = true;
            $this->dispatch('toast', title: 'Perhatian!', message: 'Pelanggan ini belum memiliki informasi rekening bank. Silakan lengkapi data bank di bawah.', type: 'warning');
        } else {
            $this->needsBankInfo = false;
        }
    }

    public function clearSelectedCustomer()
    {
        $this->selectedCustomerId = null;
    }

    public function updatedSelectedBrandId()
    {
        $this->selected_categoryName = null;
        $this->selected_proyek = null;
        $this->selected_model_name = null;

        $this->available_categories = [];
        $this->available_proyek = [];
        $this->available_models = [];
        $this->base_price = 0;

        $this->imei = '';
        $this->qc_template = null;
        $this->qc_results = [];
        $this->qc_notes = '';
        $this->qc_max_weight_threshold = 3;

        $this->device_rules = [];
        $this->selected_rules = [];
        $this->final_price = 0;

        if ($this->selected_brand_id) {
            $this->available_categories = \App\Models\ProductAccurate::where('business_unit_id', 2)
                ->where('brandName', $this->selected_brand_id)
                ->whereNotNull('categoryName')
                ->where('categoryName', '!=', '')
                ->select('categoryName')
                ->distinct()
                ->orderBy('categoryName')
                ->pluck('categoryName')
                ->toArray();
        }
    }

    public function updatedSelectedCategoryName()
    {
        $this->selected_proyek = null;
        $this->selected_model_name = null;

        $this->available_proyek = [];
        $this->available_models = [];
        $this->base_price = 0;

        if ($this->selected_brand_id && $this->selected_categoryName) {
            $this->available_proyek = \App\Models\ProductAccurate::where('business_unit_id', 2)
                ->where('brandName', $this->selected_brand_id)
                ->where('categoryName', $this->selected_categoryName)
                ->whereNotNull('proyek')
                ->where('proyek', '!=', '')
                ->select('proyek')
                ->distinct()
                ->orderBy('proyek')
                ->pluck('proyek')
                ->toArray();
        }
    }

    public function updatedSelectedProyek()
    {
        $this->selected_model_name = null;

        $this->available_models = [];
        $this->base_price = 0;

        if ($this->selected_brand_id && $this->selected_categoryName && $this->selected_proyek) {
            $this->available_models = \App\Models\ProductAccurate::where('business_unit_id', 2)
                ->where('brandName', $this->selected_brand_id)
                ->where('categoryName', $this->selected_categoryName)
                ->where('proyek', $this->selected_proyek)
                ->whereNotNull('name')
                ->select('name')
                ->distinct()
                ->orderBy('name')
                ->pluck('name')
                ->toArray();
        }
    }

    private function loadQcTemplate()
    {
        $this->qc_results = [];
        $this->qc_notes = '';
        $this->qc_max_weight_threshold = 3;

        $brandId = $this->selected_brand_id; // Ini sekarang string brandName
        $deviceCategory = null;

        if ($this->selected_categoryName) {
            $deviceCategory = \App\Models\QcTemplate::normalizeDeviceCategory($this->selected_categoryName);
        }

        $brand = \App\Models\Brand::whereRaw('LOWER(name) = ?', [strtolower($brandId)])->first();
        $realBrandId = $brand ? $brand->id : null;
        $isApple = $brand && strtolower($brand->name) === 'apple';

        // Load QC Template untuk Buyback (berdasarkan brand asli dan kategori)
        $this->qc_template = \App\Models\QcTemplate::findForBrandAndCategory($realBrandId, $deviceCategory);

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
                    'category' => $item['category'] ?? 'Lainnya'
                ];
            }
        } else {
            // Jika tidak ada template QC sama sekali
            $this->qc_verdict = 'pass';
            $this->qc_notes = 'Tidak ada form QC yang dikonfigurasi untuk perangkat ini.';
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



    public function updatedSelectedModelName()
    {
        $this->base_price = 0;
        $this->device_rules = [];
        $this->selected_rules = [];
        $this->final_price = 0;

        $this->imei = '';
        $this->qc_template = null;
        $this->qc_results = [];
        $this->qc_notes = '';
        $this->qc_max_weight_threshold = 3;

        if ($this->selected_model_name) {
            $productAccurate = \App\Models\ProductAccurate::where('business_unit_id', 2)
                ->where('name', $this->selected_model_name)
                ->first();

            if ($productAccurate) {
                $this->base_price = $productAccurate->buy_price ?? 0;

                // Cari tier menggunakan hierarki BuybackDevice
                $buybackDevice = \App\Models\BuybackDevice::findByProductAccurate($productAccurate);
                $tier = $buybackDevice?->tier;

                if ($tier) {
                    $flat = [];
                    foreach ($tier->getRulesByCategory() as $category => $items) {
                        foreach ($items as $idx => $item) {
                            $flat[] = [
                                'key'         => \Illuminate\Support\Str::slug($category) . '_' . $idx,
                                'category'    => $category,
                                'name'        => $item['name'],
                                'type'        => $item['type'],
                                'value'       => (float) $item['value'],
                                'description' => $item['description'] ?? '',
                            ];
                        }
                    }
                    $this->device_rules = $flat;
                }
            }

            $this->calculatePrice();
            $this->loadQcTemplate();
        }
    }

    public function updatedSelectedRules()
    {
        // dd($this->selected_rules);
        $this->calculatePrice();
    }

    public function resetCalculation()
    {
        $this->is_price_adjusted = false;
        $this->calculatePrice();
    }

    public function calculatePrice()
    {
        if ($this->is_price_adjusted) return;

        $price = $this->base_price;

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
                        : ($this->base_price * ($val / 100));

                    // CEK DISINI: Jika key mengandung kata 'kelengkapan', maka ditambah (+)
                    // Selain itu (seperti layar/fisik), maka dikurangi (-)
                    // if (str_contains($ruleId, 'kelengkapan')) {
                    //     $price += $adjustment;
                    // } else {
                    $price -= $adjustment;
                    // }
                }
            }
        }
        if ($price <= 0) {
            $price = 0;
        }

        $this->calculated_price = $price;
        $this->final_price = $price;
    }

    protected function rules()
    {
        $rules = [
            'selected_brand_id'         => 'required',
            'selected_categoryName'     => 'required',
            'selected_proyek'           => 'required',
            'selected_model_name'       => 'required',
            'imei' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    $exists = \App\Models\ProductSerialNumber::where('serial_number', $value)
                        ->where('status', 'Available')
                        ->exists();
                    if ($exists) {
                        $fail('IMEI ini masih berstatus "Available" di stok kita. Tidak bisa membeli perangkat ini.');
                    }
                },
            ],
            'selected_rules'            => 'required|array|min:1',
            // Aturan Baru: Semua slot wajib berupa gambar dan maksimal 5MB (5120 KB)
            'photo_depan'               => 'required|image|max:5120',
            'photo_belakang'            => 'required|image|max:5120',
            'photo_kiri'                => 'required|image|max:5120',
            'photo_kanan'               => 'required|image|max:5120',
            'photo_atas'                => 'required|image|max:5120',
            'photo_bawah'               => 'required|image|max:5120',
            'photo_box'                 => 'required|image|max:5120',
            'photo_kelengkapan'         => 'required|image|max:5120',

            'old_phone_additional_note' => 'nullable|string|max:1000',
            // 'old_phone_battery_health'  => 'required_if:buyback_device->brand->name,Apple,APPLE|nullable|numeric|min:1|max:100',
            // Jika kamu masih memakai BH atau RAM secara manual, tambahkan di sini. 
            // Tapi jika sudah include di selected_rules, ini sudah cukup.
        ];
        // Validasi data customer (selalu aktif karena FL sudah tidak dicek lagi)
        if (Auth::check()) {
            if ($this->isNewCustomer) {
                $rules['name']        = 'required|string|max:255';
                $rules['mobilePhone'] = 'required|string|max:15';
                $rules['email']       = 'required|email|unique:users,email';
                $rules['domisili']    = 'required|string|max:500';
                $rules['account_number'] = 'required|string|max:20';
                $rules['account_name'] = 'required|string|max:20';
                $rules['bank_name'] = 'required|string|max:20';
            } else {
                $rules['selectedCustomerId'] = 'required|exists:users,id';
                if ($this->needsBankInfo) {
                    $rules['account_number'] = 'required|string|max:20';
                    $rules['account_name']   = 'required|string|max:20';
                    $rules['bank_name']      = 'required|string|max:20';
                }
            }
        }
        return $rules;
    }

    protected $messages = [
        'selected_model_name.required'  => 'Silakan pilih model perangkat terlebih dahulu.',
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

        'photo_atas.required'           => 'Foto tampak atas wajib diunggah.',
        'photo_atas.image'              => 'File foto tampak atas harus berupa gambar.',
        'photo_atas.max'                => 'Ukuran foto tampak atas maksimal 5MB.',

        'photo_bawah.required'          => 'Foto tampak bawah wajib diunggah.',
        'photo_bawah.image'             => 'File foto tampak bawah harus berupa gambar.',
        'photo_bawah.max'               => 'Ukuran foto tampak bawah maksimal 5MB.',

        'photo_box.required'            => 'Foto box wajib diunggah.',
        'photo_box.image'               => 'File foto box harus berupa gambar.',
        'photo_box.max'                 => 'Ukuran foto box maksimal 5MB.',

        'photo_kelengkapan.required'    => 'Foto kelengkapan wajib diunggah.',
        'photo_kelengkapan.image'       => 'File foto kelengkapan harus berupa gambar.',
        'photo_kelengkapan.max'         => 'Ukuran foto kelengkapan maksimal 5MB.',
        'old_phone_additional_note.max' => 'Catatan tambahan maksimal 1000 karakter.',

        // Tambahkan baris ini di dalam array $messages Anda yang sudah ada
        'name.required'       => 'Nama lengkap wajib diisi.',
        'mobilePhone.required' => 'Nomor HP wajib diisi.',
        'email.required'      => 'Email wajib diisi.',
        'email.unique'        => 'Email sudah terdaftar di sistem.',
        'domisili.required'   => 'Domisili wajib diisi.',
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

        $currentUser = Auth::user(); // Cache user sekali, hindari query berulang
        $userIdToSave = $currentUser->id;

        // Jalankan Validasi
        $this->validate();

        if ($this->isNewCustomer) {
            // Buat User Baru untuk Customer
            $customer = User::create([
                'name'         => $this->name,
                'email'        => $this->email,
                'identity'     => null, // NIK
                'npwp'         => null,
                'password'     => \Illuminate\Support\Facades\Hash::make($this->mobilePhone),
            ]);
            if ($customer) {
                $customer->assignRole('user');
                $customer->profile()->create([
                    'user_id'      => $customer->id,
                    'full_name'    => $this->name,
                    'phone_number' => $this->mobilePhone,
                    'domisili'     => $this->domisili,
                ]);

                $customer->bankAccounts()->create([
                    'account_number' => $this->account_number,
                    'account_name'   => $this->account_name,
                    'bank_name'      => $this->bank_name,
                ]);
            }
            event(new Registered($customer));

            $userIdToSave = $customer->id;
            $userForAccurate = $customer;
        } else {
            $customer = User::findOrFail($this->selectedCustomerId);
            $userIdToSave = $customer->id;
            $userForAccurate = $customer;

            if ($this->needsBankInfo) {
                $customer->bankAccounts()->create([
                    'account_number' => $this->account_number,
                    'account_name'   => $this->account_name,
                    'bank_name'      => $this->bank_name,
                ]);
                $this->needsBankInfo = false;
            }
        }

        // -------------------------------------------------------------
        // PROSES INSERT DATA DEVICE & TRANSMISI KE ACCURATE
        // -------------------------------------------------------------

        $productAccurate = \App\Models\ProductAccurate::where('business_unit_id', 2)
            ->where('name', $this->selected_model_name)
            ->first();

        if (!$productAccurate) {
            $this->dispatch('toast', title: 'Gagal', message: 'Data perangkat tidak valid.', type: 'error');
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

        $finalStatus = $this->qc_verdict === 'fail' ? 'CANCELLED' : 'PAYING';
        $requiredLevel = \App\Models\ApprovalRule::where('module', 'SELL_PHONE_APPROVAL')->max('level') ?? 0;
        $needsApproval = false;

        if ($finalStatus === 'PAYING' && $requiredLevel > 0) {
            $finalStatus = 'PENDING_APPROVAL';
            $needsApproval = true;
        }

        // Simpan ke Database
        $sellPhone = \App\Models\SellPhone::create([
            'user_id'           => $userIdToSave,
            'product_accurate_id' => $productAccurate->id,
            'phone_brand'       => $productAccurate->brandName,
            'phone_model'       => $productAccurate->name,
            'phone_ram'         => null,
            'phone_storage'     => null,
            'imei'              => $this->imei,
            'minus_desc'        => $minusDesc,
            'appraised_value'   => $this->final_price,
            'original_appraised_value' => $this->calculated_price,
            'is_price_adjusted' => $this->is_price_adjusted,
            'status'            => $finalStatus,
            'handled_by'        => $currentUser->id,
            'business_unit_id'  => $currentUser->getActiveBusinessUnitId(),
            'branch_id'         => Auth::user()->branch_id,
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
            'photo_atas' => 'Tampak Atas',
            'photo_bawah' => 'Tampak Bawah',
            'photo_box' => 'Box Belakang',
            'photo_kelengkapan' => 'Kelengkapan / Box',
        ];

        // 2. Loop tiap slot dan upload jika filenya ada
        foreach ($slots as $propertyName => $label) {
            if ($this->$propertyName) {
                $photo = $this->$propertyName;

                // Menggunakan addMediaFromString untuk membaca file secara aman melalui Flysystem Livewire
                // sehingga terhindar dari error 'Unable to retrieve file_size'
                $sellPhone->addMediaFromString($photo->get())
                    ->usingFileName($photo->getClientOriginalName())
                    // Menyimpan info posisi foto ke dalam custom property Spatie
                    ->withCustomProperties([
                        'position' => str_replace('photo_', '', $propertyName), // Hasilnya: 'depan', 'belakang', dll
                        'label' => $label
                    ])
                    ->toMediaCollection('photos');
            }
        }

        if ($needsApproval) {
            $qcList = [];
            foreach ($this->qc_results as $qc) {
                if ($qc['type'] === 'boolean') {
                    $status = ($qc['value'] === '1' || $qc['value'] === true || $qc['value'] === 1) ? '✅ Normal' : '❌ Bermasalah';
                } else {
                    $status = $qc['value'] ? $qc['value'] : '-';
                }
                $qcList[] = "- " . $qc['name'] . ': ' . $status;
            }
            $qcListText = implode("\n", $qcList);

            $reasonText = 'Pembelian: ' . $sellPhone->phone_brand . ' ' . $sellPhone->phone_model . " (Rp " . number_format($sellPhone->appraised_value, 0, ',', '.') . ")\n\n" .
                "IMEI: " . $sellPhone->imei . "\n\n" .
                "List QC:\n" . $qcListText . "\n\n" .
                "Minus:\n" . str_replace(" | ", "\n", $sellPhone->minus_desc);

            // 1. Peringatan Edit Harga
            if ($sellPhone->is_price_adjusted) {
                $difference = $sellPhone->appraised_value - $sellPhone->original_appraised_value;
                $diffText = "Rp " . number_format(abs($difference), 0, ',', '.');
                $originalPriceText = "Rp " . number_format($sellPhone->original_appraised_value, 0, ',', '.');

                $reasonText .= "\n\n⚠️ *Peringatan Nego Harga!*\n";
                if ($difference > 0) {
                    $reasonText .= "Harga dinaikkan sebesar *$diffText* dari taksiran sistem ($originalPriceText).";
                } else {
                    $reasonText .= "Harga diturunkan sebesar *$diffText* dari taksiran sistem ($originalPriceText).";
                }
            }

            // 2. Analisa Persediaan Historis
            $historyStats = \App\Models\SellPhone::where('phone_brand', $sellPhone->phone_brand)
                ->where('phone_model', $sellPhone->phone_model)
                ->where('phone_storage', $sellPhone->phone_storage)
                ->whereIn('status', ['COMPLETED', 'PAYING'])
                ->selectRaw('AVG(appraised_value) as average_price, COUNT(id) as total_count')
                ->first();

            if ($historyStats && $historyStats->total_count > 0) {
                $averagePrice = $historyStats->average_price;
                $historyCount = $historyStats->total_count;

                $avgDiff = $sellPhone->appraised_value - $averagePrice;
                $avgDiffText = "Rp " . number_format(abs($avgDiff), 0, ',', '.');
                $avgPriceText = "Rp " . number_format($averagePrice, 0, ',', '.');

                $reasonText .= "\n\n📊 *Analisa Persediaan (Data Historis ZED):*\n";
                $reasonText .= "Rata-rata beli: *$avgPriceText*\n";
                if ($avgDiff > 0) {
                    $reasonText .= "Pengajuan kali ini *LEBIH MAHAL $avgDiffText* dari rata-rata historis.\n";
                } elseif ($avgDiff < 0) {
                    $reasonText .= "Pengajuan kali ini *LEBIH MURAH $avgDiffText* dari rata-rata historis.\n";
                } else {
                    $reasonText .= "Pengajuan kali ini *SAMA* dengan rata-rata historis.\n";
                }
                $reasonText .= "*(Dari total $historyCount transaksi sebelumnya)*";
            } else {
                $reasonText .= "\n\n📊 *Analisa Persediaan (Data Historis ZED):*\n";
                $reasonText .= "Belum ada riwayat transaksi sukses untuk tipe HP ini.";
            }

            $requestApproval = $sellPhone->approvalRequests()->create([
                'request_type' => 'SELL_PHONE_APPROVAL',
                'requested_by' => Auth::id(),
                'reason' => $reasonText,
                'status' => 'PENDING',
                'required_level' => $requiredLevel,
                'current_level' => 0
            ]);

            \App\Http\Controllers\ApprovalController::sendTelegramNotification($requestApproval);
            $this->dispatch('toast', title: 'Menunggu Persetujuan', message: 'Transaksi berhasil disimpan dan sedang menunggu approval Pusat.', type: 'info');
        } else {
            $this->dispatch('toast', title: 'Transaksi berhasil diproses!', message: 'Data berhasil disimpan.', type: 'success');
        }

        // Reset semua form input termasuk input data user FL
        $this->reset([
            'name',
            'mobilePhone',
            'email',
            'domisili',
            'account_number',
            'account_name',
            'bank_name',
            'isNewCustomer',
            'searchCustomer',
            'selectedCustomerId',
            'selected_brand_id',
            'selected_categoryName',
            'selected_proyek',
            'selected_model_name',
            'imei',
            'qc_template',
            'qc_results',
            'qc_notes',
            'qc_verdict',
            'selected_rules',
            'final_price',
            'base_price',
            'old_phone_additional_note',
            // Bersihkan state file upload per slot di memory
            'photo_depan',
            'photo_belakang',
            'photo_kiri',
            'photo_kanan',
            'photo_atas',
            'photo_bawah',
            'photo_box',
            'photo_kelengkapan',
            'available_categories',
            'available_proyek',
            'available_models'
        ]);

        return $this->redirect(route('zoffline.sell-phone-history'), navigate: true);
    }
    public function render()
    {
        return view('livewire.zoffline.sell-phone.sell-phone');
    }
}

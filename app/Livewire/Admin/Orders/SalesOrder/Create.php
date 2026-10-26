<?php

namespace App\Livewire\Admin\Orders\SalesOrder;

use App\Models\BusinessUnit;
use App\Models\BusinessUnitProject;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\SecondProductVariant;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\AccurateService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class Create extends Component
{
    // Header SO
    public $user_id;
    public $searchCustomer = '';
    public $customerSearchResults = [];
    public $selectedCustomerName = null;
    public $selectedCustomerEmail = null;
    public $selectedCustomerPhone = null;

    // Sales
    public $sales_id;
    public $searchSales = '';
    public $salesSearchResults = [];
    public $selectedSalesName = null;
    public $selectedSalesBranch = null;
    public $selectedSalesNo = null;

    // New Customer
    public $showNewCustomerModal = false;
    public $new_customer_name = '';
    public $new_customer_phone = '';
    public $new_customer_email = '';

    public $business_unit_id;
    public $warehouse_id;
    public $order_date;
    public $notes;

    // Items
    public $items = [];

    // Totals
    public $subtotal = 0;
    public $discount_amount = 0;
    public $grand_total = 0;

    // Direct DP Form in Step 3
    public bool $has_dp = false;
    public $dp_mode = 'tunai'; // 'tunai' or 'non-tunai'
    public $dp_bank_group = null; // 'BCA', 'MANDIRI', 'QRIS', etc.
    public $dp_amount = 0;
    public $dp_payment_method_id = null;
    public $dp_payment_method_rate_id = null;
    public $dp_date = null;
    public $dp_notes = '';
    public $dp_contract_number = '';

    // Customer Deposit (Store Credit) Support
    public bool $use_customer_deposit = false;
    public $custom_deposit_amount = 0;
    public $available_customer_deposits = [];
    public $available_customer_deposit_total = 0;

    // Wizard
    public int $wizardStep = 1;

    public function mount()
    {
        $this->order_date = Carbon::now()->format('Y-m-d');
        $this->dp_date = Carbon::now()->format('Y-m-d');
        $this->business_unit_id = Auth::user()->getActiveBusinessUnitId();
        $this->warehouse_id = Auth::user()->warehouse_id;
        $this->setDpMode('tunai');
        // Initial empty row
        $this->addItem();
    }

    public function setDpMode($mode)
    {
        $this->dp_mode = $mode;
        $user = Auth::user();
        $businessUnitId = $this->business_unit_id ?? $user->getActiveBusinessUnitId();
        $warehouseName = $user->warehouse ? trim(strtolower($user->warehouse->name)) : null;

        if ($mode === 'tunai') {
            $this->dp_bank_group = null;
            $cashMethods = \App\Models\PaymentMethod::where('is_active', true)
                ->where('category', 'TUNAI')
                ->where(function ($q) use ($businessUnitId) {
                    $q->where('business_unit_id', $businessUnitId)->orWhereNull('business_unit_id');
                })
                ->get();

            $matched = null;
            if ($warehouseName) {
                $matched = $cashMethods->first(function ($m) use ($warehouseName) {
                    $mLoc = trim(str_replace('tunai', '', strtolower($m->name)));
                    return !empty($mLoc) && str_contains($warehouseName, $mLoc);
                });
            }
            $selected = $matched ?? $cashMethods->first();
            if ($selected) {
                $this->dp_payment_method_id = $selected->id;
                $this->dp_payment_method_rate_id = null;
            }
        } else {
            // Non-tunai: reset pilihan metode & grup bank jika beralih dari tunai agar user memilih grup bank terlebih dahulu
            $current = \App\Models\PaymentMethod::find($this->dp_payment_method_id);
            if (!$current || $current->category === 'TUNAI') {
                $this->dp_bank_group = null;
                $this->dp_payment_method_id = null;
                $this->dp_payment_method_rate_id = null;
            }
        }
    }

    public function selectDpBankGroup($group)
    {
        $this->dp_bank_group = $group;
        $user = Auth::user();
        $businessUnitId = $this->business_unit_id ?? $user->getActiveBusinessUnitId();

        $methods = \App\Models\PaymentMethod::where('is_active', true)
            ->where('category', 'NON-TUNAI')
            ->where(function ($q) use ($businessUnitId) {
                $q->where('business_unit_id', $businessUnitId)->orWhereNull('business_unit_id');
            })
            ->where(function ($q) use ($group) {
                $q->where('bank_name', $group)
                  ->orWhere(function($sub) use ($group) {
                      $sub->whereNull('bank_name')->where('name', $group);
                  });
            })
            ->get();

        if ($methods->count() === 1) {
            $this->selectDpPaymentMethod($methods->first()->id);
        } else {
            $this->dp_payment_method_id = null;
            $this->dp_payment_method_rate_id = null;
        }
    }

    public function resetDpBankGroup()
    {
        $this->dp_bank_group = null;
        $this->dp_payment_method_id = null;
        $this->dp_payment_method_rate_id = null;
    }

    public function selectDpPaymentMethod($id, $rateId = null)
    {
        $this->dp_payment_method_id = $id;
        $pm = \App\Models\PaymentMethod::find($id);
        if ($pm) {
            if ($pm->category === 'NON-TUNAI') {
                $this->dp_bank_group = $pm->bank_name ?: $pm->name;
            }
            if ($rateId) {
                $this->dp_payment_method_rate_id = $rateId;
            } elseif ($pm->rates()->where('is_active', true)->exists()) {
                $this->dp_payment_method_rate_id = $pm->rates()->where('is_active', true)->first()->id;
            } else {
                $this->dp_payment_method_rate_id = null;
            }
        }
    }

    public function loadCustomerDeposits()
    {
        if ($this->user_id) {
            $buId = $this->business_unit_id;
            $this->available_customer_deposits = \App\Models\CustomerDeposit::where('user_id', $this->user_id)
                ->where('status', 'AVAILABLE')
                ->where('balance', '>', 0)
                ->where(function($q) use ($buId) {
                    if ($buId) {
                        $q->where('business_unit_id', $buId)->orWhereNull('business_unit_id');
                    }
                })
                ->get()
                ->toArray();
            $this->available_customer_deposit_total = collect($this->available_customer_deposits)->sum('balance');
        } else {
            $this->available_customer_deposits = [];
            $this->available_customer_deposit_total = 0;
        }
    }

    public function updatedUseCustomerDeposit($value)
    {
        if ($value) {
            $this->custom_deposit_amount = min($this->available_customer_deposit_total, $this->grand_total);
        } else {
            $this->custom_deposit_amount = 0;
        }
    }

    public function nextStep()
    {
        if ($this->wizardStep == 1) {
            $this->validate([
                'user_id' => 'required',
                'order_date' => 'required|date',
                'business_unit_id' => 'required',
            ], [
                'user_id.required' => 'Silakan pilih pelanggan terlebih dahulu.',
                'order_date.required' => 'Tanggal SO harus diisi.',
                'business_unit_id.required' => 'Unit Usaha harus dipilih.',
            ]);
        } elseif ($this->wizardStep == 2) {
            if (empty($this->items)) {
                $this->addError('items', 'Minimal pilih 1 produk.');
                return;
            }
            $this->validate([
                'items.*.variant_id' => 'required',
                'items.*.qty' => 'required|numeric|min:1',
            ], [
                'items.*.variant_id.required' => 'Ada baris produk yang belum dipilih.',
                'items.*.qty.min' => 'Kuantitas minimal 1.',
            ]);

            // Auto-commit SN jika pengguna mengetik/scan IMEI tapi belum sempat menekan tombol tambah/Enter
            foreach ($this->items as $idx => $item) {
                if (!empty(trim($item['scanned_sn'] ?? ''))) {
                    $this->addSn($idx);
                    if (!empty(trim($this->items[$idx]['scanned_sn'] ?? ''))) {
                        $this->addError('items', 'IMEI pada baris ke-' . ($idx + 1) . ' belum valid: ' . $this->items[$idx]['scanned_sn']);
                        return;
                    }
                }
            }
        }

        if ($this->wizardStep < 3) {
            $this->wizardStep++;
        }
    }

    public function prevStep()
    {
        if ($this->wizardStep > 1) {
            $this->wizardStep--;
        }
    }

    public function setDpPercentage($percent)
    {
        $this->has_dp = true;
        $this->dp_amount = round(($this->grand_total * (float)$percent) / 100);
    }

    public function setDpNominal($amount)
    {
        $this->has_dp = true;
        $this->dp_amount = min($this->grand_total, (float)$amount);
    }

    public function updatedHasDp($value)
    {
        if ($value && empty($this->dp_amount)) {
            $this->dp_amount = 0;
        }
    }

    public function updatedDpPaymentMethodId($val)
    {
        $pm = \App\Models\PaymentMethod::find($val);
        if ($pm && $pm->rates()->where('is_active', true)->exists()) {
            $this->dp_payment_method_rate_id = $pm->rates()->where('is_active', true)->first()->id;
        } else {
            $this->dp_payment_method_rate_id = null;
        }
    }

    public function addItem()
    {
        $this->items[] = [
            'searchProduct' => '',
            'searchResults' => [],
            'variant_id' => '',
            'qty' => 1,
            'unit_price' => 0,
            'discount' => 0,
            'total' => 0,
            'product_name' => '',
            'item_no' => '',
            'available_stock' => 0,
            'serial_numbers' => [], // Validated SNs as badges
            'scanned_sn' => '', // Current input string
        ];
        $this->calculateTotals();
    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        $this->calculateTotals();
    }

    public function updatedItems($value, $key)
    {
        // $key looks like '0.qty' or '0.variant_id'
        $parts = explode('.', $key);
        if (count($parts) == 2) {
            $index = $parts[0];
            $field = $parts[1];

            if ($field === 'searchProduct') {
                $term = $value;
                if (strlen($term) >= 2) {
                    $warehouseId = Auth::user()->warehouse_id;
                    $this->items[$index]['searchResults'] = \App\Models\ProductAccurate::where(function ($q) use ($term) {
                        $q->where('name', 'like', '%' . $term . '%')
                            ->orWhere('item_no', 'like', '%' . $term . '%');
                    })
                        ->where('business_unit_id', $this->business_unit_id)
                        ->take(10)
                        ->get()
                        ->map(function ($v) use ($warehouseId) {
                            $stock = 0;
                            if ($warehouseId) {
                                $stock = \App\Models\WarehouseStock::where([
                                    'warehouse_id' => $warehouseId,
                                    'variant_id'   => $v->id,
                                    'variant_type' => \App\Models\ProductAccurate::class,
                                ])->value('stock') ?? 0;
                            }
                            return [
                                'id' => $v->id,
                                'name' => $v->name,
                                'item_no' => $v->item_no ?? '',
                                'price' => $v->base_price,
                                'stock' => (int)$stock
                            ];
                        })->toArray();
                } else {
                    $this->items[$index]['searchResults'] = [];
                }
            }

            if ($field === 'variant_id') {
                // Fetch price based on variant
                $item = $this->items[$index];
                if (!empty($item['variant_id'])) {
                    $variant = \App\Models\ProductAccurate::find($item['variant_id']);
                    $this->items[$index]['unit_price'] = $variant ? $variant->base_price : 0;
                } else {
                    $this->items[$index]['unit_price'] = 0;
                }
            }

            // Handle Qty change to ensure serial_numbers count doesn't exceed qty
            if ($field === 'qty') {
                $item = $this->items[$index];
                $newQty = max(1, (int)$value);

                $currentSns = $item['serial_numbers'] ?? [];
                if (count($currentSns) > $newQty) {
                    // Potong SN yang berlebih
                    $this->items[$index]['serial_numbers'] = array_slice($currentSns, 0, $newQty);
                    $this->dispatch('toast', title: 'Info', message: 'Jumlah Kunci IMEI disesuaikan karena Qty berkurang.', type: 'warning');
                }
            }

            // Recalculate row total
            $qty = (float)($this->items[$index]['qty'] ?: 0);
            $price = (float)($this->items[$index]['unit_price'] ?: 0);
            $discount = (float)($this->items[$index]['discount'] ?: 0);

            $this->items[$index]['total'] = ($qty * $price) - $discount;
            $this->calculateTotals();
        }
    }

    public function updatedSearchCustomer($value)
    {
        if (strlen($value) >= 3) {
            $this->customerSearchResults = User::role('user')
                ->where(function ($q) use ($value) {
                    $q->where('name', 'like', '%' . $value . '%')
                        ->orWhere('email', 'like', '%' . $value . '%')
                        ->orWhereHas('profile', function ($q2) use ($value) {
                            $q2->where('phone_number', 'like', '%' . $value . '%');
                        });
                })
                ->with('profile')
                ->take(10)
                ->get()
                ->map(function ($u) {
                    return [
                        'id' => $u->id,
                        'name' => $u->name,
                        'email' => $u->email,
                        'phone' => $u->profile->phone_number ?? '-'
                    ];
                })->toArray();
        } else {
            $this->customerSearchResults = [];
        }
    }

    public function addSn($itemIndex)
    {
        $sn = trim($this->items[$itemIndex]['scanned_sn'] ?? '');
        $variantId = $this->items[$itemIndex]['variant_id'];
        $qty = (int)($this->items[$itemIndex]['qty'] ?? 1);
        $currentSns = $this->items[$itemIndex]['serial_numbers'] ?? [];

        if (empty($sn)) {
            return;
        }

        if (count($currentSns) >= $qty) {
            $this->dispatch('toast', title: 'Gagal', message: 'Jumlah IMEI tidak boleh melebihi Qty barang.', type: 'error');
            $this->items[$itemIndex]['scanned_sn'] = '';
            return;
        }

        if (empty($variantId)) {
            $this->dispatch('toast', title: 'Gagal', message: 'Pilih produk terlebih dahulu.', type: 'error');
            $this->items[$itemIndex]['scanned_sn'] = '';
            return;
        }

        // Cek duplikasi input dalam satu SO ini (baik di baris yang sama maupun beda baris)
        foreach ($this->items as $idx => $item) {
            foreach ($item['serial_numbers'] ?? [] as $s) {
                if (trim($s) === $sn) {
                    $this->dispatch('toast', title: 'Gagal', message: 'SN sudah dimasukkan di pesanan ini.', type: 'error');
                    $this->items[$itemIndex]['scanned_sn'] = '';
                    return;
                }
            }
        }

        // --- Logika POS ---
        $warehouseId = Auth::user()->warehouse_id;
        $buId = $this->business_unit_id ?? Auth::user()->getActiveBusinessUnitId();

        $localSnRecord = \App\Models\ProductSerialNumber::where('serial_number', $sn)->first();

        if (!$localSnRecord) {
            $this->dispatch('toast', title: 'Gagal', message: "SN '{$sn}' tidak ditemukan di sistem lokal.", type: 'error');
            $this->items[$itemIndex]['scanned_sn'] = '';
            return;
        }

        if ($localSnRecord->product_accurate_id != $variantId) {
            $productName = $localSnRecord->productAccurate ? $localSnRecord->productAccurate->name : 'Produk Lain';
            $this->dispatch('toast', title: 'Gagal', message: "SN ini milik produk: {$productName}", type: 'error');
            $this->items[$itemIndex]['scanned_sn'] = '';
            return;
        }

        if ($localSnRecord->business_unit_id !== null && $localSnRecord->business_unit_id != $buId) {
            $this->dispatch('toast', title: 'Gagal', message: "SN terdaftar untuk Cabang/Unit Usaha lain.", type: 'error');
            $this->items[$itemIndex]['scanned_sn'] = '';
            return;
        }

        if ($localSnRecord->warehouse_id != $warehouseId) {
            $actualWarehouseName = \App\Models\Warehouse::where('id', $localSnRecord->warehouse_id)->value('name') ?? 'Gudang Lain';
            $this->dispatch('toast', title: 'Gagal', message: "SN ada di {$actualWarehouseName}.", type: 'error');
            $this->items[$itemIndex]['scanned_sn'] = '';
            return;
        }

        if ($localSnRecord->status === 'Unavailable') {
            $this->dispatch('toast', title: 'SN Tidak Tersedia', message: "SN sudah Terjual atau Draft.", type: 'warning');
            $this->items[$itemIndex]['scanned_sn'] = '';
            return;
        }

        // Lolos validasi, masukkan ke array sebagai Badge
        $this->items[$itemIndex]['serial_numbers'][] = $sn;
        $this->items[$itemIndex]['scanned_sn'] = '';
        $this->dispatch('toast', title: 'Berhasil', message: 'SN ditambahkan.', type: 'success');
    }

    public function removeSn($itemIndex, $snIndex)
    {
        if (isset($this->items[$itemIndex]['serial_numbers'][$snIndex])) {
            unset($this->items[$itemIndex]['serial_numbers'][$snIndex]);
            // Re-index array
            $this->items[$itemIndex]['serial_numbers'] = array_values($this->items[$itemIndex]['serial_numbers']);
        }
    }

    public function selectCustomer($id, $name, $email = null, $phone = null)
    {
        $this->user_id = $id;
        $this->searchCustomer = '';
        $this->customerSearchResults = [];
        $this->selectedCustomerName = $name;
        $this->selectedCustomerEmail = $email;
        $this->selectedCustomerPhone = $phone;
        $this->loadCustomerDeposits();
    }

    public function clearCustomer()
    {
        $this->user_id = null;
        $this->searchCustomer = '';
        $this->selectedCustomerName = null;
        $this->selectedCustomerEmail = null;
        $this->selectedCustomerPhone = null;
        $this->available_customer_deposits = [];
        $this->available_customer_deposit_total = 0;
        $this->use_customer_deposit = false;
        $this->custom_deposit_amount = 0;
    }

    public function updatedSearchSales($value)
    {
        if (strlen($value) >= 1) {
            $user = Auth::user();
            $businessUnitId = $user->getActiveBusinessUnitId() ?? 1;

            $this->salesSearchResults = \App\Models\Employe::active()
                ->where('business_unit_id', $businessUnitId)
                ->with('branch')
                ->where(function ($q) use ($user) {
                    $q->where('branch_id', $user->branch_id)
                        ->orWhereNull('branch_id');
                })
                ->where('name', 'like', '%' . $value . '%')
                ->take(10)
                ->get()
                ->map(function ($s) {
                    return [
                        'id' => $s->id,
                        'name' => $s->name,
                        'employee_no' => $s->employee_no,
                        'branch_name' => $s->branch ? $s->branch->name : 'Semua Cabang',
                    ];
                })->toArray();
        } else {
            $this->salesSearchResults = [];
        }
    }

    public function selectSales($id, $name, $branchName = null, $employeeNo = null)
    {
        $this->sales_id = $id;
        $this->searchSales = '';
        $this->salesSearchResults = [];
        $this->selectedSalesName = $name;
        $this->selectedSalesBranch = $branchName;
        $this->selectedSalesNo = $employeeNo;
    }

    public function clearSales()
    {
        $this->sales_id = null;
        $this->searchSales = '';
        $this->selectedSalesName = null;
        $this->selectedSalesBranch = null;
        $this->selectedSalesNo = null;
    }

    public function createNewCustomer()
    {
        $this->validate([
            'new_customer_name' => 'required|string|min:3',
            'new_customer_phone' => 'required|string|min:9',
        ], [
            'new_customer_name.required' => 'Nama wajib diisi',
            'new_customer_phone.required' => 'No HP wajib diisi',
        ]);

        $email = $this->new_customer_email;
        if (empty($email)) {
            // Gunakan rand agar email generated selalu unik (mirip dengan logic di POS)
            $email = preg_replace('/[^0-9]/', '', $this->new_customer_phone) . rand(1000, 9999) . '@zpos.com';
        }

        try {
            DB::beginTransaction();

            // Check if phone or email already exists
            $existingUser = User::where('email', $email)->orWhereHas('profile', function ($q) {
                $q->where('phone_number', preg_replace('/[^0-9]/', '', $this->new_customer_phone));
            })->first();

            if ($existingUser) {
                $this->dispatch('toast', title: 'Gagal', message: 'Email atau No HP sudah terdaftar di pelanggan lain.', type: 'error');
                return;
            }

            $user = User::create([
                'name' => $this->new_customer_name,
                'email' => $email,
                'password' => bcrypt('password123'), // Default password
            ]);

            $user->assignRole('user');

            \App\Models\UserProfile::create([
                'user_id' => $user->id,
                'full_name' => $this->new_customer_name,
                'phone_number' => preg_replace('/[^0-9]/', '', $this->new_customer_phone),
            ]);

            DB::commit();

            // Sinkronisasi otomatis ke Accurate setelah berhasil simpan di lokal DB
            try {
                if ($this->business_unit_id) {
                    $accurateService = app(\App\Services\AccurateService::class);
                    $businessUnit = \App\Models\BusinessUnit::find($this->business_unit_id);
                    $dbSource = $businessUnit ? $businessUnit->code : 'syihab';

                    $accurateService->syncCustomer($user, $dbSource);
                    $user->refresh();
                }
            } catch (\Exception $e) {
                Log::error('Accurate Sync Customer Error (saat buat baru di SO): ' . $e->getMessage());
                // Tetap lanjut meskipun gagal sync (bisa disync nanti saat simpan SO)
            }

            $this->selectCustomer($user->id, $user->name, $user->email, $user->profile->phone_number ?? null);
            $this->showNewCustomerModal = false;

            // Reset fields
            $this->new_customer_name = '';
            $this->new_customer_phone = '';
            $this->new_customer_email = '';

            $this->dispatch('toast', title: 'Berhasil', message: 'Pelanggan baru berhasil ditambahkan.', type: 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal membuat pelanggan baru di SO: ' . $e->getMessage());
            $this->dispatch('toast', title: 'Gagal', message: 'Terjadi kesalahan sistem saat membuat pelanggan.', type: 'error');
        }
    }

    public function selectProduct($index, $id, $name, $price)
    {
        $warehouseId = Auth::user()->warehouse_id;
        $variant = \App\Models\ProductAccurate::find($id);
        $stock = 0;
        if ($warehouseId && $variant) {
            $stock = \App\Models\WarehouseStock::where([
                'warehouse_id' => $warehouseId,
                'variant_id'   => $variant->id,
                'variant_type' => \App\Models\ProductAccurate::class,
            ])->value('stock') ?? 0;
        }

        $this->items[$index]['variant_id'] = $id;
        $this->items[$index]['product_name'] = $name;
        $this->items[$index]['item_no'] = $variant ? $variant->item_no : '';
        $this->items[$index]['available_stock'] = (int)$stock;
        $this->items[$index]['searchProduct'] = $name;
        $this->items[$index]['unit_price'] = $price;
        $this->items[$index]['searchResults'] = [];

        $this->updatedItems(null, $index . '.qty'); // Recalculate
    }

    public function updatedBusinessUnitId($value)
    {
        foreach ($this->items as $index => $item) {
            $this->items[$index]['searchResults'] = [];
            $this->items[$index]['searchProduct'] = '';
            $this->items[$index]['variant_id'] = '';
            $this->items[$index]['product_name'] = '';
            $this->items[$index]['item_no'] = '';
            $this->items[$index]['available_stock'] = 0;
            $this->items[$index]['unit_price'] = 0;
            $this->items[$index]['total'] = 0;
        }
        $this->calculateTotals();
    }

    public function calculateTotals()
    {
        $this->subtotal = 0;
        $this->discount_amount = 0;

        foreach ($this->items as &$item) {
            $qty = (float)($item['qty'] ?: 0);
            $price = (float)($item['unit_price'] ?: 0);
            $discount = (float)($item['discount'] ?: 0);

            $rowTotal = ($qty * $price) - $discount;
            $item['total'] = max(0, $rowTotal);

            $this->subtotal += ($qty * $price);
            $this->discount_amount += $discount;
        }

        $this->grand_total = max(0, $this->subtotal - $this->discount_amount);

        if ($this->has_dp && (float)$this->dp_amount > $this->grand_total) {
            $this->dp_amount = $this->grand_total;
        }
    }

    public function save()
    {
        $handler = Auth::user();
        if (!$handler || !$handler->branch || !$handler->warehouse) {
            $this->dispatch('toast', title: 'Akses Ditolak', message: 'Akun Anda belum terhubung dengan Cabang (Branch) atau Gudang. Harap hubungi Admin.', type: 'error');
            return;
        }

        try {
            $rules = [
                'user_id' => 'required',
                'business_unit_id' => 'required',
                'order_date' => 'required|date',
                'items' => 'required|array|min:1',
                'items.*.variant_id' => 'required',
                'items.*.qty' => 'required|numeric|min:1',
            ];

            $messages = [
                'user_id.required' => 'Pelanggan harus dipilih.',
                'business_unit_id.required' => 'Unit Usaha harus dipilih.',
                'items.*.variant_id.required' => 'Produk belum dipilih.',
                'items.*.qty.min' => 'Kuantitas minimal 1.',
            ];

            if ($this->has_dp) {
                $actualAmount = (float) $this->dp_amount;
                $depositToUse = ($this->use_customer_deposit && $this->custom_deposit_amount > 0) ? (float) $this->custom_deposit_amount : 0;
                $totalPaid = $actualAmount + $depositToUse;

                if ($totalPaid <= 0) {
                    $this->addError('dp_amount', 'Masukkan nominal DP yang dibayarkan atau gunakan saldo deposit.');
                    return;
                }

                if ($totalPaid > $this->grand_total) {
                    $this->addError('dp_amount', 'Total pembayaran DP tidak boleh melebihi Grand Total pesanan.');
                    return;
                }

                if ($actualAmount > 0) {
                    $rules['dp_payment_method_id'] = 'required';
                    $messages['dp_payment_method_id.required'] = 'Metode pembayaran DP harus dipilih.';
                }

                $rules['dp_date'] = 'required|date';
                $messages['dp_date.required'] = 'Tanggal pembayaran DP harus diisi.';
            }

            $this->validate($rules, $messages);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validation Error di Create SO: ' . $e->getMessage());
            $this->dispatch('toast', title: 'Validasi Gagal', message: 'Harap periksa kembali isian form Anda.', type: 'warning');
            throw $e;
        }

        Log::info('--- Memulai Proses Simpan Sales Order ---', [
            'user_id' => $this->user_id,
            'business_unit_id' => $this->business_unit_id,
            'subtotal' => $this->subtotal,
            'grand_total' => $this->grand_total,
            'has_dp' => $this->has_dp,
            'dp_amount' => $this->dp_amount,
            'custom_deposit_amount' => $this->custom_deposit_amount,
        ]);

        $dateToUse = !empty($this->order_date) ? \Carbon\Carbon::parse($this->order_date) : now();
        $businessUnit = BusinessUnit::find($this->business_unit_id);
        $completePrefix = 'SO-' . ($businessUnit ? $businessUnit->prefix : 'GEN') . '-';
        $orderNumber = $completePrefix . $dateToUse->format('Ymd') . '-' . mt_rand(1000, 9999) . '-' . str_pad(
            Order::whereDate('order_date', $dateToUse->format('Y-m-d'))
                ->where('order_channel', 'SO')
                ->count() + 1,
            4,
            '0',
            STR_PAD_LEFT
        );

        $deposit = null;
        $orderPayment = null;

        try {
            DB::beginTransaction();

            $handler = Auth::user();
            $branchName = $handler->branch->name ?? 'Banjarbaru';

            // Create Order
            $order = Order::create([
                'user_id' => $this->user_id,
                'business_unit_id' => $this->business_unit_id,
                'branch_id' => $handler->branch_id,
                'order_channel' => 'SO',
                'order_number' => $orderNumber,
                'order_date' => $this->order_date,
                'total_amount' => $this->subtotal,
                'discount_amount' => $this->discount_amount,
                'grand_total' => $this->grand_total,
                'order_status' => 'pending',
                'handled_by' => Auth::id(),
                'sales_id' => $this->sales_id,
                'notes' => $this->notes,
                'shipping_address_snapshot' => [
                    'type' => 'MINACCURATE',
                    'store' => $branchName
                ],
            ]);

            // Create Items
            foreach ($this->items as $item) {
                $itemSns = isset($item['serial_numbers']) ? array_filter(array_map('trim', $item['serial_numbers'])) : [];
                $variant = \App\Models\ProductAccurate::find($item['variant_id']);

                $hppData = \App\Services\OrderHppResolverService::resolve(
                    $variant,
                    $itemSns,
                    $order->business_unit_id,
                    $item['qty'],
                    false
                );

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_variant_id' => $item['variant_id'],
                    'product_variant_type' => \App\Models\ProductAccurate::class,
                    'qty' => $item['qty'],
                    'price_at_checkout' => $item['unit_price'],
                    'unit_hpp' => $hppData['unit_hpp'],
                    'total_hpp' => $hppData['total_hpp'],
                    'discount_amount' => $item['discount'],
                    'subtotal' => $item['total'],
                    'serial_number' => !empty($itemSns) ? implode(',', $itemSns) : null,
                    'sales_ids' => !empty($item['sales_ids']) ? json_encode($item['sales_ids']) : ($this->sales_id ? json_encode([$this->sales_id]) : null),
                ]);

                // Kunci IMEI di lokal dengan status Unavailable & potong stok gudang lokal
                if (!empty($itemSns)) {
                    \App\Models\ProductSerialNumber::whereIn('serial_number', $itemSns)
                        ->update(['status' => 'Unavailable']);

                    $warehouseId = Auth::user()->warehouse_id;
                    if ($warehouseId) {
                        $stockRecord = \App\Models\WarehouseStock::where([
                            'warehouse_id' => $warehouseId,
                            'variant_id'   => $item['variant_id'],
                            'variant_type' => \App\Models\ProductAccurate::class,
                        ])->first();
                        if ($stockRecord) {
                            $stockRecord->decrement('stock', (int)$item['qty']);
                        }
                    }
                }
            }

            // Proses Pembayaran DP Langsung (Jika diaktifkan)
            if ($this->has_dp) {
                $actualAmount = (float)$this->dp_amount;
                $depositToUse = ($this->use_customer_deposit && $this->custom_deposit_amount > 0) ? (float)$this->custom_deposit_amount : 0;
                $totalDpPaid = $actualAmount + $depositToUse;

                // 1. Potong Saldo Deposit Pelanggan lama (jika digunakan)
                if ($depositToUse > 0 && !empty($this->available_customer_deposits)) {
                    $depositIds = collect($this->available_customer_deposits)->pluck('id')->toArray();
                    $lockedDeposits = \App\Models\CustomerDeposit::whereIn('id', $depositIds)
                        ->where('status', 'AVAILABLE')
                        ->lockForUpdate()
                        ->get();

                    $remainingDpNeeded = $depositToUse;
                    foreach ($lockedDeposits as $existingDeposit) {
                        if ($remainingDpNeeded > 0) {
                            $useAmount = min((float)$existingDeposit->balance, $remainingDpNeeded);

                            \App\Models\CustomerDepositUsage::create([
                                'customer_deposit_id' => $existingDeposit->id,
                                'order_id' => $order->id,
                                'amount_used' => $useAmount,
                            ]);

                            $existingDeposit->balance -= $useAmount;
                            if ($existingDeposit->balance <= 0) {
                                $existingDeposit->status = 'USED';
                            }
                            $existingDeposit->save();

                            \App\Models\OrderPayment::create([
                                'order_id' => $order->id,
                                'xendit_external_id' => 'DP-USE-' . date('YmdHis') . rand(1000, 9999),
                                'amount' => $useAmount,
                                'status' => 'PAID',
                                'no_kontrak' => $this->dp_contract_number,
                                'paid_at' => Carbon::parse($this->dp_date),
                            ]);

                            if ($existingDeposit->accurate_invoice_no) {
                                $doc = \App\Models\OrderAccurateDoc::firstOrNew([
                                    'order_id' => $order->id,
                                    'doc_type' => 'DP_INVOICE',
                                    'doc_number' => $existingDeposit->accurate_invoice_no,
                                ]);
                                $doc->amount = ($doc->amount ?? 0) + $useAmount;
                                $doc->status = 'SUCCESS';
                                $doc->save();
                            }
                            if ($existingDeposit->accurate_receipt_no) {
                                $doc = \App\Models\OrderAccurateDoc::firstOrNew([
                                    'order_id' => $order->id,
                                    'doc_type' => 'DP_RECEIPT',
                                    'doc_number' => $existingDeposit->accurate_receipt_no,
                                ]);
                                $doc->amount = ($doc->amount ?? 0) + $useAmount;
                                $doc->status = 'SUCCESS';
                                $doc->save();
                            }

                            $remainingDpNeeded -= $useAmount;
                        }
                    }
                }

                // 2. Buat Uang Muka baru dari Cash / Transfer
                if ($actualAmount > 0) {
                    $deposit = \App\Models\CustomerDeposit::create([
                        'user_id' => $order->user_id,
                        'origin_order_id' => $order->id,
                        'business_unit_id' => $order->business_unit_id,
                        'amount' => $actualAmount,
                        'balance' => $actualAmount,
                        'payment_method_id' => $this->dp_payment_method_id,
                        'status' => 'AVAILABLE',
                        'notes' => 'DP langsung dari SO ' . $order->order_number . ($this->dp_notes ? ' - ' . $this->dp_notes : ''),
                        'created_by' => Auth::id(),
                    ]);

                    $orderPayment = \App\Models\OrderPayment::create([
                        'order_id' => $order->id,
                        'xendit_external_id' => 'DP-SO-' . date('YmdHis') . rand(1000, 9999),
                        'amount' => $actualAmount,
                        'status' => 'PAID',
                        'payment_method_id' => $this->dp_payment_method_id,
                        'payment_method_rate_id' => $this->dp_payment_method_rate_id,
                        'no_kontrak' => $this->dp_contract_number,
                        'paid_at' => Carbon::parse($this->dp_date),
                    ]);
                }

                if ($totalDpPaid >= $order->grand_total) {
                    $order->update(['order_status' => 'paid']);
                } elseif ($totalDpPaid > 0) {
                    $order->update(['order_status' => 'down_payment']);
                }
            }

            DB::commit();

            // Trigger Sync SO to Accurate
            try {
                $accurateService = app(AccurateService::class);
                $customerUser = User::find($this->user_id);
                $businessUnit = BusinessUnit::find($this->business_unit_id);
                $dbSource = $businessUnit ? $businessUnit->code : 'syihab';

                $accurateBranchName = $branchName;
                if ($dbSource === 'second' && !str_contains(strtolower($accurateBranchName), 'gsk')) {
                    $accurateBranchName = 'GSK ' . $accurateBranchName;
                }

                $accurateService->syncCustomer($customerUser, $dbSource);
                $customerUser->refresh();

                $detailItems = [];
                foreach ($this->items as $item) {
                    $variant = \App\Models\ProductAccurate::find($item['variant_id']);
                    $itemName = $variant->name ?? 'Unknown';

                    $buId = $businessUnit?->id ?? $variant->business_unit_id ?? 1;
                    $projectNo = BusinessUnitProject::getProjectNoByBusinessUnit($buId, $variant->proyek, $variant->proyek ?? '');

                    $detailData = [
                        'itemNo' => $variant->item_no ?? 'ITEM-UNKNOWN',
                        'unitPrice' => (float)$item['unit_price'],
                        'quantity' => (float)$item['qty'],
                        'detailName' => $itemName,
                        'useTax1'   => false,
                        'itemCashDiscount' => (float)$item['discount'],
                        'projectNo' => $projectNo
                    ];

                    if (!empty($item['sales_ids'])) {
                        $employeeNos = \App\Models\Employe::whereIn('id', $item['sales_ids'])
                            ->pluck('employee_no')
                            ->filter()
                            ->values()
                            ->toArray();

                        if (!empty($employeeNos)) {
                            $detailData['salesmanListNumber'] = $employeeNos;
                        }
                    } elseif ($this->sales_id) {
                        $salesmanNo = !empty($this->selectedSalesNo) ? $this->selectedSalesNo : \App\Models\Employe::where('id', $this->sales_id)->value('employee_no');
                        if (!empty($salesmanNo)) {
                            $detailData['salesmanListNumber'] = [(string) $salesmanNo];
                        }
                    }

                    $joinedSn = isset($item['serial_numbers']) ? implode(',', array_filter(array_map('trim', $item['serial_numbers']))) : '';
                    if (!empty($joinedSn)) {
                        $sns = explode(',', $joinedSn);
                        if (count($sns) > 0) {
                            $detailSNs = [];
                            foreach ($sns as $sn) {
                                $detailSNs[] = ['serialNumberNo' => $sn, 'quantity' => 1];
                            }
                            $detailData['detailSerialNumber'] = $detailSNs;
                        }
                    }

                    $detailItems[] = $detailData;
                }

                $soData = [
                    'customerNo' => $customerUser->getAccurateCustomerNo($dbSource),
                    'branchName' => $accurateBranchName,
                    'transDate' => Carbon::parse($this->order_date)->format('d/m/Y'),
                    'detailItem' => $detailItems,
                    'inclusiveTax' => false,
                    'taxable' => false,
                    'description' => $this->notes
                ];

                $soResult = $accurateService->postSalesOrder($soData, $dbSource);
                Log::info('Response API Accurate SO: ', is_array($soResult) ? $soResult : []);

                if (isset($soResult['r']['number'])) {
                    $order->update(['accurate_so_number' => $soResult['r']['number']]);
                    \App\Models\OrderAccurateDoc::create([
                        'order_id' => $order->id,
                        'doc_type' => 'SALES_ORDER',
                        'doc_number' => $soResult['r']['number'],
                        'accurate_id' => $soResult['r']['id'] ?? null,
                        'amount' => $this->grand_total,
                        'status' => 'SUCCESS',
                    ]);
                    Log::info('Berhasil Sync SO ke Accurate dengan Nomor: ' . $soResult['r']['number']);
                }

                // Sinkronisasi Faktur DP ke Accurate jika ada pembayaran DP baru
                if ($deposit && $orderPayment && (float)$this->dp_amount > 0) {
                    try {
                        $dpAmount = (float)$this->dp_amount;
                        $pm = \App\Models\PaymentMethod::find($this->dp_payment_method_id);
                        $rate = null;
                        if ($this->dp_payment_method_rate_id) {
                            $rate = \App\Models\PaymentMethodRate::find($this->dp_payment_method_rate_id);
                        } elseif ($pm && $pm->rates()->where('is_active', true)->exists()) {
                            $rate = $pm->rates()->where('is_active', true)->first();
                        }

                        $pct = $rate ? (float) ($rate->percentage ?? $rate->mdr_percentage ?? 0) : 0;
                        $rowMdr = $pct > 0 ? round($dpAmount * $pct / 100, 0) : 0;
                        $netReceiptAmount = $dpAmount - $rowMdr;

                        $detailDiscounts = [];
                        if ($rowMdr > 0 && $rate && $rate->accurate_account_no) {
                            $detailDiscounts[] = [
                                'accountNo' => $rate->accurate_account_no,
                                'amount' => (float) $rowMdr,
                                'departmentName' => $accurateBranchName,
                                'discountNotes' => 'MDR DP'
                            ];
                        }

                        $dpInvData = [
                            'customerNo' => $customerUser->getAccurateCustomerNo($dbSource),
                            'branchName' => $accurateBranchName,
                            'dpAmount'   => (float)$dpAmount,
                            'transDate'  => Carbon::parse($this->dp_date)->format('d/m/Y'),
                            'inclusiveTax' => false,
                            'isTaxable' => false,
                            'description' => 'Uang Muka (DP) dari SO: ' . ($order->accurate_so_number ?? $order->order_number) . ($this->dp_contract_number ? '. No Kontrak: ' . $this->dp_contract_number : '') . ($this->dp_notes ? '. ' . $this->dp_notes : ''),
                        ];

                        if ($this->dp_contract_number) {
                            $dpInvData['poNumber'] = $this->dp_contract_number;
                        }

                        $dpInvResult = $accurateService->postDownPaymentInvoice($dpInvData, $dbSource);
                        if (isset($dpInvResult['r']['number'])) {
                            $dpInvoiceNo = $dpInvResult['r']['number'];
                            $deposit->update(['accurate_invoice_no' => $dpInvoiceNo]);

                            \App\Models\OrderAccurateDoc::create([
                                'order_id' => $order->id,
                                'doc_type' => 'DP_INVOICE',
                                'doc_number' => $dpInvoiceNo,
                                'accurate_id' => $dpInvResult['r']['id'] ?? null,
                                'amount' => (float)$dpAmount,
                                'status' => 'SUCCESS',
                            ]);

                            $srData = [
                                'customerNo' => $customerUser->getAccurateCustomerNo($dbSource),
                                'branchName' => $accurateBranchName,
                                'bankNo' => $pm->accurate_bank_no ?? 'KAS-CASH',
                                'transDate' => Carbon::parse($this->dp_date)->format('d/m/Y'),
                                'receiptAmount' => (float)$netReceiptAmount,
                                'chequeAmount' => (float)$netReceiptAmount,
                                'description' => 'Penerimaan DP dari SO: ' . ($order->accurate_so_number ?? $order->order_number) . ($this->dp_contract_number ? '. No Kontrak: ' . $this->dp_contract_number : '') . ($this->dp_notes ? '. ' . $this->dp_notes : ''),
                                'detailInvoice' => [
                                    [
                                        'invoiceNo' => $dpInvoiceNo,
                                        'paymentAmount' => (float)$dpAmount,
                                    ]
                                ]
                            ];

                            if (!empty($detailDiscounts)) {
                                $srData['detailInvoice'][0]['detailDiscount'] = $detailDiscounts;
                            }

                            $srResult = $accurateService->postSalesReceipt($srData, $dbSource);
                            if (isset($srResult['r']['number'])) {
                                $deposit->update(['accurate_receipt_no' => $srResult['r']['number']]);
                                \App\Models\OrderAccurateDoc::create([
                                    'order_id' => $order->id,
                                    'doc_type' => 'DP_RECEIPT',
                                    'doc_number' => $srResult['r']['number'],
                                    'accurate_id' => $srResult['r']['id'] ?? null,
                                    'amount' => (float)$dpAmount,
                                    'status' => 'SUCCESS',
                                ]);
                            }
                        }
                    } catch (\Exception $eDp) {
                        Log::error('Accurate DP Sync Error: ' . $eDp->getMessage());
                        $this->dispatch('toast', title: 'DP Tersimpan di Tokopon', message: 'DP tercatat di sistem toko, namun gagal sync faktur DP ke Accurate: ' . $eDp->getMessage(), type: 'warning');
                    }
                }
            } catch (\Exception $e) {
                Log::error('Accurate SO Sync Error: ' . $e->getMessage());
                $this->dispatch('toast', title: 'Sync Accurate Gagal', message: 'SO tersimpan di sistem, namun gagal tersinkron ke Accurate: ' . $e->getMessage(), type: 'warning');
            }

            Log::info('--- Berhasil Menyimpan Sales Order: ' . $order->order_number . ' ---');
            $this->dispatch('toast', title: 'Berhasil', message: 'Sales Order ' . $order->order_number . ' berhasil dibuat!', type: 'success');
            return redirect()->route('admin.sales-orders.show', $order);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('CRITICAL ERROR Simpan SO: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            $this->dispatch('toast', title: 'Gagal Menyimpan', message: 'Terjadi kesalahan sistem: ' . $e->getMessage(), type: 'error');
        }
    }

    public function render()
    {
        $warehouses = Warehouse::all();
        $isGsk = false;
        if ($this->business_unit_id) {
            $bu = BusinessUnit::find($this->business_unit_id);
            if ($bu && $bu->code === 'second') {
                $isGsk = true;
            }
            $warehouses = Warehouse::where('business_unit_id', $this->business_unit_id)->get();
        }

        $user = Auth::user();
        $businessUnitId = $this->business_unit_id ?? $user->getActiveBusinessUnitId();
        $warehouseName = $user->warehouse ? trim(strtolower($user->warehouse->name)) : null;

        $cashPaymentMethods = \App\Models\PaymentMethod::where('is_active', true)
            ->where('category', 'TUNAI')
            ->where(function ($query) use ($businessUnitId) {
                $query->where('business_unit_id', $businessUnitId)
                    ->orWhereNull('business_unit_id');
            })
            ->get();

        if ($warehouseName) {
            $filteredCash = $cashPaymentMethods->filter(function ($method) use ($warehouseName) {
                $methodNameLower = strtolower(trim($method->name));
                if (str_contains($methodNameLower, 'tukar tambah')) {
                    return true;
                }
                $methodLocation = trim(str_replace('tunai', '', $methodNameLower));
                if (empty($methodLocation)) return false;
                return str_contains($warehouseName, $methodLocation);
            })->values();
            if ($filteredCash->isNotEmpty()) {
                $cashPaymentMethods = $filteredCash;
            }
        }

        $nonCashPaymentMethods = \App\Models\PaymentMethod::where('is_active', true)
            ->where('category', 'NON-TUNAI')
            ->where(function ($query) use ($businessUnitId) {
                $query->where('business_unit_id', $businessUnitId)
                    ->orWhereNull('business_unit_id');
            })
            ->with('rates')
            ->orderBy('name')
            ->get();

        // Auto-assign default payment method hanya jika tunai
        if (empty($this->dp_payment_method_id) && $this->dp_mode === 'tunai' && $cashPaymentMethods->isNotEmpty()) {
            $this->dp_payment_method_id = $cashPaymentMethods->first()->id;
        }

        // Kumpulan grup bank non-tunai (seperti pada POS)
        $nonCashBankGroups = $nonCashPaymentMethods
            ->map(function ($m) {
                return trim($m->bank_name ?: $m->name);
            })
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        // Filter metode untuk grup bank yang sedang dipilih
        $methodsInSelectedGroup = collect();
        if ($this->dp_mode === 'non-tunai' && !empty($this->dp_bank_group)) {
            $methodsInSelectedGroup = $nonCashPaymentMethods->filter(function ($m) {
                $grp = trim($m->bank_name ?: $m->name);
                return strcasecmp($grp, $this->dp_bank_group) === 0;
            })->values();
        }

        return view('livewire.admin.orders.sales-order.create', [
            'businessUnits' => BusinessUnit::all(),
            'warehouses' => $warehouses,
            'isGsk' => $isGsk,
            'cashPaymentMethods' => $cashPaymentMethods,
            'nonCashPaymentMethods' => $nonCashPaymentMethods,
            'nonCashBankGroups' => $nonCashBankGroups,
            'methodsInSelectedGroup' => $methodsInSelectedGroup,
            'paymentMethods' => \App\Models\PaymentMethod::where('is_active', true)->where(function($q) use ($businessUnitId) {
                if ($businessUnitId) $q->where('business_unit_id', $businessUnitId)->orWhereNull('business_unit_id');
            })->get(),
        ])->layout('layouts.z');
    }
}

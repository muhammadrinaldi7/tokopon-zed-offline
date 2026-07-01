<?php

namespace App\Livewire\Admin\Orders\SalesOrder;

use App\Models\BusinessUnit;
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

    // Sales
    public $sales_id;
    public $searchSales = '';
    public $salesSearchResults = [];

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

    // Wizard
    public int $wizardStep = 1;

    public function mount()
    {
        $this->order_date = Carbon::now()->format('Y-m-d');
        $this->business_unit_id = Auth::user()->getActiveBusinessUnitId();
        $this->warehouse_id = Auth::user()->warehouse_id;
        // Initial empty row
        $this->addItem();
    }

    public function nextStep()
    {
        if ($this->wizardStep == 1) {
            $this->validate([
                'user_id' => 'required',
                'order_date' => 'required|date',
            ], [
                'user_id.required' => 'Silakan pilih pelanggan terlebih dahulu.',
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
            'serial_number' => '', // Tambahan untuk Kunci IMEI di SO
            'searchSales' => '',
            'salesSearchResults' => [],
            'sales_ids' => $this->sales_id ? [$this->sales_id] : [], // Default ke master sales_id
            'sales_names' => $this->searchSales ? [$this->searchSales] : [],
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
                    $this->items[$index]['searchResults'] = \App\Models\ProductAccurate::where(function ($q) use ($term) {
                        $q->where('name', 'like', '%' . $term . '%')
                            ->orWhere('item_no', 'like', '%' . $term . '%');
                    })
                        ->where('business_unit_id', $this->business_unit_id)
                        ->take(10)
                        ->get()
                        ->map(function ($v) {
                            return [
                                'id' => $v->id,
                                'name' => $v->name,
                                'price' => $v->base_price
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

            if ($field === 'searchSales') {
                $term = $value;
                if (strlen($term) >= 1) {
                    $user = Auth::user();
                    $businessUnitId = $user->getActiveBusinessUnitId() ?? 1;

                    $this->items[$index]['salesSearchResults'] = \App\Models\Employe::active()
                        ->where('business_unit_id', $businessUnitId)
                        ->where(function ($q) use ($user) {
                            $q->where('branch_id', $user->branch_id)
                                ->orWhereNull('branch_id');
                        })
                        ->where('name', 'like', '%' . $term . '%')
                        ->take(5)
                        ->get()
                        ->map(function ($s) {
                            return [
                                'id' => $s->id,
                                'name' => $s->name,
                            ];
                        })->toArray();
                } else {
                    $this->items[$index]['salesSearchResults'] = [];
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
                        ->orWhere('email', 'like', '%' . $value . '%');
                })
                ->take(10)
                ->get()
                ->map(function ($u) {
                    return [
                        'id' => $u->id,
                        'name' => $u->name,
                        'email' => $u->email
                    ];
                })->toArray();
        } else {
            $this->customerSearchResults = [];
        }
    }

    public function selectCustomer($id, $name)
    {
        $this->user_id = $id;
        $this->searchCustomer = $name;
        $this->customerSearchResults = [];
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

    public function selectSales($id, $name)
    {
        $this->sales_id = $id;
        $this->searchSales = $name;
        $this->salesSearchResults = [];

        // Terapkan ke semua item yang belum punya sales_ids
        foreach ($this->items as $index => $item) {
            if (empty($item['sales_ids'])) {
                $this->items[$index]['sales_ids'] = [$id];
                $this->items[$index]['sales_names'] = [$name];
            }
        }
    }

    public function selectItemSales($index, $id, $name)
    {
        if (!in_array($id, $this->items[$index]['sales_ids'])) {
            $this->items[$index]['sales_ids'][] = $id;
            $this->items[$index]['sales_names'][] = $name;
        }
        $this->items[$index]['searchSales'] = '';
        $this->items[$index]['salesSearchResults'] = [];
    }

    public function removeItemSales($index, $salesIndex)
    {
        unset($this->items[$index]['sales_ids'][$salesIndex]);
        unset($this->items[$index]['sales_names'][$salesIndex]);
        
        $this->items[$index]['sales_ids'] = array_values($this->items[$index]['sales_ids']);
        $this->items[$index]['sales_names'] = array_values($this->items[$index]['sales_names']);
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

            $this->selectCustomer($user->id, $user->name);
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
        $this->items[$index]['variant_id'] = $id;
        $this->items[$index]['product_name'] = $name;
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
    }

    public function save()
    {
        $handler = Auth::user();
        if (!$handler || !$handler->branch || !$handler->warehouse) {
            $this->dispatch('toast', title: 'Akses Ditolak', message: 'Akun Anda belum terhubung dengan Cabang (Branch) atau Gudang. Harap hubungi Admin.', type: 'error');
            return;
        }

        try {
            $this->validate([
                'user_id' => 'required',
                'business_unit_id' => 'required',
                'order_date' => 'required|date',
                'items' => 'required|array|min:1',
                'items.*.variant_id' => 'required',
                'items.*.qty' => 'required|numeric|min:1',
            ], [
                'user_id.required' => 'Pelanggan harus dipilih.',
                'business_unit_id.required' => 'Unit Usaha harus dipilih.',
                'items.*.variant_id.required' => 'Produk belum dipilih.',
            ]);
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
        ]);

        $dateToUse = !empty($this->order_date) ? \Carbon\Carbon::parse($this->order_date) : now();
        $businessUnit = BusinessUnit::find($this->business_unit_id);
        $completePrefix = 'SO-' . $businessUnit->prefix . '-';
        $orderNumber = $completePrefix . $dateToUse->format('Ymd') . '-' . mt_rand(1000, 9999) . '-' . str_pad(
            Order::whereDate('order_date', $dateToUse->format('Y-m-d')) // <- Menggunakan order_date
                ->where('order_channel', 'SO')
                ->count() + 1,
            4,
            '0',
            STR_PAD_LEFT
        );

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
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_variant_id' => $item['variant_id'],
                    'product_variant_type' => \App\Models\ProductAccurate::class,
                    'qty' => $item['qty'],
                    'price_at_checkout' => $item['unit_price'],
                    'discount_amount' => $item['discount'],
                    'subtotal' => $item['total'],
                    'serial_number' => $item['serial_number'] ?? null, // Simpan IMEI
                    'sales_ids' => !empty($item['sales_ids']) ? json_encode($item['sales_ids']) : null,
                ]);
            }

            DB::commit();

            // Trigger Sync to Accurate
            try {
                $accurateService = app(AccurateService::class);
                $customerUser = User::find($this->user_id);
                $businessUnit = BusinessUnit::find($this->business_unit_id);
                $dbSource = $businessUnit ? $businessUnit->code : 'syihab';

                // Check if we need to append GSK prefix for Accurate Second branch
                // Accurate Second usually prefixes branch with "GSK " if it's not already there
                $accurateBranchName = $branchName;
                if ($dbSource === 'second' && !str_contains(strtolower($accurateBranchName), 'gsk')) {
                    $accurateBranchName = 'GSK ' . $accurateBranchName;
                }

                // Sync Customer to Accurate
                $accurateService->syncCustomer($customerUser, $dbSource);
                $customerUser->refresh();

                $detailItems = [];
                foreach ($this->items as $item) {
                    $variant = \App\Models\ProductAccurate::find($item['variant_id']);
                    $itemName = $variant->name ?? 'Unknown';

                    $detailData = [
                        'itemNo' => $variant->item_no ?? 'ITEM-UNKNOWN',
                        'unitPrice' => (float)$item['unit_price'],
                        'quantity' => (float)$item['qty'],
                        'detailName' => $itemName,
                        'useTax1'   => false,
                        'itemCashDiscount' => (float)$item['discount'],
                    ];

                    // Map sales_ids to Accurate employee numbers
                    if (!empty($item['sales_ids'])) {
                        $employeeNos = \App\Models\Employe::whereIn('id', $item['sales_ids'])
                            ->pluck('employee_no')
                            ->filter()
                            ->values()
                            ->toArray();
                            
                        if (!empty($employeeNos)) {
                            $detailData['salesmanListNumber'] = $employeeNos;
                        }
                    }

                    // Jika user mengisi SN / IMEI, kirim ke Accurate untuk mencadangkan SN
                    if (!empty($item['serial_number'])) {
                        $sns = array_filter(array_map('trim', explode(',', $item['serial_number'])));
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

                    // CEK APAKAH ADA ITEM YANG PUNYA SN (JIKA YA, BUAT DELIVERY ORDER)
                    $hasSN = false;
                    foreach ($this->items as $item) {
                        if (!empty($item['serial_number'])) {
                            $hasSN = true;
                            break;
                        }
                    }

                    if ($hasSN) {
                        Log::info('IMEI dideteksi pada SO, memulai pembuatan Delivery Order otomatis...');

                        // Menentukan Gudang (Warehouse) untuk DO
                        $handler = Auth::user();
                        $warehouseName = $handler->warehouse->name ?? 'Gudang Utama';

                        $doDetailItems = [];
                        foreach ($this->items as $item) {
                            $variant = \App\Models\ProductAccurate::find($item['variant_id']);

                            $detailData = [
                                'itemNo' => $variant->item_no ?? 'ITEM-UNKNOWN',
                                'quantity' => (float)$item['qty'],
                                'warehouseName' => $warehouseName,
                                'salesOrderNumber' => $soResult['r']['number'],
                            ];

                            if (!empty($item['serial_number'])) {
                                $sns = array_filter(array_map('trim', explode(',', $item['serial_number'])));
                                if (count($sns) > 0) {
                                    $detailSNs = [];
                                    foreach ($sns as $sn) {
                                        $detailSNs[] = ['serialNumberNo' => $sn, 'quantity' => 1];
                                    }
                                    $detailData['detailSerialNumber'] = $detailSNs;
                                }
                            }

                            $doDetailItems[] = $detailData;
                        }

                        $doData = [
                            'customerNo' => $customerUser->getAccurateCustomerNo($dbSource),
                            'branchName' => $accurateBranchName,
                            'transDate' => Carbon::parse($this->order_date)->format('d/m/Y'),
                            'salesOrderNumber' => $soResult['r']['number'],
                            'description' => 'DO Otomatis dari SO (Kunci IMEI). ' . $this->notes,
                            'detailItem' => $doDetailItems
                        ];

                        Log::info('Payload Delivery Order: ' . json_encode($doData));
                        $doResult = $accurateService->postDeliveryOrder($doData, $dbSource);
                        Log::info('Response API Accurate DO: ', is_array($doResult) ? $doResult : []);

                        if (isset($doResult['r']['number'])) {
                            \App\Models\OrderAccurateDoc::create([
                                'order_id' => $order->id,
                                'doc_type' => 'DELIVERY_ORDER',
                                'doc_number' => $doResult['r']['number'],
                                'accurate_id' => $doResult['r']['id'] ?? null,
                                'amount' => $this->grand_total,
                                'status' => 'SUCCESS',
                            ]);
                            Log::info('Berhasil membuat Delivery Order dengan Nomor: ' . $doResult['r']['number']);
                        }
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

        return view('livewire.admin.orders.sales-order.create', [
            'businessUnits' => BusinessUnit::all(),
            'warehouses' => $warehouses,
            'isGsk' => $isGsk,
        ])->layout('layouts.z');
    }
}

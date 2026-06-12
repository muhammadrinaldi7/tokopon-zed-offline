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

    public function mount()
    {
        $this->order_date = Carbon::now()->format('Y-m-d');
        // Initial empty row
        $this->addItem();
    }

    public function getAvailableType()
    {
        if ($this->business_unit_id) {
            $bu = BusinessUnit::find($this->business_unit_id);
            if ($bu && $bu->code === 'second') {
                return 'second';
            }
        }
        return 'new'; // default
    }

    public function addItem()
    {
        $this->items[] = [
            'type' => $this->getAvailableType(),
            'searchProduct' => '',
            'searchResults' => [],
            'variant_id' => '',
            'qty' => 1,
            'unit_price' => 0,
            'discount' => 0,
            'total' => 0,
            'product_name' => '',
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
                    $type = $this->items[$index]['type'];
                    if ($type === 'new') {
                        $this->items[$index]['searchResults'] = ProductVariant::with('product')
                            ->whereHas('product', function($q) use ($term) {
                                $q->where('name', 'like', '%' . $term . '%');
                            })
                            ->orWhere('sku', 'like', '%' . $term . '%')
                            ->take(10)
                            ->get()
                            ->map(function($v) {
                                return [
                                    'id' => $v->id,
                                    'name' => ($v->product->name ?? 'Unknown') . ' ' . $v->storage . ' ' . $v->color,
                                    'price' => $v->price
                                ];
                            })->toArray();
                    } else {
                        $this->items[$index]['searchResults'] = SecondProductVariant::with('secondProduct')
                            ->whereHas('secondProduct', function($q) use ($term) {
                                $q->where('name', 'like', '%' . $term . '%');
                            })
                            ->orWhere('sku', 'like', '%' . $term . '%')
                            ->take(10)
                            ->get()
                            ->map(function($v) {
                                return [
                                    'id' => $v->id,
                                    'name' => ($v->secondProduct->name ?? 'Unknown') . ' ' . $v->storage . ' ' . $v->color,
                                    'price' => $v->price
                                ];
                            })->toArray();
                    }
                } else {
                    $this->items[$index]['searchResults'] = [];
                }
            }

            if ($field === 'variant_id' || $field === 'type') {
                // Fetch price based on variant
                $item = $this->items[$index];
                if (!empty($item['variant_id'])) {
                    if ($item['type'] === 'new') {
                        $variant = ProductVariant::find($item['variant_id']);
                        $this->items[$index]['unit_price'] = $variant ? $variant->price : 0;
                    } else {
                        $variant = SecondProductVariant::find($item['variant_id']);
                        $this->items[$index]['unit_price'] = $variant ? $variant->price : 0;
                    }
                } else {
                    $this->items[$index]['unit_price'] = 0;
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
                ->where(function($q) use ($value) {
                    $q->where('name', 'like', '%' . $value . '%')
                      ->orWhere('email', 'like', '%' . $value . '%');
                })
                ->take(10)
                ->get()
                ->map(function($u) {
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
        $type = $this->getAvailableType();
        foreach ($this->items as $index => $item) {
            if ($item['type'] !== $type) {
                $this->items[$index]['type'] = $type;
                $this->items[$index]['searchResults'] = [];
                $this->items[$index]['searchProduct'] = '';
                $this->items[$index]['variant_id'] = '';
                $this->items[$index]['product_name'] = '';
                $this->items[$index]['unit_price'] = 0;
                $this->items[$index]['total'] = 0;
            }
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

        try {
            DB::beginTransaction();

            $businessUnit = BusinessUnit::find($this->business_unit_id);
            $branchName = $businessUnit->name ?? 'Banjarbaru';

            // Create Order
            $order = Order::create([
                'user_id' => $this->user_id,
                'business_unit_id' => $this->business_unit_id,
                'order_channel' => 'SO',
                'order_number' => 'SO-' . time() . rand(10, 99),
                'order_date' => $this->order_date,
                'total_amount' => $this->subtotal,
                'discount_amount' => $this->discount_amount,
                'grand_total' => $this->grand_total,
                'order_status' => 'pending',
                'handled_by' => Auth::id(),
                'notes' => $this->notes,
                'shipping_address_snapshot' => [
                    'type' => 'MINACCURATE',
                    'store' => $branchName
                ],
            ]);

            // Create Items
            foreach ($this->items as $item) {
                $variantClass = $item['type'] === 'new' ? ProductVariant::class : SecondProductVariant::class;
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_variant_id' => $item['variant_id'],
                    'product_variant_type' => $variantClass,
                    'qty' => $item['qty'],
                    'price_at_checkout' => $item['unit_price'],
                    'discount_amount' => $item['discount'],
                    'subtotal' => $item['total'],
                ]);
            }

            DB::commit();

            // Trigger Sync to Accurate
            try {
                $accurateService = app(AccurateService::class);
                $customerUser = User::find($this->user_id);
                $businessUnit = BusinessUnit::find($this->business_unit_id);
                $branchName = $businessUnit->name ?? 'Banjarbaru';
                $dbSource = $businessUnit ? $businessUnit->code : 'syihab';

                // Sync Customer to Accurate
                $accurateService->syncCustomer($customerUser, $dbSource);
                $customerUser->refresh();

                $detailItems = [];
                foreach ($this->items as $item) {
                    $variant = $item['type'] === 'new' ? ProductVariant::find($item['variant_id']) : SecondProductVariant::find($item['variant_id']);
                    $itemName = $item['type'] === 'new' ? ($variant->product->name ?? 'Unknown') : ($variant->secondProduct->name ?? 'Unknown');

                    $detailItems[] = [
                        'itemNo' => $variant->sku ?? 'ITEM-UNKNOWN',
                        'unitPrice' => (float)$item['unit_price'],
                        'quantity' => (float)$item['qty'],
                        'detailName' => $itemName . ' ' . ($variant->color ?? '') . ' ' . ($variant->storage ?? ''),
                        'itemCashDiscount' => (float)$item['discount'],
                    ];
                }

                $soData = [
                    'customerNo' => $customerUser->getAccurateCustomerNo($dbSource),
                    'branchName' => $branchName,
                    'transDate' => Carbon::parse($this->order_date)->format('d/m/Y'),
                    'detailItem' => $detailItems,
                    'inclusiveTax' => true,
                    'taxable' => true,
                    'description' => $this->notes
                ];

                $soResult = $accurateService->postSalesOrder($soData, $dbSource);
                Log::info('Response API Accurate SO: ', is_array($soResult) ? $soResult : []);
                
                if (isset($soResult['r']['number'])) {
                    $order->update(['accurate_so_number' => $soResult['r']['number']]);
                    Log::info('Berhasil Sync SO ke Accurate dengan Nomor: ' . $soResult['r']['number']);
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
        ])->layout('layouts.admin');
    }
}

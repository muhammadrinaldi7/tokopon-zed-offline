<?php

namespace App\Livewire\Admin\Orders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\SecondProductVariant;
use App\Models\User;
use App\Models\WarehouseStock;
use App\Services\AccurateService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.admin', ['title' => 'Import Transaksi (Draft)'])]
class ImportDraft extends Component
{
    use WithFileUploads;

    public $file;
    public $isProcessing = false;
    public $results = [];
    public $summary = ['total' => 0, 'success' => 0, 'failed' => 0];

    public function downloadTemplate()
    {
        return response()->download(public_path('templates/template_import_draft_pos.csv'));
    }

    public function processImport()
    {
        $this->validate([
            'file' => 'required|file|max:2048',
        ], [
            'file.required' => 'Pilih file CSV terlebih dahulu.',
        ]);

        if (strtolower($this->file->getClientOriginalExtension()) !== 'csv' && strtolower($this->file->getClientOriginalExtension()) !== 'txt') {
            $this->addError('file', 'Format file harus CSV.');
            return;
        }

        Log::info("Mulai proses import CSV. Mime: " . $this->file->getMimeType());

        $this->isProcessing = true;
        $this->results = [];
        $this->summary = ['total' => 0, 'success' => 0, 'failed' => 0];

        try {
            $path = $this->file->getRealPath();

            // Auto detect delimiter (comma, semicolon, or tab)
            $content = file_get_contents($path);
            $commas = substr_count($content, ',');
            $semicolons = substr_count($content, ';');
            $tabs = substr_count($content, "\t");

            $delimiter = ',';
            if ($semicolons > $commas && $semicolons > $tabs) $delimiter = ';';
            if ($tabs > $commas && $tabs > $semicolons) $delimiter = "\t";

            Log::info("Detected delimiter: " . ($delimiter === "\t" ? 'TAB' : $delimiter));

            $data = [];
            if (($handle = fopen($path, "r")) !== false) {
                while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                    if (count($row) > 0 && !empty(array_filter($row))) {
                        $data[] = $row;
                    }
                }
                fclose($handle);
            }

            $header = array_shift($data);

            // Clean BOM from header if exists
            if (!empty($header) && count($header) > 0) {
                $header[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $header[0]);
            }

            // Expected headers: Tanggal,Nama Customer,No HP,SKU,SN,Qty,Harga Satuan,Diskon Item,Catatan,Tenaga Penjual(Nama/NoPegawai)
            // Group by Customer and Date to form Orders
            $ordersGroup = [];

            foreach ($data as $index => $row) {
                if (count($row) < 9) {
                    // Tampilkan struktur row yang bermasalah langsung di UI agar kita tahu penyebabnya
                    $this->addError('file', "Baris ke-" . ($index + 2) . " tidak valid/kurang dari 9 kolom. Jumlah Kolom: " . count($row) . ". Isi data: " . json_encode($row));
                    $this->isProcessing = false;
                    return;
                }

                $rowKey = strtolower(trim($row[1]) . '_' . trim($row[0])); // Group by Customer Name and Date

                if (!isset($ordersGroup[$rowKey])) {
                    $ordersGroup[$rowKey] = [
                        'tanggal' => trim($row[0]),
                        'customerName' => trim($row[1]),
                        'customerPhone' => trim($row[2]),
                        'notes' => trim($row[8] ?? ''),
                        'handlerIdentifier' => trim($row[9] ?? ''), // Optional
                        'items' => []
                    ];
                }

                $ordersGroup[$rowKey]['items'][] = [
                    'sku' => trim($row[3]),
                    'sn' => trim($row[4]),
                    'qty' => (int)trim($row[5]),
                    'price' => (float)trim($row[6]),
                    'discount' => (float)trim($row[7]),
                    'rowNumber' => $index + 2
                ];
            }

            if (empty($ordersGroup)) {
                $this->dispatch('toast', title: 'Data Kosong', message: 'Tidak ada baris data yang valid. Pastikan format kolom sudah sesuai template (Pemisah Koma atau Titik Koma).', type: 'warning');
                $this->isProcessing = false;
                $this->file = null;
                return;
            }

            $this->summary['total'] = count($ordersGroup);

            $accurateService = app(AccurateService::class);
            $handler = Auth::user();

            // Ambil jumlah order hari ini untuk penomoran
            $orderCountToday = Order::where('order_number', 'like', 'POS-SYB-' . now()->format('Ymd') . '-%')->count();

            foreach ($ordersGroup as $group) {
                $orderCountToday++;
                $this->processOrderGroup($group, $accurateService, $handler, $orderCountToday);
            }
        } catch (\Exception $e) {
            Log::error('Import Draft Error: ' . $e->getMessage());
            $this->dispatch('toast', title: 'Error File', message: 'Terjadi kesalahan membaca file CSV: ' . $e->getMessage(), type: 'error');
        }

        $this->isProcessing = false;
        $this->file = null; // reset file
    }

    private function processOrderGroup($group, $accurateService, $handler, $orderCountToday)
    {
        DB::beginTransaction();
        try {
            // 1. Resolve Customer
            $customerName = $group['customerName'];
            $customerPhone = $group['customerPhone'];
            $emailToValidate = $customerPhone ? ($customerPhone . '@zpos.com') : null;

            $user = null;
            if ($customerPhone) {
                $user = User::whereHas('profile', function ($q) use ($customerPhone) {
                    $q->where('phone_number', $customerPhone);
                })->first();
            }

            if (!$user && $customerName) {
                $user = User::where('name', $customerName)->first();
            }

            if (!$user) {
                $user = User::create([
                    'name' => $customerName,
                    'email' => $emailToValidate,
                    'password' => bcrypt('zpos' . rand(1000, 9999)),
                ]);
                $user->assignRole('user');

                if ($customerPhone) {
                    $user->profile()->create([
                        'full_name' => $customerName,
                        'phone_number' => $customerPhone,
                    ]);
                }
            }

            // 2. Prepare Order Details
            $subtotal = 0;
            $totalDiscount = 0;
            $orderItemsToInsert = [];
            $hasSecond = false;

            $branchName = $handler->branch->name ?? 'Toko';
            $warehouseName = $handler->warehouse->name ?? 'Head Office';
            $warehouseId = $handler->warehouse_id;

            $orderNumber = 'POS-SYB-' . now()->format('Ymd') . '-' . str_pad(
                $orderCountToday,
                4,
                '0',
                STR_PAD_LEFT
            );

            // Prepare Salesperson (Tenaga Penjual)
            $salesId = null;
            $handlerIdentifier = $group['handlerIdentifier'] ?? '';
            if (!empty($handlerIdentifier)) {
                $sales = \App\Models\Employe::where('name', $handlerIdentifier)
                    ->orWhere('employee_no', $handlerIdentifier)
                    ->first();
                if ($sales) {
                    $salesId = $sales->id;
                }
            }

            // Create Order first to get ID
            $order = Order::create([
                'business_unit_id' => \Illuminate\Support\Facades\Auth::user()->getActiveBusinessUnitId() ?? 1,
                'user_id' => $user->id,
                'order_number' => $orderNumber,
                'total_amount' => 0,
                'shipping_cost' => 0,
                'discount_amount' => 0,
                'grand_total' => 0,
                'order_status' => 'DRAFT',
                'order_channel' => 'POS',
                'handled_by' => $handler->id, // Kasir yang melakukan import
                'sales_id' => $salesId, // Tenaga Penjual dari Accurate
                'shipping_address_snapshot' => ['type' => 'POS', 'store' => $branchName],
                'notes' => 'IMPORT: ' . $group['notes'],
                'created_at' => \Carbon\Carbon::parse($group['tanggal'])->format('Y-m-d H:i:s'),
                'updated_at' => \Carbon\Carbon::parse($group['tanggal'])->format('Y-m-d H:i:s'),
            ]);

            $accurateDetailItems = [];

            foreach ($group['items'] as $itemData) {
                // Find Variant
                $variant = ProductVariant::where('sku', $itemData['sku'])->first();
                $variantType = ProductVariant::class;
                $dbSource = 'syihab';

                if (!$variant) {
                    $variant = SecondProductVariant::where('sku', $itemData['sku'])->first();
                    $variantType = SecondProductVariant::class;
                    if ($variant) {
                        $hasSecond = true;
                        $dbSource = 'second';
                    }
                }

                if (!$variant) {
                    throw new \Exception("SKU tidak ditemukan: " . $itemData['sku']);
                }

                $itemSubtotal = $itemData['price'] * $itemData['qty'];
                $subtotal += $itemSubtotal;
                $totalDiscount += $itemData['discount'];

                // Insert Local OrderItem
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_variant_id' => $variant->id,
                    'product_variant_type' => $variantType,
                    'qty' => $itemData['qty'],
                    'price_at_checkout' => $itemData['price'],
                    'subtotal' => $itemSubtotal,
                    'discount_amount' => $itemData['discount'],
                    'serial_number' => $itemData['sn'],
                ]);

                // Reduce Stock Local
                $warehouseStock = WarehouseStock::firstOrCreate(
                    [
                        'warehouse_id' => $warehouseId,
                        'variant_id' => $variant->id,
                        'variant_type' => $variantType,
                    ],
                    ['stock' => 0]
                );
                $warehouseStock->update([
                    'stock' => max(0, $warehouseStock->stock - (int)$itemData['qty'])
                ]);

                // Prepare Accurate Item
                $sns = array_filter(array_map('trim', explode(',', $itemData['sn'])));
                $detailSN = [];
                if (!empty($sns)) {
                    foreach ($sns as $sn) {
                        $detailSN[] = ['serialNumberNo' => $sn, 'quantity' => 1];
                    }
                } else {
                    $detailSN[] = ['serialNumberNo' => '-', 'quantity' => 1];
                }

                $accurateDetailItems[] = [
                    'itemNo' => $itemData['sku'],
                    'warehouseName' => $warehouseName,
                    'unitPrice' => $itemData['price'],
                    'quantity' => $itemData['qty'],
                    'itemCashDiscount' => $itemData['discount'],
                    'detailSerialNumber' => $detailSN, // SN is included in SO for draft imported via Excel
                ];
            }

            $order->update([
                'total_amount' => $subtotal,
                'discount_amount' => $totalDiscount,
                'grand_total' => max(0, $subtotal - $totalDiscount),
            ]);

            DB::commit();

            // 3. Sync to Accurate (Outside DB Transaction to avoid blocking local save)
            try {
                // [DISABLED FOR LOCAL TESTING]

                $accurateDbSource = $hasSecond ? 'second' : 'syihab';
                $accurateService->syncCustomer($user, $accurateDbSource);
                $user->refresh();

                $soData = [
                    'customerNo' => $user->accurate_customer_no ?? 'CASH',
                    'branchName' => $branchName,
                    'detailItem' => $accurateDetailItems,
                    'inclusiveTax' => true,
                    'taxable' => true,
                    'description' => 'IMPORT DRAFT ' . $group['notes'],
                    'transDate' => \Carbon\Carbon::parse($group['tanggal'])->format('d/m/Y')
                ];

                $soResult = $accurateService->postSalesOrder($soData, $accurateDbSource);

                if (isset($soResult['r']['id']) && isset($soResult['r']['number'])) {
                    $order->update([
                        'accurate_so_id' => $soResult['r']['id'],
                        'accurate_so_number' => $soResult['r']['number'],
                    ]);
                }


                $this->summary['success']++;
                $this->results[] = [
                    'customer' => $customerName,
                    'status' => 'Berhasil',
                    'message' => 'Draft Order ' . $order->order_number . ' tersimpan. (Local Test Mode)'
                ];
            } catch (\Exception $e) {
                Log::error('Import Accurate Sync Error: ' . $e->getMessage());
                $this->summary['success']++; // Local DB succeeded, Accurate sync failed
                $this->results[] = [
                    'customer' => $customerName,
                    'status' => 'Peringatan',
                    'message' => 'Draft lokal tersimpan, tapi gagal sync Accurate SO: ' . $e->getMessage()
                ];
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $this->summary['failed']++;
            $this->results[] = [
                'customer' => $group['customerName'],
                'status' => 'Gagal',
                'message' => $e->getMessage()
            ];
        }
    }

    public function render()
    {
        return view('livewire.admin.orders.import-draft');
    }
}

<?php

namespace App\Livewire\Admin\Settings;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class SystemReset extends Component
{
    public $confirmText = '';
    public $selectedCategories = [
        'transactions' => false,
        'products' => false,
        'vendors' => false,
        'purchases' => false,
        'cashiers' => false,
        'warranties' => false,
        'logs' => false,
        'buybacks' => false,
        'reset_customers' => false,
    ];

    public $preflightData = [];
    public $showConfirmModal = false;

    public function mount()
    {
        $this->refreshPreflight();
    }

    public function refreshPreflight()
    {
        $this->preflightData = [
            'transactions' => [
                'label' => 'Transaksi & Penjualan (Orders)',
                'tables' => [
                    'orders' => $this->countTable('orders'),
                    'order_items' => $this->countTable('order_items'),
                    'order_payments' => $this->countTable('order_payments'),
                    'order_promos' => $this->countTable('order_promos'),
                    'order_item_promos' => $this->countTable('order_item_promos'),
                ]
            ],
            'products' => [
                'label' => 'Master Produk & Stok',
                'tables' => [
                    'product_accurates' => $this->countTable('product_accurates'),
                    'product_serial_numbers' => $this->countTable('product_serial_numbers'),
                    'warehouse_stocks' => $this->countTable('warehouse_stocks'),
                ]
            ],
            'vendors' => [
                'label' => 'Vendor / Pemasok',
                'tables' => [
                    'vendors' => $this->countTable('vendors'),
                ]
            ],
            'purchases' => [
                'label' => 'Pembelian (PO)',
                'tables' => [
                    'purchase_orders' => $this->countTable('purchase_orders'),
                    'purchase_order_items' => $this->countTable('purchase_order_items'),
                ]
            ],
            'cashiers' => [
                'label' => 'Kasir & Closing',
                'tables' => [
                    'cashier_shifts' => $this->countTable('cashier_shifts'),
                ]
            ],
            'warranties' => [
                'label' => 'Garansi & Klaim',
                'tables' => [
                    'warranties' => $this->countTable('warranties'),
                    'warranty_claims' => $this->countTable('warranty_claims'),
                ]
            ],
            'logs' => [
                'label' => 'Approval & Log AI',
                'tables' => [
                    'approval_requests' => $this->countTable('approval_requests'),
                    'ai_chat_histories' => $this->countTable('ai_chat_histories'),
                ]
            ],
            'buybacks' => [
                'label' => 'Trade-In & Buyback',
                'tables' => [
                    'buyback_devices' => $this->countTable('buyback_devices'),
                    'sell_phones' => $this->countTable('sell_phones'),
                    'device_inspections' => $this->countTable('device_inspections'),
                    'trade_ins' => $this->countTable('trade_ins'),
                ]
            ],
            'reset_customers' => [
                'label' => 'Reset Accurate Customer No (Hanya NULL-kan)',
                'tables' => [
                    'user_accurate_customers' => DB::table('user_accurate_customers')->whereNotNull('accurate_customer_no')->count(),
                ]
            ]
        ];
    }

    private function countTable($table)
    {
        try {
            return DB::table($table)->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function selectAll()
    {
        foreach ($this->selectedCategories as $key => $val) {
            $this->selectedCategories[$key] = true;
        }
    }

    public function deselectAll()
    {
        foreach ($this->selectedCategories as $key => $val) {
            $this->selectedCategories[$key] = false;
        }
    }

    public function openConfirmModal()
    {
        // Pastikan ada setidaknya satu opsi dipilih
        $hasSelection = in_array(true, array_values($this->selectedCategories), true);
        
        if (!$hasSelection) {
            session()->flash('error', 'Silakan pilih setidaknya satu kategori data untuk di-reset.');
            return;
        }

        $this->confirmText = '';
        $this->showConfirmModal = true;
    }

    public function executeReset()
    {
        if ($this->confirmText !== 'RESET SISTEM') {
            $this->addError('confirmText', 'Ketik kata kunci dengan benar.');
            return;
        }

        DB::beginTransaction();
        try {
            // Nonaktifkan foreign key checks untuk menghindari error constraint saat Truncate (jika dibutuhkan)
            // Di MySQL, Truncate tidak diperbolehkan jika ada constraint. Kita bisa pakai DELETE.
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            // 1. Transactions
            if ($this->selectedCategories['transactions']) {
                DB::table('order_item_promos')->delete();
                DB::table('order_promos')->delete();
                DB::table('order_payments')->delete();
                DB::table('order_items')->delete();
                DB::table('orders')->delete();
            }

            // 2. Buybacks
            if ($this->selectedCategories['buybacks']) {
                DB::table('device_inspections')->delete();
                DB::table('trade_ins')->delete();
                DB::table('sell_phones')->delete();
                DB::table('buyback_devices')->delete();
            }

            // 3. Warranties
            if ($this->selectedCategories['warranties']) {
                DB::table('warranty_claims')->delete();
                DB::table('warranties')->delete();
            }

            // 4. Products & Stocks
            if ($this->selectedCategories['products']) {
                DB::table('warehouse_stocks')->delete();
                DB::table('product_serial_numbers')->delete();
                DB::table('product_accurates')->delete();
            }

            // 5. Purchases
            if ($this->selectedCategories['purchases']) {
                DB::table('purchase_order_items')->delete();
                DB::table('purchase_orders')->delete();
            }

            // 6. Vendors
            if ($this->selectedCategories['vendors']) {
                DB::table('vendors')->delete();
            }

            // 7. Cashiers
            if ($this->selectedCategories['cashiers']) {
                DB::table('cashier_shifts')->delete();
            }

            // 8. Logs
            if ($this->selectedCategories['logs']) {
                DB::table('approval_requests')->delete();
                DB::table('ai_chat_histories')->delete();
            }

            // 9. Reset Customers
            if ($this->selectedCategories['reset_customers']) {
                DB::table('user_accurate_customers')->update([
                    'accurate_customer_no' => null,
                    'accurate_customer_id' => null,
                ]);
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            DB::commit();

            // Log
            Log::alert('SYSTEM RESET EXECUTED', [
                'admin_id' => Auth::id(),
                'categories' => $this->selectedCategories
            ]);

            $this->showConfirmModal = false;
            $this->confirmText = '';
            $this->deselectAll();
            $this->refreshPreflight();

            session()->flash('success', 'Reset sistem berhasil dieksekusi sesuai kategori yang dipilih.');

        } catch (\Exception $e) {
            DB::rollBack();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            Log::error('System Reset Error: ' . $e->getMessage());
            session()->flash('error', 'Terjadi kesalahan saat mereset sistem: ' . $e->getMessage());
        }
    }

    public function exportPendingSO()
    {
        // For now, redirect to a separate route that handles CSV/Excel export
        return redirect()->route('admin.settings.system-reset.export-so');
    }

    public function render()
    {
        return view('livewire.admin.settings.system-reset')->layout('layouts.admin');
    }
}

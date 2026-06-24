<?php

namespace App\Livewire\Zoffline\Warehouse;

use App\Models\ProductSerialNumber;
use App\Models\OrderItem;
use App\Models\MigrationInvoiceItem;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.z', ['title' => 'Riwayat Serial Number'])]
class SerialNumberHistory extends Component
{
    public $sn = '';
    public $productSn = null;
    public $history = [];


    public function mount($sn)
    {
        $this->sn = urldecode($sn);
        $this->loadHistory();
    }

    public function loadHistory()
    {
        $this->history = [];

        // 1. Dapatkan data awal (Inbound / Beli) dari product_serial_numbers
        $this->productSn = ProductSerialNumber::with(['vendor', 'warehouse', 'productAccurate'])
            ->where('serial_number', $this->sn)
            ->first();

        if ($this->productSn) {
            $this->history[] = [
                'type' => 'inbound',
                'title' => 'Barang Masuk / Terdaftar',
                'date' => $this->productSn->created_at,
                'price' => $this->productSn->hpp,
                'actor' => $this->productSn->vendor ? $this->productSn->vendor->vendor_name : 'Vendor Tidak Diketahui',
                'notes' => 'Tercatat di gudang: ' . ($this->productSn->warehouse ? $this->productSn->warehouse->name : '-'),
                'doc_link' => null,
                'icon' => 'login' // just a flag for blade
            ];
        }

        // 2. Dapatkan data Penjualan dari OrderItem
        $orderItems = OrderItem::with(['order.user', 'order.handledBy'])
            ->where('serial_number', 'like', '%' . $this->sn . '%')
            ->get();

        foreach ($orderItems as $item) {
            $sns = array_map('trim', explode(',', $item->serial_number ?? ''));
            if (in_array($this->sn, $sns)) {
                $sellingPrice = (float)$item->price_at_checkout;
                $profit = null;
                if ($this->productSn && $this->productSn->hpp > 0) {
                    $profit = $sellingPrice - $this->productSn->hpp;
                }

                $customerName = $item->order && $item->order->user ? $item->order->user->name : 'Pelanggan Umum';
                $soNumber = $item->order ? $item->order->accurate_so_number : '-';

                $orderDate = $item->order ? $item->order->created_at : $item->created_at;

                $this->history[] = [
                    'type' => 'outbound',
                    'title' => 'Penjualan',
                    'date' => $orderDate,
                    'price' => $sellingPrice,
                    'profit' => $profit,
                    'actor' => $customerName,
                    'notes' => "Nomor Invoice Accurate: {$soNumber}",
                    'doc_link' => $item->order ? route('admin.sales-orders.show', $item->order->id) : null,
                    'icon' => 'logout'
                ];
            }
        }

        // 3. Dapatkan data Penjualan Lama dari MigrationInvoiceItem
        $legacyItems = MigrationInvoiceItem::with('invoice')
            ->where('serial_numbers', 'like', '%' . $this->sn . '%')
            ->get();

        foreach ($legacyItems as $item) {
            $sns = array_map('trim', explode(',', $item->serial_numbers ?? ''));
            if (in_array($this->sn, $sns)) {
                $sellingPrice = (float)$item->unit_price;
                $profit = null;
                if ($this->productSn && $this->productSn->hpp > 0) {
                    $profit = $sellingPrice - $this->productSn->hpp;
                }

                $customerName = $item->invoice ? $item->invoice->customer_name : 'Pelanggan Lama';
                $invNumber = $item->invoice ? $item->invoice->invoice_number : '-';
                $invDate = $item->invoice && $item->invoice->invoice_date ? \Carbon\Carbon::parse($item->invoice->invoice_date) : $item->created_at;

                $this->history[] = [
                    'type' => 'outbound_legacy',
                    'title' => 'Penjualan Lama (Invoice Migrasi)',
                    'date' => $invDate,
                    'price' => $sellingPrice,
                    'profit' => $profit,
                    'actor' => $customerName,
                    'notes' => "Nomor Invoice: {$invNumber}",
                    'doc_link' => null,
                    'icon' => 'logout'
                ];
            }
        }

        // Urutkan berdasarkan tanggal (ascending - dari lama ke baru)
        usort($this->history, function ($a, $b) {
            return $a['date'] <=> $b['date'];
        });
    }

    public function render()
    {
        return view('livewire.zoffline.warehouse.serial-number-history');
    }
}

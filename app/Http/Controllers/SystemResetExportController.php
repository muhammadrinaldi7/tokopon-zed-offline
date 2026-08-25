<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SystemResetExportController extends Controller
{
    public function exportSo()
    {
        $fileName = 'outstanding_so_backup_' . date('Y-m-d_His') . '.csv';

        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $orders = DB::table('orders')
            ->leftJoin('user_accurate_customers', 'orders.user_id', '=', 'user_accurate_customers.user_id')
            ->leftJoin('users', 'orders.user_id', '=', 'users.id')
            ->whereIn('orders.order_status', ['down_payment', 'pending', 'paid'])
            ->where('orders.order_channel', 'SO')
            ->select(
                'orders.id',
                'orders.order_number',
                'users.name as customer_name',
                'orders.created_at',
                'orders.grand_total'
            )
            ->get();

        $columns = ['Order Number', 'Customer Name', 'Date', 'Item Name', 'Qty', 'Price', 'Serial Number', 'Grand Total', 'Total Paid', 'Remaining Balance'];

        $callback = function () use ($orders, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($orders as $order) {
                // Get payments
                $payments = DB::table('order_payments')->where('order_id', $order->id)->sum('amount');
                $remaining = $order->grand_total - $payments;

                // Get items
                $items = \App\Models\OrderItem::with('variant')->where('order_id', $order->id)->get();

                if ($items->isEmpty()) {
                    fputcsv($file, [
                        $order->order_number,
                        $order->customer_name,
                        $order->created_at,
                        '-',
                        '0',
                        '0',
                        '-',
                        $order->grand_total,
                        $payments,
                        $remaining
                    ]);
                } else {
                    foreach ($items as $index => $item) {
                        $sku = '-';
                        if ($item->variant) {
                            if (get_class($item->variant) === \App\Models\ProductAccurate::class) {
                                $sku = $item->variant->item_no;
                            } else {
                                $sku = $item->variant->accurateData->item_no ?? ($item->variant->item_no ?? '-');
                            }
                        }
                        $name = $item->variant->name ?? '-';
                        $price = $item->price_at_checkout;
                        $sn = $item->serial_number ?? '-';

                        // First row for the order includes the totals, subsequent rows for items just show item info
                        if ($index === 0) {
                            fputcsv($file, [
                                $order->order_number,
                                $order->customer_name,
                                $order->created_at,
                                $sku . ' - ' . $name,
                                $item->qty,
                                $price,
                                $sn,
                                $order->grand_total,
                                $payments,
                                $remaining
                            ]);
                        } else {
                            fputcsv($file, [
                                '',
                                '',
                                '', // Empty for Order Number, Customer, Date
                                $sku . ' - ' . $name,
                                $item->qty,
                                $price,
                                $sn,
                                '',
                                '',
                                ''  // Empty for totals
                            ]);
                        }
                    }
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

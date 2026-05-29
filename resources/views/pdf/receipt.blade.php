<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Struk Transaksi</title>
    <style>
        @page {
            margin: 0;
        }

        body {
            font-family: monospace;
            font-size: 12px;
            color: #000;
            line-height: 1.4;
            background-color: #fff;
            padding: 10px;
            margin: 0;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .font-bold {
            font-weight: bold;
        }

        .divider {
            border-top: 1px dashed #000;
            margin: 8px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            padding: 2px 0;
            vertical-align: top;
        }

        .item-table td {
            font-size: 12px;
        }

        .payment-table td {
            font-size: 11px;
            color: #333;
        }
    </style>
</head>

<body>

    <div class="text-center">
        <p class="font-bold" style="font-size: 14px; margin: 0 0 2px 0;">SYIHAB STORE</p>
        <p style="margin: 0 0 2px 0;">{{ $order->shipping_address_snapshot['store'] ?? 'Toko' }}</p>
        <p style="margin: 0; font-size: 11px;">{{ $order->created_at->format('d/m/Y H:i') }}</p>
    </div>

    <div class="divider"></div>

    <table class="item-table">
        <tr>
            <td>Tanggal:</td>
            <td class="text-right">{{ $order->created_at->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td>No:</td>
            <td class="text-right">{{ $order->order_number }}</td>
        </tr>
        <tr>
            <td>Sales:</td>
            <td class="text-right">{{ $order->salesBy->name ?? '-' }}</td>
        </tr>
        <tr>
            <td>Customer:</td>
            <td class="text-right">{{ $order->user->name ?? '-' }}</td>
        </tr>
    </table>

    <div class="divider"></div>

    @foreach ($order->items as $item)
        @php
            $v = $item->variant;
            $itemName = $v ? $v->product->name ?? ($v->secondProduct->name ?? '-') : '-';
        @endphp
        <div style="margin-bottom: 6px;">
            <p class="font-bold" style="margin: 0;">{{ $itemName }}</p>
            <table class="item-table">
                <tr>
                    <td>{{ $item->qty }}x {{ number_format($item->price_at_checkout, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
            </table>
            @if ($item->serial_number)
                <p style="margin: 0; font-size: 10px; color: #555;">SN: {{ $item->serial_number }}</p>
            @endif
        </div>
    @endforeach

    <div class="divider"></div>

    <table class="item-table">
        <tr>
            <td>TOTAL</td>
            <td class="text-right">{{ number_format($order->total_amount, 0, ',', '.') }}</td>
        </tr>
        {{-- @if ($order->discount_amount > 0)
            <tr>
                <td>Diskon</td>
                <td class="text-right">-{{ number_format($order->discount_amount, 0, ',', '.') }}</td>
            </tr>
        @endif --}}
    </table>

    <div class="divider"></div>

    {{-- <table class="item-table" style="font-size: 13px;">
        <tr class="font-bold">
            <td>TOTAL</td>
            <td class="text-right">Rp {{ number_format($order->grand_total, 0, ',', '.') }}</td>
        </tr>
    </table> --}}

    <div class="divider"></div>

    {{-- <table class="payment-table">
        @foreach ($order->payments as $payment)
            <tr>
                <td>Bayar
                    ({{ $payment->paymentMethod->name ?? 'Cash' }}{{ $payment->paymentMethodRate ? ' - ' . $payment->paymentMethodRate->name : '' }})
                    :
                </td>
                <td class="text-right">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
            </tr>
        @endforeach
    </table> --}}

    @if ($order->accurate_invoice_no)
        <p style="margin: 5px 0 0 0; font-size: 10px; color: #555;">No. SI: {{ $order->accurate_invoice_no }}</p>
    @endif

    <div class="text-center" style="margin-top: 15px;">
        <p style="margin: 0; font-size: 11px;">Terima kasih telah berbelanja!</p>
        <p style="margin: 2px 0 0 0; font-size: 10px; color: #666;">Call Center : 0811-5600-6464</p>
    </div>

</body>

</html>

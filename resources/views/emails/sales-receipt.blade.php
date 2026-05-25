<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Struk Transaksi</title>
</head>

<body
    style="font-family: monospace; font-size: 14px; color: #333; line-height: 1.6; background-color: #f9f9f9; padding: 20px;">
    <div
        style="max-width: 400px; margin: 0 auto; bg-color: #fff; background: #ffffff; padding: 20px; border: 1px solid #eee; border-radius: 8px;">
        <div style="text-align: center; margin-bottom: 20px;">
            <h2 style="margin: 0; font-size: 18px; font-weight: bold;">TOKOPUN</h2>
            <p style="margin: 5px 0 0; font-size: 12px; color: #666;">
                {{ $order->shipping_address_snapshot['store'] ?? 'Toko' }}</p>
            <p style="margin: 5px 0 0; font-size: 11px; color: #999;">{{ $order->created_at->format('d/m/Y H:i') }}</p>
        </div>

        <div style="border-top: 1px dashed #ccc; margin: 10px 0;"></div>
        <p style="margin: 5px 0; font-size: 12px;">No: {{ $order->order_number }}</p>
        <p style="margin: 5px 0; font-size: 12px;">Kasir: {{ $order->handledBy->name ?? '-' }}</p>
        <p style="margin: 5px 0; font-size: 12px;">Customer: {{ $order->user->name ?? '-' }}</p>
        <div style="border-top: 1px dashed #ccc; margin: 10px 0;"></div>

        @foreach ($order->items as $item)
            @php
                $v = $item->variant;
                $itemName = $v ? $v->product->name ?? ($v->secondProduct->name ?? '-') : '-';
            @endphp
            <div style="margin-bottom: 10px;">
                <p style="margin: 0; font-weight: bold;">{{ $itemName }}</p>
                <table style="width: 100%; font-size: 13px;">
                    <tr>
                        <td>{{ $item->qty }}x {{ number_format($item->price_at_checkout, 0, ',', '.') }}</td>
                        <td style="text-align: right;">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                </table>
                @if ($item->serial_number)
                    <p style="margin: 0; font-size: 11px; color: #888;">SN: {{ $item->serial_number }}</p>
                @endif
            </div>
        @endforeach

        <div style="border-top: 1px dashed #ccc; margin: 10px 0;"></div>

        <table style="width: 100%; font-size: 13px; margin-bottom: 5px;">
            <tr>
                <td>Subtotal</td>
                <td style="text-align: right;">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
            </tr>
            @if ($order->discount_amount > 0)
                <tr style="color: #e11d48;">
                    <td>Diskon</td>
                    <td style="text-align: right;">-Rp {{ number_format($order->discount_amount, 0, ',', '.') }}</td>
                </tr>
            @endif
            @if ($order->mdr_amount > 0)
                <tr style="color: #d97706;">
                    <td>MDR Charge ({{ $order->mdr_percentage }}%)</td>
                    <td style="text-align: right;">+Rp {{ number_format($order->mdr_amount, 0, ',', '.') }}</td>
                </tr>
            @endif
        </table>

        <div style="border-top: 1px dashed #ccc; margin: 5px 0;"></div>
        <table style="width: 100%; font-size: 15px; font-weight: bold;">
            <tr>
                <td>TOTAL</td>
                <td style="text-align: right; color: #1c69d4;">Rp {{ number_format($order->grand_total, 0, ',', '.') }}
                </td>
            </tr>
        </table>
        <div style="border-top: 1px dashed #ccc; margin: 10px 0;"></div>

        <p style="margin: 5px 0; font-size: 11px; color: #666;">Bayar: {{ $order->paymentMethod->name ?? 'Cash' }}</p>

        <div style="text-align: center; margin-top: 25px; font-size: 11px; color: #999;">
            <p style="margin: 0;">Terima kasih telah berbelanja!</p>
            <p style="margin: 3px 0 0;">www.tokopun.com</p>
        </div>
    </div>
</body>

</html>

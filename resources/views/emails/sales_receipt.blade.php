<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
        }


        ul {
            padding-left: 20px;
            margin: 15px 0;
        }

        li {
            margin-bottom: 5px;
        }
    </style>
</head>

<body>

    <p>Halo <strong>{{ $order->user->name ?? 'Customer' }}</strong>,</p>

    <p>Terima kasih telah berbelanja di Call Center Zed Group! Berikut adalah detail nota pembelian Anda:</p>

    <ul>
        <li><strong>No. Invoice:</strong> {{ $order->order_number }}</li>
        <li><strong>Total Tagihan:</strong> Rp {{ number_format($order->subtotal, 0, ',', '.') }}</li>
        <li><strong>Status Pembayaran:</strong> <span style="color: #2ca01c; font-weight: bold;">Lunas</span></li>
    </ul>

    <p>Nota digital ini dikirimkan secara otomatis sebagai bukti transaksi yang sah. Jika ada pertanyaan, silakan
        hubungi tim kami ya. Terima kasih!</p>

</body>

</html>

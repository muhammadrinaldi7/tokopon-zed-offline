<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Aktivasi Garansi</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            color: #333;
            margin: 0;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }

        .header h1 {
            margin: 0;
            font-size: 18pt;
        }

        .header p {
            margin: 5px 0 0 0;
            color: #666;
        }

        .summary-container {
            width: 100%;
            margin-bottom: 30px;
        }

        .summary-box {
            float: left;
            width: 30%;
            padding: 10px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-right: 2%;
            box-sizing: border-box;
        }

        .summary-box:last-child {
            margin-right: 0;
        }

        .summary-box h3 {
            margin: 0 0 10px 0;
            font-size: 11pt;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
        }

        .summary-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .summary-list li {
            margin-bottom: 5px;
            border-bottom: 1px dashed #eee;
            padding-bottom: 2px;
        }

        .summary-list li:last-child {
            border-bottom: none;
        }

        .summary-list span {
            float: right;
            font-weight: bold;
        }

        .clear {
            clear: both;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
            font-size: 9pt;
            text-transform: uppercase;
        }

        td {
            font-size: 9pt;
        }

        .footer {
            margin-top: 30px;
            text-align: right;
            font-size: 8pt;
            color: #777;
        }
    </style>
</head>

<body>

    <div class="header">
        <h1>LAPORAN AKTIVASI GARANSI</h1>
        <p>Periode: {{ $startDate }} s/d {{ $endDate }}</p>
    </div>

    <div class="summary-container">
        <div class="summary-box">
            <h3>Total Aktivasi</h3>
            <h2 style="margin:0; font-size: 24pt; text-align: center; color: #1c69d4;">{{ $total }} <span
                    style="font-size: 10pt; color: #666;">Unit</span></h2>
        </div>

        <div class="summary-box">
            <h3>Top Promotor (Sales)</h3>
            <ul class="summary-list">
                @forelse($topPromotors as $name => $count)
                    <li>{{ Str::limit($name, 20) }} <span>{{ $count }}</span></li>
                @empty
                    <li>Belum ada data</li>
                @endforelse
            </ul>
        </div>

        <div class="summary-box">
            <h3>Top Inspektur (Aktivator)</h3>
            <ul class="summary-list">
                @forelse($topInspectors as $name => $count)
                    <li>{{ Str::limit($name, 20) }} <span>{{ $count }}</span></li>
                @empty
                    <li>Belum ada data</li>
                @endforelse
            </ul>
        </div>
        <div class="clear"></div>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tgl Transaksi</th>
                <th>Tgl Aktivasi</th>
                <th>No. Order</th>
                <th>Cabang</th>
                <th>SN / Perangkat</th>
                <th>Kategori</th>
                <th>Nama Barang</th>
                <th>Nama Pelanggan</th>
                <th>Tipe Garansi</th>
                <th>Inspektur</th>
                <th>Promotor</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($warranties as $index => $w)
                @php
                    $warranty = $w->warranty;
                    $orderDate = $w->orderItem->order->order_date ?? $w->orderItem->order->created_at;
                @endphp
                <tr>
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td>{{ $orderDate ? $orderDate->format('d/m/Y') : '-' }}</td>
                    <td>{{ $warranty && $warranty->activated_at ? $warranty->activated_at->format('d/m/Y H:i') : '-' }}</td>
                    <td>{{ $w->orderItem->order->order_number ?? '-' }}</td>
                    <td>{{ $w->orderItem->order->branch->name ?? '-' }}</td>
                    <td><strong>{{ $w->serial_number }}</strong></td>
                    <td>{{ $w->orderItem->variant->categoryName ?? '-' }}</td>
                    <td>{{ Str::limit($w->orderItem->variant->name ?? '-', 30) }}</td>
                    <td>{{ $w->orderItem->order->user->name ?? '-' }}</td>
                    <td>{{ $warranty->policy->name ?? '-' }}</td>
                    <td>{{ $warranty->deviceInspection->inspector->name ?? '-' }}</td>
                    <td>{{ $w->orderItem->order->salesBy->name ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" style="text-align: center; padding: 20px;">Tidak ada data pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Dicetak pada: {{ now()->format('d/m/Y H:i:s') }}
    </div>

</body>

</html>

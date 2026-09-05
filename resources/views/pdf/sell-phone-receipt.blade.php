<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Struk Tanda Terima SPL-{{ $sellPhone->id }}</title>
    <style>
        @page {
            margin: 0;
        }
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px;
            color: #1f2937;
            background-color: #fff;
            margin: 0;
            padding: 12px 14px;
            line-height: 1.35;
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
        .store-title {
            font-size: 14px;
            font-weight: bold;
            color: #111827;
            margin-bottom: 2px;
        }
        .store-sub {
            font-size: 10px;
            color: #6b7280;
            margin: 1px 0;
        }
        .divider {
            border-top: 1px dashed #9ca3af;
            margin: 8px 0;
        }
        .info-p {
            font-size: 10px;
            color: #4b5563;
            margin: 2px 0;
            word-wrap: break-word;
        }
        .section-title {
            font-size: 11px;
            font-weight: bold;
            text-align: center;
            color: #111827;
            margin: 4px 0 6px 0;
        }
        table.kv-table {
            width: 100%;
            border-collapse: collapse;
        }
        table.kv-table td {
            padding: 2px 0;
            vertical-align: top;
        }
        .deal-label {
            font-size: 11px;
            font-weight: bold;
            color: #111827;
        }
        .deal-value {
            font-size: 11px;
            font-weight: bold;
            text-align: right;
            color: #111827;
        }
        .status-label {
            font-size: 10px;
            color: #4b5563;
        }
        .status-value {
            font-size: 10px;
            font-weight: bold;
            text-align: right;
            text-transform: uppercase;
            color: #059669;
        }
        .guarantee-box {
            text-align: center;
            font-size: 9px;
            color: #4b5563;
            line-height: 1.4;
        }
        .guarantee-title {
            font-size: 10px;
            font-weight: bold;
            color: #374151;
            margin-bottom: 3px;
        }
        .bank-info {
            font-weight: bold;
            color: #1f2937;
            font-size: 10px;
            margin: 2px 0;
        }
        .footer-note {
            font-size: 10px;
            color: #9ca3af;
            text-align: center;
            margin-top: 4px;
        }
        p {
            margin: 2px 0;
        }
    </style>
</head>
<body>
    <div class="text-center" style="margin-bottom: 6px;">
        <div class="store-title">{{ optional($sellPhone->businessUnit)->store_title ?? 'Z-POS STORE' }}</div>
        <div class="store-sub">{{ optional($sellPhone->businessUnit)->address ?? 'Toko' }}</div>
        <div class="store-sub">{{ optional($sellPhone->created_at)->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i') }}</div>
    </div>

    <div class="divider"></div>

    <div class="info-p">No Transaksi : SPL-{{ $sellPhone->id }}</div>
    @if(!empty($sellPhone->invoice_number))
        <div class="info-p">No Invoice   : {{ $sellPhone->invoice_number }}</div>
    @endif
    <div class="info-p">Frontliner   : {{ optional($sellPhone->handledBy)->name ?? '-' }}</div>
    <div class="info-p">Pelanggan    : {{ optional($sellPhone->user)->name ?? '-' }}</div>
    <div class="info-p">No. HP       : {{ optional(optional($sellPhone->user)->profile)->phone_number ?? '-' }}</div>

    <div class="divider"></div>

    <div class="section-title">DATA PERANGKAT</div>

    <div class="info-p">Merek/Model: {{ $sellPhone->phone_brand }} {{ $sellPhone->phone_model }}</div>
    <div class="info-p">Kapasitas  : {{ $sellPhone->phone_ram ?? '-' }} / {{ $sellPhone->phone_storage ?? '-' }}</div>
    <div class="info-p">IMEI/SN    : {{ $sellPhone->imei ?? '-' }}</div>

    <div class="divider"></div>

    <table class="kv-table">
        <tr>
            <td class="deal-label">NILAI KESEPAKATAN</td>
            <td class="deal-value">Rp {{ number_format($sellPhone->appraised_value ?? 0, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="status-label">STATUS TRANSAKSI</td>
            <td class="status-value">{{ strtoupper(str_replace('_', ' ', $sellPhone->status ?? '')) }}</td>
        </tr>
    </table>

    <div class="divider"></div>

    <div class="guarantee-box">
        <div class="guarantee-title">** JAMINAN PENYERAHAN UNIT **</div>
        <p>Struk ini adalah bukti sah penyerahan perangkat ke toko.</p>
        <p>Pembayaran akan ditransfer ke rekening:</p>
        @php
            $userBank = $sellPhone->bank_name 
                ? (object)['bank_name' => $sellPhone->bank_name, 'account_number' => $sellPhone->bank_account_number, 'account_name' => $sellPhone->bank_account_name]
                : ($sellPhone->user && $sellPhone->user->bankAccounts->first() ? $sellPhone->user->bankAccounts->first() : null);
        @endphp
        @if ($userBank)
            <p class="bank-info" style="margin-top: 4px;">{{ $userBank->bank_name }} - {{ $userBank->account_number }}</p>
            <p class="bank-info">A/N: {{ $userBank->account_name }}</p>
        @else
            <p class="bank-info" style="margin-top: 4px;">Rekening Belum Diinput</p>
        @endif
        <p style="margin-top: 6px;">Simpan struk ini sampai dana berhasil masuk.</p>
        <p style="margin-top: 3px;">Terima kasih telah menjual HP Anda di {{ optional($sellPhone->businessUnit)->store_title ?? 'Z-POS STORE' }}.</p>
    </div>

    <div class="divider"></div>
    <div class="footer-note">*** TANDA TERIMA ***</div>
</body>
</html>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Struk Tanda Terima</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            color: #000;
            margin: 0;
            padding: 0;
            width: 80mm;
            line-height: 1.4;
        }
        .container {
            padding: 5px;
        }
        .text-center {
            text-align: center;
        }
        .font-bold {
            font-weight: bold;
        }
        .text-sm {
            font-size: 14px;
        }
        .text-xs {
            font-size: 10px;
        }
        .text-gray {
            color: #555;
        }
        .mb-1 { margin-bottom: 5px; }
        .mb-2 { margin-bottom: 10px; }
        .mt-1 { margin-top: 5px; }
        .mt-2 { margin-top: 10px; }
        .my-2 { margin-top: 10px; margin-bottom: 10px; }
        .border-t-dashed {
            border-top: 1px dashed #000;
        }
        .flex {
            display: table;
            width: 100%;
        }
        .flex span {
            display: table-cell;
        }
        .flex span:last-child {
            text-align: right;
        }
        .uppercase {
            text-transform: uppercase;
        }
        p {
            margin: 2px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="text-center mb-2">
            <p class="font-bold text-sm">{{ optional($sellPhone->businessUnit)->store_title ?? 'Z-POS STORE' }}</p>
            <p class="text-xs text-gray">{{ optional($sellPhone->businessUnit)->address ?? 'Toko' }}</p>
            <p class="text-xs text-gray">{{ $sellPhone->created_at->format('d/m/Y H:i') }}</p>
        </div>
        
        <div class="border-t-dashed my-2"></div>
        
        <p class="text-xs text-gray">No Transaksi : SPL-{{ $sellPhone->id }}</p>
        <p class="text-xs text-gray">Frontliner   : {{ optional($sellPhone->handledBy)->name ?? '-' }}</p>
        @if($sellPhone->salesBy)
        <p class="text-xs text-gray">Sales        : {{ $sellPhone->salesBy->name }} ({{ $sellPhone->salesBy->employee_no ?? '-' }})</p>
        @endif
        <p class="text-xs text-gray">Pelanggan    : {{ optional($sellPhone->user)->name ?? '-' }}</p>
        <p class="text-xs text-gray">No. HP       : {{ optional(optional($sellPhone->user)->profile)->phone_number ?? '-' }}</p>
        
        <div class="border-t-dashed my-2"></div>
        
        <div class="text-center font-bold mb-2 text-xs">DATA PERANGKAT</div>
        
        <p class="text-xs text-gray">Merek/Model: {{ $sellPhone->phone_brand }} {{ $sellPhone->phone_model }}</p>
        <p class="text-xs text-gray">Kapasitas  : {{ $sellPhone->phone_ram ?? '-' }} / {{ $sellPhone->phone_storage ?? '-' }}</p>
        <p class="text-xs text-gray">IMEI/SN    : {{ $sellPhone->imei ?? '-' }}</p>
        
        <div class="border-t-dashed my-2"></div>
        
        <div class="flex font-bold text-xs mb-1">
            <span>NILAI KESEPAKATAN</span>
            <span>Rp {{ number_format($sellPhone->appraised_value, 0, ',', '.') }}</span>
        </div>
        <div class="flex text-xs">
            <span>STATUS TRANSAKSI</span>
            <span class="uppercase font-bold">{{ str_replace('_', ' ', $sellPhone->status) }}</span>
        </div>
        
        <div class="border-t-dashed my-2"></div>
        
        <div class="text-center text-xs text-gray">
            <p class="font-bold mb-1" style="color:#000;">** JAMINAN PENYERAHAN UNIT **</p>
            <p>Struk ini adalah bukti sah penyerahan perangkat ke toko.</p>
            <p>Pembayaran akan ditransfer ke rekening:</p>
            @if ($sellPhone->user && $sellPhone->user->bankAccounts->first())
                <p class="font-bold mt-1" style="color:#000;">{{ $sellPhone->user->bankAccounts->first()->bank_name }} - {{ $sellPhone->user->bankAccounts->first()->account_number }}</p>
                <p class="font-bold" style="color:#000;">A/N: {{ $sellPhone->user->bankAccounts->first()->account_name }}</p>
            @else
                <p class="font-bold mt-1" style="color:#000;">Rekening Belum Diinput</p>
            @endif
            <p class="mt-2">Simpan struk ini sampai dana berhasil masuk.</p>
            <p class="mt-1">Terima kasih telah menjual HP Anda di {{ optional($sellPhone->businessUnit)->store_title ?? 'Z-POS STORE' }}.</p>
        </div>
        
        <div class="border-t-dashed my-2"></div>
        <div class="text-center text-xs text-gray">*** TANDA TERIMA ***</div>
    </div>
</body>
</html>

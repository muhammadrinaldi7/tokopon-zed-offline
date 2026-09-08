<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Berita Acara Stock Opname - {{ $opname->opname_number }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 9pt;
            color: #222;
            margin: 0;
            padding: 15px;
            line-height: 1.4;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #222;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .header h1 {
            margin: 0;
            font-size: 15pt;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .header h2 {
            margin: 4px 0 0 0;
            font-size: 11pt;
            font-weight: normal;
            color: #555;
        }

        .header .sub {
            font-size: 8pt;
            color: #777;
            margin-top: 3px;
        }

        .meta-table {
            width: 100%;
            margin-bottom: 15px;
            border-collapse: collapse;
        }

        .meta-table td {
            padding: 3px 6px;
            font-size: 8.5pt;
            vertical-align: top;
        }

        .meta-table .label {
            width: 18%;
            font-weight: bold;
            color: #444;
        }

        .meta-table .colon {
            width: 2%;
        }

        .meta-table .value {
            width: 30%;
        }

        .kpi-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .kpi-table td {
            width: 20%;
            border: 1px solid #ccc;
            padding: 6px 8px;
            text-align: center;
            background: #f9f9f9;
        }

        .kpi-table .kpi-title {
            font-size: 7pt;
            text-transform: uppercase;
            color: #666;
            font-weight: bold;
            display: block;
        }

        .kpi-table .kpi-value {
            font-size: 11pt;
            font-weight: bold;
            margin-top: 2px;
            display: block;
        }

        .section-title {
            font-size: 9.5pt;
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 1px solid #bbb;
            padding-bottom: 3px;
            margin-top: 15px;
            margin-bottom: 8px;
            color: #333;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            font-size: 8pt;
        }

        table.data-table th {
            background: #ececec;
            border: 1px solid #bbb;
            padding: 5px 6px;
            text-align: left;
            font-size: 7.5pt;
            text-transform: uppercase;
        }

        table.data-table td {
            border: 1px solid #ddd;
            padding: 4px 6px;
            vertical-align: top;
        }

        table.data-table tr:nth-child(even) {
            background: #fafafa;
        }

        .text-center { text-align: center !important; }
        .text-right { text-align: right !important; }
        .font-mono { font-family: monospace; }
        .font-bold { font-weight: bold; }
        .text-red { color: #b91c1c; }
        .text-amber { color: #b45309; }
        .text-green { color: #15803d; }

        .notes-box {
            border: 1px solid #ddd;
            background: #fdfdfd;
            padding: 8px 10px;
            font-size: 8.5pt;
            margin-bottom: 25px;
            border-radius: 4px;
        }

        .signatures {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
            page-break-inside: avoid;
        }

        .signatures td {
            width: 33.3%;
            text-align: center;
            vertical-align: top;
            font-size: 8.5pt;
        }

        .sign-space {
            height: 55px;
        }

        .sign-name {
            font-weight: bold;
            text-decoration: underline;
        }

        .sign-title {
            font-size: 7.5pt;
            color: #555;
            margin-top: 2px;
        }
    </style>
</head>
<body>

    {{-- Kop Header --}}
    <div class="header">
        <h1>{{ $opname->businessUnit->name ?? 'TOKOPON' }}</h1>
        <h2>BERITA ACARA STOCK OPNAME CABANG</h2>
        <div class="sub">
            Nomor: <strong class="font-mono">{{ $opname->opname_number }}</strong> | 
            Dicetak: {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>

    {{-- Meta Informasi --}}
    <table class="meta-table">
        <tr>
            <td class="label">Cabang</td>
            <td class="colon">:</td>
            <td class="value font-bold">{{ $opname->branch->name ?? '-' }}</td>

            <td class="label">Pelaksana (BM)</td>
            <td class="colon">:</td>
            <td class="value">{{ $opname->user->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Gudang Toko</td>
            <td class="colon">:</td>
            <td class="value">{{ $opname->warehouse->name ?? '-' }}</td>

            <td class="label">Waktu Pelaksanaan</td>
            <td class="colon">:</td>
            <td class="value">{{ $opname->start_time->format('d/m/Y H:i') }} s/d {{ $opname->end_time ? $opname->end_time->format('H:i') : 'Selesai' }}</td>
        </tr>
        <tr>
            <td class="label">Cakupan Audit</td>
            <td class="colon">:</td>
            <td class="value">
                @if($opname->type === 'ALL') Semua Produk (HP & Aksesoris)
                @elseif($opname->type === 'SERIALIZED_ONLY') Khusus Unit HP (IMEI)
                @elseif($opname->type === 'NON_SERIALIZED_ONLY') Khusus Aksesoris
                @else Kategori: {{ $opname->category_filter }}
                @endif
            </td>

            <td class="label">Status Pengesahan</td>
            <td class="colon">:</td>
            <td class="value font-bold">
                @if($opname->status === 'COMPLETED' || $opname->status === 'APPROVED') DISAHKAN / SELESAI
                @elseif($opname->status === 'PENDING_APPROVAL') MENUNGGU PERSETUJUAN MANAJEMEN
                @elseif($opname->status === 'REJECTED') DITOLAK
                @else SEDANG DIHITUNG
                @endif
            </td>
        </tr>
    </table>

    {{-- Rekapitulasi KPI --}}
    <table class="kpi-table">
        <tr>
            <td>
                <span class="kpi-title">Stok Buku</span>
                <span class="kpi-value font-mono">{{ number_format($opname->total_system_qty) }}</span>
            </td>
            <td>
                <span class="kpi-title">Fisik Riil</span>
                <span class="kpi-value font-mono">{{ number_format($opname->total_physical_qty) }}</span>
            </td>
            <td>
                <span class="kpi-title">Selisih Fisik</span>
                <span class="kpi-value font-mono {{ $opname->total_difference_qty == 0 ? 'text-green' : ($opname->total_difference_qty < 0 ? 'text-red' : 'text-amber') }}">
                    {{ $opname->total_difference_qty > 0 ? '+' : '' }}{{ number_format($opname->total_difference_qty) }}
                </span>
            </td>
            <td>
                <span class="kpi-title">Kerugian HPP (Loss)</span>
                <span class="kpi-value font-mono text-red">
                    Rp {{ number_format($opname->total_loss_value, 0, ',', '.') }}
                </span>
            </td>
            <td>
                <span class="kpi-title">Surplus HPP</span>
                <span class="kpi-value font-mono text-amber">
                    Rp {{ number_format($opname->total_surplus_value, 0, ',', '.') }}
                </span>
            </td>
        </tr>
    </table>

    {{-- Daftar Rincian Selisih Barang --}}
    <div class="section-title">I. Daftar Produk Selisih Fisik vs Buku</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 16%;">SKU</th>
                <th style="width: 38%;">Nama Produk</th>
                <th style="width: 8%;" class="text-center">Buku</th>
                <th style="width: 8%;" class="text-center">Fisik</th>
                <th style="width: 8%;" class="text-center">Selisih</th>
                <th style="width: 18%;" class="text-right">Total Nilai Selisih</th>
            </tr>
        </thead>
        <tbody>
            @forelse($discrepancyItems as $idx => $item)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="font-mono">{{ $item->item_no }}</td>
                    <td>{{ $item->product_name }}</td>
                    <td class="text-center font-mono">{{ $item->system_qty }}</td>
                    <td class="text-center font-mono font-bold">{{ $item->physical_qty }}</td>
                    <td class="text-center font-mono font-bold {{ $item->difference_qty < 0 ? 'text-red' : 'text-amber' }}">
                        {{ $item->difference_qty > 0 ? '+' : '' }}{{ $item->difference_qty }}
                    </td>
                    <td class="text-right font-mono font-bold {{ $item->difference_value < 0 ? 'text-red' : 'text-amber' }}">
                        {{ $item->difference_value < 0 ? '-' : '+' }} Rp {{ number_format(abs($item->difference_value), 0, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 12px; color: #15803d; font-weight: bold;">
                        ✓ Tidak ada selisih kuantitas. Seluruh stok fisik 100% cocok dengan catatan buku.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Rincian IMEI Missing (Jika Ada) --}}
    @if($missingSerials->isNotEmpty())
        <div class="section-title" style="color: #b91c1c;">II. Rincian IMEI / Serial Number yang Hilang (Missing)</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 25%;">Nomor IMEI / Seri</th>
                    <th style="width: 45%;">Nama Produk</th>
                    <th style="width: 25%;" class="text-right">HPP Unit</th>
                </tr>
            </thead>
            <tbody>
                @foreach($missingSerials as $sIdx => $sn)
                    <tr>
                        <td class="text-center">{{ $sIdx + 1 }}</td>
                        <td class="font-mono font-bold text-red">{{ $sn->serial_number }}</td>
                        <td>{{ $sn->stockOpnameItem->product_name ?? '-' }}</td>
                        <td class="text-right font-mono font-bold text-red">Rp {{ number_format($sn->hpp, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- Rincian IMEI Nyasar (Jika Ada) --}}
    @if($unexpectedSerials->isNotEmpty())
        <div class="section-title" style="color: #b45309;">III. Rincian IMEI / Serial Number Nyasar (Unexpected)</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 25%;">Nomor IMEI / Seri</th>
                    <th style="width: 35%;">Nama Produk</th>
                    <th style="width: 35%;">Catatan Asal / Investigasi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($unexpectedSerials as $uIdx => $uSn)
                    <tr>
                        <td class="text-center">{{ $uIdx + 1 }}</td>
                        <td class="font-mono font-bold text-amber">{{ $uSn->serial_number }}</td>
                        <td>{{ $uSn->stockOpnameItem->product_name ?? '-' }}</td>
                        <td>{{ $uSn->notes ?? 'Fisik ada saat scan, tidak terdaftar di cabang ini.' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- Catatan Berita Acara --}}
    <div class="section-title">Keterangan Berita Acara (Penjelasan BM):</div>
    <div class="notes-box">
        {!! nl2br(e($opname->notes ?: 'Tidak ada catatan keterangan khusus. Hasil audit stok telah diverifikasi oleh pelaksana.')) !!}
    </div>

    {{-- Kolom Tanda Tangan --}}
    <table class="signatures">
        <tr>
            <td>
                Dibuat Oleh,<br>
                <strong>Branch Manager (BM)</strong>
                <div class="sign-space"></div>
                <div class="sign-name">{{ $opname->user->name ?? '( ...................................... )' }}</div>
                <div class="sign-title">Cabang {{ $opname->branch->name ?? '' }}</div>
            </td>
            <td>
                Diperiksa Oleh,<br>
                <strong>Supervisor / Auditor</strong>
                <div class="sign-space"></div>
                <div class="sign-name">( ...................................... )</div>
                <div class="sign-title">Tim Audit Internal</div>
            </td>
            <td>
                Disahkan Oleh,<br>
                <strong>Manager Operasional / Direktur</strong>
                <div class="sign-space"></div>
                <div class="sign-name">( ...................................... )</div>
                <div class="sign-title">Manajemen Pusat</div>
            </td>
        </tr>
    </table>

</body>
</html>

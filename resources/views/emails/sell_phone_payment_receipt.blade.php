<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bukti Pembayaran Jual HP - SPL-{{ $sellPhone->id }}</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f4f5f7; color: #334155; line-height: 1.6; margin: 0; padding: 24px 12px;">
    @php
        $storeTitle = optional($sellPhone->businessUnit)->store_title ?? 'TOKOPON';
        $userBank = $sellPhone->user?->bankAccounts?->first();
        $bankName = $sellPhone->bank_name ?: ($userBank?->bank_name ?? '-');
        $bankNumber = $sellPhone->bank_account_number ?: ($userBank?->account_number ?? '-');
        $bankOwner = $sellPhone->bank_account_name ?: ($userBank?->account_name ?? '-');
    @endphp

    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
        {{-- Header --}}
        <div style="background: linear-gradient(135deg, #4E44DB 0%, #3730a3 100%); padding: 28px 24px; text-align: center; color: #ffffff;">
            <p style="margin: 0 0 6px 0; font-size: 11px; font-weight: 800; letter-spacing: 2px; text-transform: uppercase; color: #c7d2fe;">{{ $storeTitle }}</p>
            <h1 style="margin: 0; font-size: 22px; font-weight: 800; letter-spacing: -0.5px;">Bukti Pembayaran Transfer</h1>
            <p style="margin: 6px 0 0 0; font-size: 13px; color: #e0e7ff;">No. Transaksi: <strong>SPL-{{ $sellPhone->id }}</strong></p>
        </div>

        {{-- Body Content --}}
        <div style="padding: 24px;">
            <p style="font-size: 15px; margin-top: 0;">Halo <strong>{{ optional($sellPhone->user)->name ?? 'Pelanggan Setia' }}</strong>,</p>
            <p style="font-size: 14px; color: #475569; margin-bottom: 20px;">
                Kabar baik! Pembayaran untuk transaksi penjualan HP Anda telah <strong>berhasil dicairkan dan ditransfer</strong> ke rekening tujuan Anda.
            </p>

            {{-- Card Total Nominal --}}
            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 18px; text-align: center; margin-bottom: 24px;">
                <span style="display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #15803d; margin-bottom: 4px;">Total Dana Ditransfer</span>
                <span style="display: block; font-size: 26px; font-weight: 900; color: #16a34a;">
                    Rp {{ number_format($sellPhone->appraised_value, 0, ',', '.') }}
                </span>
                <span style="display: inline-block; background: #dcfce7; color: #166534; font-size: 11px; font-weight: 800; padding: 4px 10px; border-radius: 9999px; margin-top: 8px;">
                    ✓ Status: Transfer Berhasil
                </span>
            </div>

            {{-- Detail Perangkat & Rekening --}}
            <table style="width: 100%; border-collapse: collapse; font-size: 13px; margin-bottom: 24px;">
                <tr>
                    <td style="padding: 10px 0; border-bottom: 1px solid #f1f5f9; color: #64748b; width: 40%;">Perangkat</td>
                    <td style="padding: 10px 0; border-bottom: 1px solid #f1f5f9; color: #1e293b; font-weight: 700; text-align: right;">
                        {{ $sellPhone->phone_brand }} {{ $sellPhone->phone_model }}
                    </td>
                </tr>
                <tr>
                    <td style="padding: 10px 0; border-bottom: 1px solid #f1f5f9; color: #64748b;">Spesifikasi</td>
                    <td style="padding: 10px 0; border-bottom: 1px solid #f1f5f9; color: #1e293b; font-weight: 600; text-align: right;">
                        {{ $sellPhone->phone_ram ? $sellPhone->phone_ram . ' RAM' : '-' }} • {{ $sellPhone->phone_storage ?? '-' }}
                    </td>
                </tr>
                @if ($sellPhone->inspections->isNotEmpty() && $sellPhone->inspections->first()->imei)
                <tr>
                    <td style="padding: 10px 0; border-bottom: 1px solid #f1f5f9; color: #64748b;">IMEI</td>
                    <td style="padding: 10px 0; border-bottom: 1px solid #f1f5f9; color: #1e293b; font-family: monospace; font-weight: 600; text-align: right;">
                        {{ $sellPhone->inspections->first()->imei }}
                    </td>
                </tr>
                @endif
                <tr>
                    <td style="padding: 10px 0; border-bottom: 1px solid #f1f5f9; color: #64748b;">Bank Tujuan</td>
                    <td style="padding: 10px 0; border-bottom: 1px solid #f1f5f9; color: #1e293b; font-weight: 700; text-align: right;">
                        {{ $bankName }}
                    </td>
                </tr>
                <tr>
                    <td style="padding: 10px 0; border-bottom: 1px solid #f1f5f9; color: #64748b;">Nomor Rekening</td>
                    <td style="padding: 10px 0; border-bottom: 1px solid #f1f5f9; color: #1e293b; font-family: monospace; font-weight: 700; text-align: right;">
                        {{ $bankNumber }}
                    </td>
                </tr>
                <tr>
                    <td style="padding: 10px 0; border-bottom: 1px solid #f1f5f9; color: #64748b;">Atas Nama</td>
                    <td style="padding: 10px 0; border-bottom: 1px solid #f1f5f9; color: #1e293b; font-weight: 700; text-align: right;">
                        {{ $bankOwner }}
                    </td>
                </tr>
                @if ($sellPhone->store_bank_no)
                <tr>
                    <td style="padding: 10px 0; color: #64748b;">Rekening Pengirim Toko</td>
                    <td style="padding: 10px 0; color: #1e293b; font-weight: 600; text-align: right;">
                        {{ $sellPhone->store_bank_no }}
                    </td>
                </tr>
                @endif
            </table>

            {{-- Catatan Lampiran --}}
            <div style="background: #f8fafc; border-left: 4px solid #4E44DB; padding: 12px 16px; border-radius: 6px; font-size: 13px; color: #475569; margin-bottom: 20px;">
                📎 <strong>Bukti Transfer Asli</strong> telah dilampirkan pada email ini sebagai dokumen resmi transaksi Anda.
            </div>

            <p style="font-size: 13px; color: #64748b; margin-bottom: 4px;">
                Terima kasih telah mempercayakan transaksi jual HP Anda kepada <strong>{{ $storeTitle }}</strong>.
            </p>
        </div>

        {{-- Footer --}}
        <div style="background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 18px 24px; text-align: center; font-size: 12px; color: #94a3b8;">
            <p style="margin: 0 0 4px 0; font-weight: 700; color: #64748b;">{{ $storeTitle }}</p>
            <p style="margin: 0;">Email ini dibuat otomatis oleh sistem. Harap simpan email ini sebagai arsip transaksi.</p>
        </div>
    </div>
</body>
</html>

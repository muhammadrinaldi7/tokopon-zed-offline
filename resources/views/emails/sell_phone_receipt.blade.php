<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: Arial, sans-serif; color: #333; line-height: 1.6; margin: 0; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #ddd;">
        <h2 style="color: #4F46E5; margin-bottom: 15px;">Struk Transaksi Pembelian HP</h2>
        <p>Halo <strong>{{ optional($sellPhone->user)->name ?? 'Customer' }}</strong>,</p>
        <p>Terima kasih telah melakukan transaksi di <strong>{{ optional($sellPhone->businessUnit)->store_title ?? 'Z-POS STORE' }}</strong>.</p>
        <p>Bersama email ini, kami lampirkan Struk Tanda Terima (PDF) sebagai bukti sah penyerahan perangkat Anda kepada kami.</p>
        
        <div style="background: #f9f9f9; padding: 15px; border-radius: 6px; margin: 20px 0;">
            <p style="margin: 0;"><strong>No Transaksi:</strong> SPL-{{ $sellPhone->id }}</p>
            <p style="margin: 5px 0;"><strong>Perangkat:</strong> {{ $sellPhone->phone_brand }} {{ $sellPhone->phone_model }}</p>
            <p style="margin: 0;"><strong>Nilai Kesepakatan:</strong> Rp {{ number_format($sellPhone->appraised_value, 0, ',', '.') }}</p>
        </div>

        <p>Simpan tanda terima ini sebagai bukti transaksi sampai dana Anda berhasil dicairkan.</p>
        <br>
        <p>Salam hangat,</p>
        <p><strong>Tim {{ optional($sellPhone->businessUnit)->store_title ?? 'Z-POS STORE' }}</strong></p>
    </div>
</body>
</html>

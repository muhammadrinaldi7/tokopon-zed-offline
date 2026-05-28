<!DOCTYPE html>
<html>

<head>
    <title>Aktivasi Akun Kasir</title>
</head>

<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <h2>Halo, {{ $name }}!</h2>
    <p>Akun kasir Anda telah berhasil didaftarkan ke dalam sistem. Demi menjaga keamanan data, Anda diwajibkan untuk
        membuat password baru sebelum dapat menggunakan sistem.</p>

    <table style="border: none; margin-bottom: 25px;">
        <tr>
            <td style="padding: 5px 15px 5px 0;"><strong>Username (Email)</strong></td>
            <td>: {{ $email }}</td>
        </tr>
        <tr>
            <td style="padding: 5px 15px 5px 0;"><strong>Status Akun</strong></td>
            <td>: <span style="color: #d9534f; font-weight: bold;">Belum Aktif (Perlu Atur Password)</span></td>
        </tr>
    </table>

    <!-- Tombol Utama untuk Buat Password via Forgot Password -->
    <p>Silakan klik tombol di bawah ini untuk membuat password baru dan mengaktifkan akun Anda:</p>
    <p style="margin-top: 15px; margin-bottom: 25px;">
        <a href="{{ url('/forgot-password') }}"
            style="background-color: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;">
            Atur Password & Aktifkan Akun
        </a>
    </p>

    <p style="font-size: 0.9em; color: #666;">
        Jika tombol di atas tidak berfungsi, silakan salin dan tempel tautan berikut ke browser Anda:<br>
        <a href="{{ url('/forgot-password') }}" style="color: #0066cc;">{{ url('/forgot-password') }}</a>
    </p>

    <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">

    <p>Terima Kasih,<br><strong>Programmer ZED GROUP</strong></p>
</body>

</html>

<!DOCTYPE html>
<html>

<head>
    <title>Akun Login Kasir</title>
</head>

<body>
    <h2>Halo, {{ $name }}!</h2>
    <p>Akun kasir Anda telah berhasil didaftarkan ke dalam sistem. Berikut adalah detail kredensial untuk login:</p>

    <table style="border: none;">
        <tr>
            <td><strong>URL Login</strong></td>
            <td>: {{ url('/login') }}</td>
        </tr>
        <tr>
            <td><strong>Username (Email)</strong></td>
            <td>: {{ $email }}</td>
        </tr>
        <tr>
            <td><strong>Password</strong></td>
            <td>: <code>{{ $password }}</code></td>
        </tr>
    </table>

    <p>Demi keamanan, mohon segera ganti password Anda setelah berhasil login pertama kali.</p>
    <br>
    <p>Terima Kasih,<br>Programmer</p>
</body>

</html>

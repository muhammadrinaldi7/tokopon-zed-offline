<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('mail:test-mailtrap {email=test@example.com}', function (string $email) {
    $this->info("Menyiapkan pengujian pengiriman email POS via Mailtrap SMTP...");
    $this->line("Host: " . config('mail.mailers.pos_sales.host'));
    $this->line("Port: " . config('mail.mailers.pos_sales.port'));
    $this->line("From: " . config('mail.mailers.pos_sales.from.address'));
    $this->line("To  : " . $email);

    if (config('mail.mailers.pos_sales.password') === '<YOUR_API_TOKEN>' || empty(config('mail.mailers.pos_sales.password'))) {
        $this->warn("⚠️ Perhatian: Nilai MAIL_POS_PASSWORD di .env masih placeholder atau kosong. Pastikan sudah mengisi API Token Mailtrap Anda.");
    }

    try {
        \Illuminate\Support\Facades\Mail::mailer('pos_sales')->raw(
            "Halo!\n\nIni adalah email uji coba untuk memverifikasi integrasi Mailtrap SMTP pada fitur POS TokoPun.\n\nDikirim pada: " . now()->toDateTimeString(),
            function ($message) use ($email) {
                $message->to($email)
                    ->subject('Test Integrasi Mailtrap POS - ' . config('app.name'));
            }
        );

        $this->info("✅ Email pengujian BERHASIL dikirim!");
        $this->comment("👉 Cek status pengiriman di: https://mailtrap.io/sending/email_logs");
    } catch (\Throwable $e) {
        $this->error("❌ Gagal mengirim email: " . $e->getMessage());
        $this->line("Jika terjadi error otentikasi, periksa kembali API Token di MAIL_POS_PASSWORD pada file .env");
    }
})->purpose('Menguji koneksi pengiriman email POS via Mailtrap SMTP');

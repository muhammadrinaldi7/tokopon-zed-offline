<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class QzTrayController extends Controller
{
    /**
     * Endpoint penandatanganan (signing) pesan request untuk QZ Tray
     * Menggunakan private key yang disimpan di storage/app/private/private-key.pem
     */
    public function sign(Request $request)
    {
        $toSign = $request->input('request');

        if (!$toSign) {
            return response('Parameter "request" tidak ditemukan.', 400);
        }

        $keyPath = storage_path('app/private/private-key.pem');

        // Fallback jika tidak ada di app/private, coba cek di app/qz
        if (!file_exists($keyPath)) {
            $keyPath = storage_path('app/qz/private-key.pem');
        }

        if (!file_exists($keyPath)) {
            Log::error('QZ Tray Signing Error: File private-key.pem tidak ditemukan.');
            return response('File private key tidak ditemukan.', 500);
        }

        $privateKeyContent = file_get_contents($keyPath);
        $privateKey = openssl_pkey_get_private($privateKeyContent);

        if (!$privateKey) {
            Log::error('QZ Tray Signing Error: Format private key tidak valid.');
            return response('Private key tidak valid.', 500);
        }

        $signature = '';
        // QZ Tray 2.1+ secara default menggunakan SHA512
        $success = openssl_sign($toSign, $signature, $privateKey, OPENSSL_ALGO_SHA512);

        if (!$success) {
            Log::error('QZ Tray Signing Error: Gagal menandatangani data dengan OpenSSL.');
            return response('Gagal menandatangani data.', 500);
        }

        return response(base64_encode($signature), 200, [
            'Content-Type' => 'text/plain',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }
}

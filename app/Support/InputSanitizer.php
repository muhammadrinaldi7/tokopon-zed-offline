<?php

namespace App\Support;

class InputSanitizer
{
    /**
     * Deteksi apakah string mengandung tag HTML, event handler JS, atau payload XSS berbahaya.
     */
    public static function containsHtmlOrScript(?string $value): bool
    {
        if ($value === null || trim($value) === '') {
            return false;
        }

        // Deteksi tag HTML (misal <img ...>, <script>, <svg>, <iframe>, <a>, dll.)
        if (preg_match('/<\s*\/?\s*[a-zA-Z!][^>]*>/', $value) === 1) {
            return true;
        }

        // Deteksi protokol berbahaya atau inline JS event handler
        if (preg_match('/(?:javascript\s*:|vbscript\s*:|data\s*:\s*text\/html|\bon[a-z]+\s*=)/i', $value) === 1) {
            return true;
        }

        return false;
    }

    /**
     * Bersihkan teks polos (nama, alamat, catatan): hapus tag HTML & karakter kontrol.
     */
    public static function cleanPlainText(?string $value, int $maxLength = 255): string
    {
        if ($value === null) {
            return '';
        }

        $clean = strip_tags($value);
        // Hapus karakter kontrol ASCII selain newline/tab jika diperlukan
        $clean = (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $clean);
        $clean = trim((string) preg_replace('/\s+/u', ' ', $clean));

        if ($maxLength > 0 && mb_strlen($clean) > $maxLength) {
            $clean = mb_substr($clean, 0, $maxLength);
        }

        return $clean;
    }

    /**
     * Bersihkan pesan chat: izinkan newline & emoji, namun buang tag HTML & pola XSS.
     */
    public static function cleanMessage(?string $value, int $maxLength = 2000): string
    {
        if ($value === null) {
            return '';
        }

        $clean = strip_tags($value);
        $clean = (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $clean);
        $clean = trim($clean);

        if ($maxLength > 0 && mb_strlen($clean) > $maxLength) {
            $clean = mb_substr($clean, 0, $maxLength);
        }

        return $clean;
    }
}

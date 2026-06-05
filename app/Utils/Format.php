<?php

namespace App\Utils;

class Format
{
    /**
     * Format angka menjadi format Rupiah
     *
     * @param float|int $angka
     * @param bool $withPrefix
     * @return string
     */
    public static function rupiah($angka, $withPrefix = true)
    {
        $hasil = number_format($angka, 0, ',', '.');
        return $withPrefix ? 'Rp. ' . $hasil : $hasil;
    }
}

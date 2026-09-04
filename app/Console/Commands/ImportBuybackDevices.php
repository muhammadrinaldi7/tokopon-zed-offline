<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\BuybackDevice;
use Illuminate\Console\Command;

class ImportBuybackDevices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-buyback-devices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrasi data baseprice device lama ke tabel baru';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai proses migrasi data Buyback Devices...');

        // 1. Baca file CSV (Pastikan file ada di storage/app/imports/old_devices.csv)
        $filePath = storage_path('app/imports/old_devices.csv');

        if (!file_exists($filePath)) {
            $this->error('File CSV tidak ditemukan di ' . $filePath);
            return;
        }

        // Buka file CSV
        $file = fopen($filePath, 'r');
        $header = fgetcsv($file); // Membaca baris pertama sebagai header

        $dataRows = [];
        while ($row = fgetcsv($file)) {
            $dataRows[] = array_combine($header, $row); // Gabungkan header jadi key array
        }
        fclose($file);

        // Buat Progress Bar di terminal biar keren seperti produksi betulan!
        $bar = $this->output->createProgressBar(count($dataRows));
        $bar->start();

        // 2. Loop datanya dan Transformasikan (Mapping)
        foreach ($dataRows as $row) {

            // --- A. Mapping Brand (Merek) ---
            $brandName = trim($row['merk'] ?? '');
            if (empty($brandName)) {
                $bar->advance();
                continue; // Lewati jika tidak ada merk
            }

            $brand = Brand::whereNull('business_unit_id')
                ->whereRaw('LOWER(name) = ?', [strtolower(trim($brandName))])
                ->first();
            if (!$brand) {
                $brand = Brand::create(['name' => trim($brandName), 'business_unit_id' => null]);
            }

            // --- B. Ambil Data Lainnya ---
            $modelName = trim($row['nama'] ?? '');
            $storage = trim($row['storage'] ?? null);
            
            // Bersihkan format harga jika ada (misal ada titik atau huruf)
            $hargaRaw = $row['harga'] ?? 0;
            $basePrice = (float) preg_replace('/[^0-9]/', '', $hargaRaw);

            // --- C. Load / Masukkan ke Database Baru ---
            // Pakai updateOrCreate agar kalau script dijalankan 2x, datanya tidak dobel
            $device = BuybackDevice::updateOrCreate(
                [
                    // Kondisi pencarian (jangan sampai duplikat)
                    'brand_id' => $brand->id,
                    'model_name' => $modelName,
                    'storage' => $storage,
                ],
                [
                    // Data yang diupdate/diisi
                    'base_price' => $basePrice,
                    'is_active' => true,
                ]
            );

            // --- D. Tentukan Tier Berdasarkan Base Price ---
            // Menggunakan method bawaan model untuk auto-assign tier
            $device->assignTierByPrice();

            $bar->advance(); // Majukan progress bar
        }

        $bar->finish();
        $this->newLine();
        $this->info('Yeay! Migrasi data selesai dengan sukses! 🎉');
    }
}

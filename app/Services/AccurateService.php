<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AccurateService
{
    /**
     * Hit Accurate Online API to save User as Vendor
     * 
     * @param User $user
     * @return void
     * @throws \Exception
     */
    public function syncVendor(User $user)
    {
        // Jika sudah punya vendor ID, tidak perlu hit API lagi
        if ($user->accurate_vendor_id) {
            return;
        }

        // Ambil alamat primary
        $address = $user->addresses()->where('is_primary', true)->first();

        // Data yang akan dikirim ke Accurate
        $vendorData = [
            'name' => 'GSK_VENDOR_' . $user->profile->full_name,
            'vendorNo' => 'GSK_VENDOR_' . str_pad($user->id, 5, '0', STR_PAD_LEFT),
            // 'transDate' => date('d/m/Y'),
            'currencyCode' => 'IDR',
            'mobilePhone' => $user->profile->phone_number,
            'email' => $user->email,
            'npwpNo' => $user->npwp,
            'notes' => 'VENDOR GSK - NIK:' . $user->identity,
        ];

        // Opsional: Jika ada alamat, kirimkan juga
        if ($address) {
            $vendorData['billStreet'] = $address->full_address;
            $vendorData['billZipCode'] = $address->postal_code;
        }

        // 1. Siapkan Timestamp (Format ISO 8601 sangat disarankan)
        $timestamp = now()->toIso8601String();

        // 2. Ambil Secret Key dari .env
        $secretKey = env('ACCURATE_SECRET_KEY');

        // 3. Generate Signature: HMAC-SHA256 dari Timestamp menggunakan Secret Key
        $signature = hash_hmac('sha256', $timestamp, $secretKey);

        // CONTOH HIT API MENGGUNAKAN LARAVEL HTTP CLIENT:
        // Pastikan Anda sudah mengatur ACCURATE_HOST dan ACCURATE_TOKEN di .env Anda
        // dd($vendorData);
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('ACCURATE_TOKEN'),
            'X-Api-Timestamp' => $timestamp,
            'X-Api-Signature'  => $signature, // Jika menggunakan OAuth Accurate
            'Content-Type'  => 'application/json',
        ])->post(env('ACCURATE_HOST') . '/vendor/save.do', $vendorData);

        Log::info('API Accurate Success: ' . $response->body());
        if ($response->successful()) {
            $data = $response->json();
            // Simpan ID dari Accurate ke Database kita
            if (isset($data['r'])) {

                $result = $data['r']; // Ambil objek 'r'
                Log::info('data accurate : ' . json_encode($result));
                // Log::info('data tunggal : ' . json_encode($result['vendorNo']));
                $idAccurate = json_encode($result['id']);        // Hasilnya: 601
                $noVendor   = json_encode($result['vendorNo']);  // Hasilnya: "GSK_VENDOR_00002"
                // 2. Update database user
                // Log::info($idAccurate, $noVendor);
                $user->update([
                    'accurate_vendor_id' => $idAccurate,
                    'accurate_vendor_no' => $noVendor,
                ]);
            }
        } else {
            Log::info('API Accurate Error: ' . $response->body());
            throw new \Exception('API Accurate Error: ' . $response->body());
        }

        // MOCKUP SEMENTARA KARENA KITA BELUM PUNYA TOKEN ACCURATE:
        // Menyimulasikan response berhasil dari Accurate
        // $simulatedVendorId = rand(1000, 9999);
        // $simulatedVendorNo = 'V-' . str_pad($simulatedVendorId, 5, '0', STR_PAD_LEFT);

        // $user->update([
        //     'accurate_vendor_id' => $simulatedVendorId,
        //     'accurate_vendor_no' => $simulatedVendorNo,
        // ]);
    }
    public function getWarehouseList()
    {
        // 1. Siapkan Timestamp (Format ISO 8601 sangat disarankan)
        $timestamp = now()->toIso8601String();

        // 2. Ambil Secret Key dari .env
        $secretKey = env('ACCURATE_SECRET_KEY');

        // 3. Generate Signature: HMAC-SHA256 dari Timestamp menggunakan Secret Key
        $signature = hash_hmac('sha256', $timestamp, $secretKey);

        // CONTOH HIT API MENGGUNAKAN LARAVEL HTTP CLIENT:
        // Pastikan Anda sudah mengatur ACCURATE_HOST dan ACCURATE_TOKEN di .env Anda
        // dd($vendorData);
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('ACCURATE_TOKEN'),
            'X-Api-Timestamp' => $timestamp,
            'X-Api-Signature'  => $signature, // Jika menggunakan OAuth Accurate
            'Content-Type'  => 'application/json',
        ])->get(env('ACCURATE_HOST') . '/warehouse/list.do');

        Log::info('API Accurate Success: ' . $response->body());
        if ($response->successful()) {
            $data = $response->json();
            // Simpan ID dari Accurate ke Database kita
            if (isset($data)) {
                // dd($data[]);
                return $data;
            }
            return [];
        } else {
            Log::info('API Accurate Error: ' . $response->body());
            throw new \Exception('API Accurate Error: ' . $response->body());
        }
    }
    public function getBranchList()
    {
        // 1. Siapkan Timestamp (Format ISO 8601 sangat disarankan)
        $timestamp = now()->toIso8601String();

        // 2. Ambil Secret Key dari .env
        $secretKey = env('ACCURATE_SECRET_KEY');

        // 3. Generate Signature: HMAC-SHA256 dari Timestamp menggunakan Secret Key
        $signature = hash_hmac('sha256', $timestamp, $secretKey);

        // CONTOH HIT API MENGGUNAKAN LARAVEL HTTP CLIENT:
        // Pastikan Anda sudah mengatur ACCURATE_HOST dan ACCURATE_TOKEN di .env Anda
        // dd($vendorData);
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('ACCURATE_TOKEN'),
            'X-Api-Timestamp' => $timestamp,
            'X-Api-Signature'  => $signature, // Jika menggunakan OAuth Accurate
            'Content-Type'  => 'application/json',
        ])->get(env('ACCURATE_HOST') . '/branch/list.do');

        Log::info('API Accurate Success: ' . $response->body());
        if ($response->successful()) {
            $data = $response->json();
            // Simpan ID dari Accurate ke Database kita
            if (isset($data)) {
                // dd($data[]);
                return $data;
            }
            return [];
        } else {
            Log::info('API Accurate Error: ' . $response->body());
            throw new \Exception('API Accurate Error: ' . $response->body());
        }
    }
}

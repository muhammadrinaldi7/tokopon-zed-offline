<?php

namespace App\Services;

use App\Models\Employe;
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

    public function itemDetailDo($itemNo)
    {
        // 1. Siapkan Timestamp (Format ISO 8601 sangat disarankan)
        $timestamp = now()->toIso8601String();

        // 2. Generate Signature: HMAC-SHA256 dari Timestamp menggunakan Secret Key
        $signature = hash_hmac('sha256', $timestamp, env('ACCURATE_SECRET_KEY'));

        // CONTOH HIT API MENGGUNAKAN LARAVEL HTTP CLIENT:
        // Pastikan Anda sudah mengatur ACCURATE_HOST dan ACCURATE_TOKEN di .env Anda
        // dd($vendorData);
        $param = [
            "no" => $itemNo
        ];
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('ACCURATE_TOKEN'),
            'X-Api-Timestamp' => $timestamp,
            'X-Api-Signature'  => $signature, // Jika menggunakan OAuth Accurate
            'Content-Type'  => 'application/json',
        ])->get(env('ACCURATE_HOST') . '/item/detail.do', $param);

        Log::info('API Accurate Success: ' . $response->body());
        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['s']) && $data['s'] === false) {
                $errorMsg = isset($data['d']) && is_array($data['d']) ? implode(', ', $data['d']) : json_encode($data);
                throw new \Exception('API Accurate Error: ' . $errorMsg);
            }
            // Simpan ID dari Accurate ke Database kita
            if (isset($data)) {
                $result = $data['d']['detailOpenBalance'][0]['detailSerialNumber'];
                return $result;
            }
            return [];
        } else {
            Log::info('API Accurate Error: ' . $response->body());
            throw new \Exception('API Accurate Error: ' . $response->body());
        }
    }
    public function syncVendor(User $user, $databaseSource = 'syihab')
    {
        // Jika sudah punya vendor ID, tidak perlu hit API lagi
        if ($user->accurate_vendor_id || $user->accurate_vendor_no) {
            return;
        }

        $tokenSuffix = strtoupper($databaseSource) === 'SECOND' ? '_SECOND' : '';
        $host = env('ACCURATE_HOST' . $tokenSuffix, env('ACCURATE_HOST'));
        $token = env('ACCURATE_TOKEN' . $tokenSuffix, env('ACCURATE_TOKEN'));
        $secretKey = env('ACCURATE_SECRET_KEY' . $tokenSuffix, env('ACCURATE_SECRET_KEY'));

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

        // 2. Generate Signature: HMAC-SHA256 dari Timestamp menggunakan Secret Key
        $signature = hash_hmac('sha256', $timestamp, $secretKey);

        // CONTOH HIT API MENGGUNAKAN LARAVEL HTTP CLIENT:
        // Pastikan Anda sudah mengatur ACCURATE_HOST dan ACCURATE_TOKEN di .env Anda
        // dd($vendorData);
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'X-Api-Timestamp' => $timestamp,
            'X-Api-Signature'  => $signature, // Jika menggunakan OAuth Accurate
            'Content-Type'  => 'application/json',
        ])->post($host . '/vendor/save.do', $vendorData);

        Log::info('API Accurate Success: ' . $response->body());
        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['s']) && $data['s'] === false) {
                $errorMsg = isset($data['d']) && is_array($data['d']) ? implode(', ', $data['d']) : json_encode($data);
                throw new \Exception('API Accurate Error: ' . $errorMsg);
            }
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
            if (isset($data['s']) && $data['s'] === false) {
                $errorMsg = isset($data['d']) && is_array($data['d']) ? implode(', ', $data['d']) : json_encode($data);
                throw new \Exception('API Accurate Error: ' . $errorMsg);
            }
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
            if (isset($data['s']) && $data['s'] === false) {
                $errorMsg = isset($data['d']) && is_array($data['d']) ? implode(', ', $data['d']) : json_encode($data);
                throw new \Exception('API Accurate Error: ' . $errorMsg);
            }
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

    public function postPurchaseInvoice($purchaseInvoiceData, $databaseSource = 'syihab')
    {
        $tokenSuffix = strtoupper($databaseSource) === 'SECOND' ? '_SECOND' : '';

        $host = env('ACCURATE_HOST' . $tokenSuffix, env('ACCURATE_HOST'));
        $token = env('ACCURATE_TOKEN' . $tokenSuffix, env('ACCURATE_TOKEN'));
        $secretKey = env('ACCURATE_SECRET_KEY' . $tokenSuffix, env('ACCURATE_SECRET_KEY'));

        if (!$host || !$token) {
            throw new \Exception("Kredensial API Accurate untuk sumber '{$databaseSource}' belum diatur.");
        }

        // 1. Siapkan Timestamp (Format ISO 8601 sangat disarankan)
        $timestamp = now()->toIso8601String();

        // 3. Generate Signature: HMAC-SHA256 dari Timestamp menggunakan Secret Key
        $signature = hash_hmac('sha256', $timestamp, $secretKey);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'X-Api-Timestamp' => $timestamp,
            'X-Api-Signature'  => $signature, // Jika menggunakan OAuth Accurate
            'Content-Type'  => 'application/json',
        ])->post($host . '/purchase-invoice/save.do', $purchaseInvoiceData);

        Log::info('API Accurate Success: ' . $response->body());
        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['s']) && $data['s'] === false) {
                $errorMsg = isset($data['d']) && is_array($data['d']) ? implode(', ', $data['d']) : json_encode($data);
                throw new \Exception('API Accurate Error: ' . $errorMsg);
            }
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

    public function getItemList($databaseSource = 'syihab')
    {
        // Tentukan kredensial berdasarkan sumber database
        // Default (syihab) mengambil dari ACCURATE_TOKEN, sedangkan 'second' dari ACCURATE_TOKEN_SECOND
        $tokenSuffix = strtoupper($databaseSource) === 'SECOND' ? '_SECOND' : '';

        $host = env('ACCURATE_HOST' . $tokenSuffix, env('ACCURATE_HOST'));
        $token = env('ACCURATE_TOKEN' . $tokenSuffix, env('ACCURATE_TOKEN'));
        $secretKey = env('ACCURATE_SECRET_KEY' . $tokenSuffix, env('ACCURATE_SECRET_KEY'));

        if (!$host || !$token) {
            throw new \Exception("Kredensial API Accurate untuk sumber '{$databaseSource}' belum diatur.");
        }

        $timestamp = now()->toIso8601String();
        $signature = hash_hmac('sha256', $timestamp, $secretKey);
        $param = [
            "sp.pageSize" => 3000,
            "fields" => "no,unitPrice,availableToSell,itemBranchName",
        ];
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'X-Api-Timestamp' => $timestamp,
            'X-Api-Signature'  => $signature,
            'Content-Type'  => 'application/json',
        ])->get($host . '/item/list.do', $param);

        Log::info("API Accurate Get Item List ({$databaseSource}) Success: " . $response->body());
        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['s']) && $data['s'] === false) {
                $errorMsg = isset($data['d']) && is_array($data['d']) ? implode(', ', $data['d']) : json_encode($data);
                throw new \Exception('API Accurate Error: ' . $errorMsg);
            }
            if (isset($data)) {
                return $data;
            }
            return [];
        } else {
            Log::info("API Accurate Get Item List ({$databaseSource}) Error: " . $response->body());
            throw new \Exception('API Accurate Error: ' . $response->body());
        }
    }
    public function getItemListForBuyback($databaseSource = 'syihab')
    {
        // Tentukan kredensial berdasarkan sumber database
        // Default (syihab) mengambil dari ACCURATE_TOKEN, sedangkan 'second' dari ACCURATE_TOKEN_SECOND
        $tokenSuffix = strtoupper($databaseSource) === 'SECOND' ? '_SECOND' : '';

        $host = env('ACCURATE_HOST' . $tokenSuffix, env('ACCURATE_HOST'));
        $token = env('ACCURATE_TOKEN' . $tokenSuffix, env('ACCURATE_TOKEN'));
        $secretKey = env('ACCURATE_SECRET_KEY' . $tokenSuffix, env('ACCURATE_SECRET_KEY'));

        if (!$host || !$token) {
            throw new \Exception("Kredensial API Accurate untuk sumber '{$databaseSource}' belum diatur.");
        }

        $timestamp = now()->toIso8601String();
        $signature = hash_hmac('sha256', $timestamp, $secretKey);
        $param = [
            "fields" => "no,availableToSell,itemBranchName",
            "filter.keywords.op" => "CONTAIN",
            "filter.keywords.val" => "hp",
        ];
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'X-Api-Timestamp' => $timestamp,
            'X-Api-Signature'  => $signature,
            'Content-Type'  => 'application/json',
        ])->get($host . '/item/list.do', $param);

        Log::info("API Accurate Get Item List ({$databaseSource}) Success: " . $response->body());
        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['s']) && $data['s'] === false) {
                $errorMsg = isset($data['d']) && is_array($data['d']) ? implode(', ', $data['d']) : json_encode($data);
                throw new \Exception('API Accurate Error: ' . $errorMsg);
            }
            if (isset($data)) {
                return $data;
            }
            return [];
        } else {
            Log::info("API Accurate Get Item List ({$databaseSource}) Error: " . $response->body());
            throw new \Exception('API Accurate Error: ' . $response->body());
        }
    }

    public function syncCustomer(User $user, $databaseSource = 'syihab')
    {
        if ($user->accurate_customer_id) {
            return;
        }

        $tokenSuffix = strtoupper($databaseSource) === 'SECOND' ? '_SECOND' : '';
        $host = env('ACCURATE_HOST' . $tokenSuffix, env('ACCURATE_HOST'));
        $token = env('ACCURATE_TOKEN' . $tokenSuffix, env('ACCURATE_TOKEN'));
        $secretKey = env('ACCURATE_SECRET_KEY' . $tokenSuffix, env('ACCURATE_SECRET_KEY'));

        $address = $user->addresses()->where('is_primary', true)->first();

        $customerData = [
            'name' => 'GSK_CUSTOMER_' . $user->profile->full_name,
            'customerNo' => 'GSK_CUSTOMER_' . str_pad($user->id, 5, '0', STR_PAD_LEFT),
            'currencyCode' => 'IDR',
            'mobilePhone' => $user->profile->phone_number,
            'email' => $user->email,
            'npwpNo' => $user->npwp,
            'notes' => 'CUSTOMER GSK - NIK:' . $user->identity,
        ];

        if ($address) {
            $customerData['billStreet'] = $address->full_address;
            $customerData['billZipCode'] = $address->postal_code;
        }

        $timestamp = now()->toIso8601String();
        $signature = hash_hmac('sha256', $timestamp, $secretKey);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'X-Api-Timestamp' => $timestamp,
            'X-Api-Signature'  => $signature,
            'Content-Type'  => 'application/json',
        ])->post($host . '/customer/save.do', $customerData);

        Log::info('API Accurate Customer Success: ' . $response->body());
        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['s']) && $data['s'] === false) {
                $errorMsg = isset($data['d']) && is_array($data['d']) ? implode(', ', $data['d']) : json_encode($data);
                throw new \Exception('API Accurate Error: ' . $errorMsg);
            }
            if (isset($data['r'])) {
                $result = $data['r'];
                $user->update([
                    'accurate_customer_id' => $result['id'],
                    'accurate_customer_no' => $result['customerNo'],
                ]);
            }
        } else {
            Log::info('API Accurate Customer Error: ' . $response->body());
            throw new \Exception('API Accurate Customer Error: ' . $response->body());
        }
    }

    public function postSalesInvoice($salesInvoiceData, $databaseSource = 'syihab')
    {
        $tokenSuffix = strtoupper($databaseSource) === 'SECOND' ? '_SECOND' : '';
        $host = env('ACCURATE_HOST' . $tokenSuffix, env('ACCURATE_HOST'));
        $token = env('ACCURATE_TOKEN' . $tokenSuffix, env('ACCURATE_TOKEN'));
        $secretKey = env('ACCURATE_SECRET_KEY' . $tokenSuffix, env('ACCURATE_SECRET_KEY'));

        $timestamp = now()->toIso8601String();
        $signature = hash_hmac('sha256', $timestamp, $secretKey);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'X-Api-Timestamp' => $timestamp,
            'X-Api-Signature'  => $signature,
            'Content-Type'  => 'application/json',
        ])->post($host . '/sales-invoice/save.do', $salesInvoiceData);

        Log::info('API Accurate Sales Invoice Success: ' . $response->body());
        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['s']) && $data['s'] === false) {
                $errorMsg = isset($data['d']) && is_array($data['d']) ? implode(', ', $data['d']) : json_encode($data);
                throw new \Exception('API Accurate Error: ' . $errorMsg);
            }
            if (isset($data)) {
                return $data;
            }
            return [];
        } else {
            Log::info('API Accurate Sales Invoice Error: ' . $response->body());
            throw new \Exception('API Accurate Sales Invoice Error: ' . $response->body());
        }
    }

    public function getEmployees($databaseSource = 'syihab')
    {
        $tokenSuffix = strtoupper($databaseSource) === 'SECOND' ? '_SECOND' : '';

        // Mengambil konfigurasi environment berdasarkan database source
        $host = env('ACCURATE_HOST' . $tokenSuffix, env('ACCURATE_HOST'));
        $token = env('ACCURATE_TOKEN' . $tokenSuffix, env('ACCURATE_TOKEN'));
        $secretKey = env('ACCURATE_SECRET_KEY' . $tokenSuffix, env('ACCURATE_SECRET_KEY'));

        // Pembuatan Signature Oauth / API Timestamp jika menggunakan metode signature custom
        $timestamp = now()->toIso8601String();
        $signature = hash_hmac('sha256', $timestamp, $secretKey);
        $paramBody = [
            "sp.pageSize" => 10000
        ];
        // Hit ke endpoint karyawan milik Accurate Online
        $response = Http::withHeaders([
            'Authorization'   => 'Bearer ' . $token,
            'X-Api-Timestamp' => $timestamp,
            'X-Api-Signature' => $signature,
            'Content-Type'    => 'application/json',
            // 'X-Session-ID'   => $this->sessionId, // Hidupkan kolom ini jika otentikasi Anda via Session ID open-db
        ])->get($host . '/employee/list.do', $paramBody);

        // Jika request sukses, kembalikan data array murninya ke pemanggil (Livewire)
        if ($response->successful()) {
            return $response->json('d') ?? [];
        }

        // Catat log jika terjadi kendala pada server Accurate
        \Illuminate\Support\Facades\Log::error("Accurate API Get Employees Failed ({$databaseSource}): " . $response->body());

        return [];
    }

    public function postSalesReceipt($salesReceiptData, $databaseSource = 'syihab')
    {
        $tokenSuffix = strtoupper($databaseSource) === 'SECOND' ? '_SECOND' : '';
        $host = env('ACCURATE_HOST' . $tokenSuffix, env('ACCURATE_HOST'));
        $token = env('ACCURATE_TOKEN' . $tokenSuffix, env('ACCURATE_TOKEN'));
        $secretKey = env('ACCURATE_SECRET_KEY' . $tokenSuffix, env('ACCURATE_SECRET_KEY'));

        $timestamp = now()->toIso8601String();
        $signature = hash_hmac('sha256', $timestamp, $secretKey);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'X-Api-Timestamp' => $timestamp,
            'X-Api-Signature'  => $signature,
            'Content-Type'  => 'application/json',
        ])->post($host . '/sales-receipt/save.do', $salesReceiptData);

        Log::info('API Accurate Sales Receipt Success: ' . $response->body());
        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['s']) && $data['s'] === false) {
                $errorMsg = isset($data['d']) && is_array($data['d']) ? implode(', ', $data['d']) : json_encode($data);
                throw new \Exception('API Accurate Error: ' . $errorMsg);
            }
            if (isset($data)) {
                return $data;
            }
            return [];
        } else {
            Log::info('API Accurate Sales Receipt Error: ' . $response->body());
            throw new \Exception('API Accurate Sales Receipt Error: ' . $response->body());
        }
    }

    public function getItemStockPerWarehouse($warehouseName, $databaseSource = 'syihab')
    {
        $tokenSuffix = strtoupper($databaseSource) === 'SECOND' ? '_SECOND' : '';
        $host = env('ACCURATE_HOST' . $tokenSuffix, env('ACCURATE_HOST'));
        $token = env('ACCURATE_TOKEN' . $tokenSuffix, env('ACCURATE_TOKEN'));
        $secretKey = env('ACCURATE_SECRET_KEY' . $tokenSuffix, env('ACCURATE_SECRET_KEY'));

        $timestamp = now()->toIso8601String();
        $signature = hash_hmac('sha256', $timestamp, $secretKey);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'X-Api-Timestamp' => $timestamp,
            'X-Api-Signature'  => $signature,
            'Content-Type'  => 'application/json',
        ])->get($host . '/item/list-stock.do', [
            'sp.pageSize' => 1000,
            'warehouseName' => $warehouseName
        ]);

        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['s']) && $data['s'] === false) {
                throw new \Exception('API Accurate Error: ' . json_encode($data['d']));
            }
            return $data['d'] ?? [];
        } else {
            throw new \Exception('API Accurate Error: ' . $response->body());
        }
    }
    public function getItemStockAllPerWarehouse($itemNo, $databaseSource = 'syihab')
    {
        $tokenSuffix = strtoupper($databaseSource) === 'SECOND' ? '_SECOND' : '';
        $host = env('ACCURATE_HOST' . $tokenSuffix, env('ACCURATE_HOST'));
        $token = env('ACCURATE_TOKEN' . $tokenSuffix, env('ACCURATE_TOKEN'));
        $secretKey = env('ACCURATE_SECRET_KEY' . $tokenSuffix, env('ACCURATE_SECRET_KEY'));

        $timestamp = now()->toIso8601String();
        $signature = hash_hmac('sha256', $timestamp, $secretKey);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'X-Api-Timestamp' => $timestamp,
            'X-Api-Signature'  => $signature,
            'Content-Type'  => 'application/json',
        ])->get($host . '/item/list-stock.do', [
            'pageSize' => 100,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['s']) && $data['s'] === false) {
                throw new \Exception('API Accurate Error: ' . json_encode($data['d']));
            }
            return $data['d'] ?? [];
        } else {
            throw new \Exception('API Accurate Error: ' . $response->body());
        }
    }
    /**
     * FUNGSI BARU: Memeriksa keberadaan Serial Number (SN) di database Accurate Online
     */
    // public function checkSerialNumberExistance($sn, $databaseSource = 'syihab')
    // {
    //     $tokenSuffix = strtoupper($databaseSource) === 'SECOND' ? '_SECOND' : '';
    //     $host = env('ACCURATE_HOST' . $tokenSuffix, env('ACCURATE_HOST'));
    //     $token = env('ACCURATE_TOKEN' . $tokenSuffix, env('ACCURATE_TOKEN'));
    //     $secretKey = env('ACCURATE_SECRET_KEY' . $tokenSuffix, env('ACCURATE_SECRET_KEY'));

    //     $timestamp = now()->toIso8601String();
    //     $signature = hash_hmac('sha256', $timestamp, $secretKey);

    //     try {
    //         $response = Http::withHeaders([
    //             'Authorization'   => 'Bearer ' . $token,
    //             'X-Api-Timestamp' => $timestamp,
    //             'X-Api-Signature' => $signature,
    //             'Content-Type'    => 'application/json',
    //         ])->get($host . '/item/search-by-item-or-sn.do', [
    //             'keywords' => $sn
    //         ]);

    //         Log::info("API Accurate Check SN ({$databaseSource}) Success: " . $response->body());

    //         if ($response->successful()) {
    //             $data = $response->json();
    //             // Jika Accurate mengembalikan status sukses (s = true) dan isi datanya (d) tidak kosong, berati SN ada
    //             if (isset($data['s']) && $data['s'] === true && !empty($data['d'])) {
    //                 return true;
    //             }
    //         }
    //         return false;
    //     } catch (\Exception $e) {
    //         Log::error("API Accurate Check SN ({$databaseSource}) Error: " . $e->getMessage());
    //         return false;
    //     }
    // }
    public function checkSerialNumberExistance($sn, $expectedSku, $databaseSource = 'syihab')
    {
        $tokenSuffix = strtoupper($databaseSource) === 'SECOND' ? '_SECOND' : '';
        $host = env('ACCURATE_HOST' . $tokenSuffix, env('ACCURATE_HOST'));
        $token = env('ACCURATE_TOKEN' . $tokenSuffix, env('ACCURATE_TOKEN'));
        $secretKey = env('ACCURATE_SECRET_KEY' . $tokenSuffix, env('ACCURATE_SECRET_KEY'));

        $timestamp = now()->toIso8601String();
        $signature = hash_hmac('sha256', $timestamp, $secretKey);

        try {
            $response = Http::withHeaders([
                'Authorization'   => 'Bearer ' . $token,
                'X-Api-Timestamp' => $timestamp,
                'X-Api-Signature' => $signature,
                'Content-Type'    => 'application/json',
            ])->get($host . '/item/search-by-item-or-sn.do', [
                'keywords' => $sn
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['s']) && $data['s'] === true) {
                    // KONDISI 1: Jika Accurate sukses merespons tapi array 'd' kosong (SN memang tidak ada)
                    if (empty($data['d'])) {
                        return 'not_found';
                    }

                    // Jika array 'd' ada isinya, kita cek kecocokan SKU
                    foreach ($data['d'] as $accurateItem) {
                        if (isset($accurateItem['no']) && $accurateItem['no'] === $expectedSku) {
                            return 'valid'; // SN ada DAN cocok dengan SKU
                        }
                    }

                    // KONDISI 2: Loop selesai tapi tidak ada SKU yang cocok (SN ada, tapi beda barang)
                    return 'mismatch';
                }
            }

            return 'not_found';
        } catch (\Exception $e) {
            Log::error("API Accurate Check SN ({$databaseSource}) Error: " . $e->getMessage());
            return 'error'; // Jika terjadi gangguan server / API timeout
        }
    }

    /**
     * Hit API Bulk Save Penyesuaian Persediaan (Max 100 data)
     * * @param array $chunkData
     * @return array
     * @throws \Exception
     */
    public function bulkSaveItemAdjustment(array $chunkData)
    {
        // 1. Siapkan Timestamp & Signature khas Accurate
        $timestamp = now()->toIso8601String();
        $signature = hash_hmac('sha256', $timestamp, env('ACCURATE_SECRET_KEY'));

        // 2. Susun parameter bungkus "data"
        $payload = [
            "data" => $chunkData
        ];

        // 3. Eksekusi POST ke endpoint bulk-save
        $response = Http::withHeaders([
            'Authorization'   => 'Bearer ' . env('ACCURATE_TOKEN'),
            'X-Api-Timestamp' => $timestamp,
            'X-Api-Signature' => $signature,
            'Content-Type'    => 'application/json',
        ])->post(env('ACCURATE_HOST') . '/item-adjustment/bulk-save.do', $payload);

        // 4. Catat Log & Validasi Response internal Accurate
        Log::info('API Accurate Bulk Adjustment Response: ' . $response->body());

        if ($response->successful()) {
            $data = $response->json();

            // Cek jika status 's' bernilai false (error bawaan Accurate)
            if (isset($data['s']) && $data['s'] === false) {
                $errorMsg = isset($data['d']) && is_array($data['d']) ? implode(', ', $data['d']) : json_encode($data);
                throw new \Exception('API Accurate Error: ' . $errorMsg);
            }

            return $data;
        } else {
            throw new \Exception('API Accurate Connection Error: ' . $response->body());
        }
    }
}

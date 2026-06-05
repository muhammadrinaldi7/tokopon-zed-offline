<?php

namespace App\Services;

use App\Models\Employe;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AccurateService
{
    /**
     * Fetch Item Detail from Accurate
     * 
     * @param string $itemNo
     * @return array
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
                $result = $data['d'];
                return $result;
            }
            return [];
        } else {
            Log::info('API Accurate Error: ' . $response->body());
            throw new \Exception('API Accurate Error: ' . $response->body());
        }
    }
    /**
     * Hit Accurate Online API to save User as Vendor
     * 
     * @param User $user
     * @param string $databaseSource
     * @return void
     * @throws \Exception
     */
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

    public function getItemList($page = 1, $pageSize = 100, $databaseSource = 'syihab')
    {
        // Tentukan kredensial berdasarkan sumber database
        $tokenSuffix = strtoupper($databaseSource) === 'SECOND' ? '_SECOND' : '';

        $host = env('ACCURATE_HOST' . $tokenSuffix, env('ACCURATE_HOST'));
        $token = env('ACCURATE_TOKEN' . $tokenSuffix, env('ACCURATE_TOKEN'));
        $secretKey = env('ACCURATE_SECRET_KEY' . $tokenSuffix, env('ACCURATE_SECRET_KEY'));

        if (!$host || !$token) {
            throw new \Exception("Kredensial API Accurate untuk sumber '{$databaseSource}' belum diatur.");
        }

        $timestamp = now()->toIso8601String();
        $signature = hash_hmac('sha256', $timestamp, $secretKey);

        // PERUBAHAN: Gunakan variabel dinamis untuk parameter halaman
        $param = [
            "sp.page"     => $page,
            "sp.pageSize" => $pageSize,
            "fields"      => "no,name,unitPrice,availableToSell,itemBranchName,balanceUnitCost",
        ];

        $response = Http::withHeaders([
            'Authorization'   => 'Bearer ' . $token,
            'X-Api-Timestamp' => $timestamp,
            'X-Api-Signature' => $signature,
            'Content-Type'    => 'application/json',
        ])->get($host . '/item/list.do', $param);

        if ($response->successful()) {
            $data = $response->json();

            if (isset($data['s']) && $data['s'] === false) {
                $errorMsg = isset($data['d']) && is_array($data['d']) ? implode(', ', $data['d']) : json_encode($data);
                throw new \Exception('API Accurate Error: ' . $errorMsg);
            }

            // PERUBAHAN: Langsung kembalikan array datanya (bagian 'd')
            return $data['d'] ?? [];
        } else {
            \Illuminate\Support\Facades\Log::error("API Accurate Get Item List ({$databaseSource}) Error: " . $response->body());
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

    public function fetchCustomers($page = 1, $databaseSource = 'syihab')
    {
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
            'fields' => 'id,name,customerNo,email,mobilePhone',
            'sp.sort' => 'id|asc',
            'sp.page' => $page,
            'sp.pageSize' => 100
        ];

        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'X-Api-Timestamp' => $timestamp,
            'X-Api-Signature' => $signature
        ])->get($host . '/customer/list.do', $param);

        if ($response->successful()) {
            return $response->json();
        }

        \Illuminate\Support\Facades\Log::error('API Accurate Fetch Customers Error: ' . $response->body());
        throw new \Exception('API Accurate Fetch Customers Error: ' . $response->body());
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
            'name' => 'SYB_CUSTOMER_' . $user ? $user->profile->full_name : $user->name,
            'customerNo' => 'SYB_CUSTOMER_' . str_pad($user->id, 5, '0', STR_PAD_LEFT),
            'currencyCode' => 'IDR',
            'mobilePhone' => $user->profile->phone_number,
            'email' => $user->email,
            'npwpNo' => $user->npwp,
            'notes' => 'CUSTOMER SYB - NIK:' . $user->identity,
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

    public function postSalesOrder($salesOrderData, $databaseSource = 'syihab')
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
        ])->post($host . '/sales-order/save.do', $salesOrderData);

        Log::info('API Accurate Sales Order Success: ' . $response->body());
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
            Log::info('API Accurate Sales Order Error: ' . $response->body());
            throw new \Exception('API Accurate Sales Order Error: ' . $response->body());
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

        $allEmployees = [];
        $page = 1;
        $hasMore = true;

        while ($hasMore) {
            // Pembuatan Signature Oauth / API Timestamp jika menggunakan metode signature custom
            $timestamp = now()->toIso8601String();
            $signature = hash_hmac('sha256', $timestamp, $secretKey);
            $paramBody = [
                "sp.pageSize" => 100,
                "sp.page" => $page,
                "fields" => "id,number,name,email,mobilePhone,workPositionName,suspended,branchId"
            ];

            // Hit ke endpoint karyawan milik Accurate Online
            $response = Http::withHeaders([
                'Authorization'   => 'Bearer ' . $token,
                'X-Api-Timestamp' => $timestamp,
                'X-Api-Signature' => $signature,
                'Content-Type'    => 'application/json',
            ])->get($host . '/employee/list.do', $paramBody);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['s']) && $data['s'] === false) {
                    \Illuminate\Support\Facades\Log::error("Accurate API Get Employees Error ({$databaseSource}): " . json_encode($data));
                    break;
                }

                $chunk = $data['d'] ?? [];
                $allEmployees = array_merge($allEmployees, $chunk);

                $pageCount = $data['sp']['pageCount'] ?? 1;

                if ($page >= $pageCount || count($chunk) < 100) {
                    $hasMore = false;
                } else {
                    $page++;
                }
            } else {
                // Catat log jika terjadi kendala pada server Accurate
                \Illuminate\Support\Facades\Log::error("Accurate API Get Employees Failed ({$databaseSource}): " . $response->body());
                $hasMore = false;
            }
        }

        return $allEmployees;
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

    // public function getStockPerWarehouse($warehouseName, $databaseSource = 'syihab')
    // {
    //     $tokenSuffix = strtoupper($databaseSource) === 'SECOND' ? '_SECOND' : '';
    //     $host = env('ACCURATE_HOST' . $tokenSuffix, env('ACCURATE_HOST'));
    //     $token = env('ACCURATE_TOKEN' . $tokenSuffix, env('ACCURATE_TOKEN'));
    //     $secretKey = env('ACCURATE_SECRET_KEY' . $tokenSuffix, env('ACCURATE_SECRET_KEY'));

    //     $allData = [];
    //     $page = 1;
    //     $hasMore = true;

    //     while ($hasMore) {
    //         $timestamp = now()->toIso8601String();
    //         $signature = hash_hmac('sha256', $timestamp, $secretKey);

    //         $response = Http::withHeaders([
    //             'Authorization' => 'Bearer ' . $token,
    //             'X-Api-Timestamp' => $timestamp,
    //             'X-Api-Signature'  => $signature,
    //             'Content-Type'  => 'application/json',
    //         ])->get($host . '/item/list-stock.do', [
    //             'sp.pageSize' => 100,
    //             'sp.page' => $page,
    //             'warehouseName' => $warehouseName
    //         ]);

    //         if ($response->successful()) {
    //             $data = $response->json();
    //             if (isset($data['s']) && $data['s'] === false) {
    //                 throw new \Exception('API Accurate Error: ' . json_encode($data['d']));
    //             }

    //             $chunk = $data['d'] ?? [];
    //             $allData = array_merge($allData, $chunk);

    //             // Jika jumlah data di halaman ini kurang dari limit (100), berarti sudah mentok di halaman terakhir
    //             if (count($chunk) < 100) {
    //                 $hasMore = false;
    //             } else {
    //                 $page++;
    //             }
    //         } else {
    //             throw new \Exception('API Accurate Error: ' . $response->body());
    //         }
    //     }

    //     return $allData;
    // }

    public function getGlAccounts($databaseSource = 'syihab')
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
        ])->get($host . '/glaccount/list.do', [
            'fields' => 'id,no,name,accountType',
            'filter.accountType.op' => 'EQUAL',
            'filter.accountType.val' => ['CASH_BANK'],
            'filter.leafOnly' => true,
            'filter.suspended' => false,
            'sp.pageSize' => 100
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
     * Get serial number per warehouse from Accurate report
     */
    public function getSerialNumberPerWarehouse($sku, $databaseSource = 'syihab')
    {
        $tokenSuffix = strtoupper($databaseSource) === 'SECOND' ? '_SECOND' : '';

        $host = env('ACCURATE_HOST' . $tokenSuffix, env('ACCURATE_HOST'));
        $token = env('ACCURATE_TOKEN' . $tokenSuffix, env('ACCURATE_TOKEN'));
        $secretKey = env('ACCURATE_SECRET_KEY' . $tokenSuffix, env('ACCURATE_SECRET_KEY'));

        if (!$host || !$token) {
            throw new \Exception("Kredensial API Accurate untuk sumber '{$databaseSource}' belum diatur.");
        }

        $timestamp = now()->toIso8601String();
        $signature = hash_hmac('sha256', $timestamp, $secretKey);

        $response = Http::withHeaders([
            'Authorization'   => 'Bearer ' . $token,
            'X-Api-Timestamp' => $timestamp,
            'X-Api-Signature' => $signature,
            'Content-Type'    => 'application/json',
        ])->get($host . '/report/serial-number-per-warehouse.do', [
            'itemNo' => $sku,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['s']) && $data['s'] === true) {
                return $data['d'] ?? [];
            }
        }
        Log::info('data proses serial number 2: ' . json_encode($data));
        return [];
    }

    public function getItemStockPerWarehouse($warehouseName, $databaseSource = 'syihab')
    {
        $tokenSuffix = strtoupper($databaseSource) === 'SECOND' ? '_SECOND' : '';
        $host = env('ACCURATE_HOST' . $tokenSuffix, env('ACCURATE_HOST'));
        $token = env('ACCURATE_TOKEN' . $tokenSuffix, env('ACCURATE_TOKEN'));
        $secretKey = env('ACCURATE_SECRET_KEY' . $tokenSuffix, env('ACCURATE_SECRET_KEY'));

        $allData = [];
        $page = 1;
        $hasMore = true;

        while ($hasMore) {
            $timestamp = now()->toIso8601String();
            $signature = hash_hmac('sha256', $timestamp, $secretKey);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'X-Api-Timestamp' => $timestamp,
                'X-Api-Signature'  => $signature,
                'Content-Type'  => 'application/json',
            ])->get($host . '/item/list-stock.do', [
                'sp.pageSize' => 100,
                'sp.page' => $page,
                'warehouseName' => $warehouseName
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['s']) && $data['s'] === false) {
                    throw new \Exception('API Accurate Error: ' . json_encode($data['d']));
                }

                $chunk = $data['d'] ?? [];
                $allData = array_merge($allData, $chunk);

                // Jika jumlah data di halaman ini kurang dari limit (100), berarti sudah mentok di halaman terakhir
                if (count($chunk) < 100) {
                    $hasMore = false;
                } else {
                    $page++;
                }
            } else {
                throw new \Exception('API Accurate Error: ' . $response->body());
            }
        }

        return $allData;
    }
    public function getStockPerItem($itemNo, $databaseSource = 'syihab')
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
            'itemNo' => $itemNo
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

    public function getStockPerItemWarehouse($itemNo, $warehouseName, $databaseSource = 'syihab')
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
        ])->get($host . '/item/get-stock.do', [
            'no' => $itemNo,
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

                    // FILTER BARU: Cek apakah hasil yang ditemukan benar-benar Serial Number
                    $firstHit = $data['d'][0];
                    if (isset($firstHit['searchHitType']) && $firstHit['searchHitType'] !== 'serialNumber') {
                        return 'invalid_type'; // Jika bukan SN (misal: barcode barang), tolak!
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
    private function getHeaders($databaseSource)
    {
        $tokenSuffix = strtoupper($databaseSource) === 'SECOND' ? '_SECOND' : '';
        $host = env('ACCURATE_HOST' . $tokenSuffix, env('ACCURATE_HOST'));
        $token = env('ACCURATE_TOKEN' . $tokenSuffix, env('ACCURATE_TOKEN'));
        $secretKey = env('ACCURATE_SECRET_KEY' . $tokenSuffix, env('ACCURATE_SECRET_KEY'));

        $timestamp = now()->toIso8601String();
        $signature = hash_hmac('sha256', $timestamp, $secretKey);

        return [
            'host' => $host,
            'headers' => [
                'Authorization'   => 'Bearer ' . $token,
                'X-Api-Timestamp' => $timestamp,
                'X-Api-Signature' => $signature,
                'Content-Type'    => 'application/json',
            ]
        ];
    }
    /**
     * FUNGSI BARU: Mencari SKU (item no) berdasarkan SN saat scanning di POS
     */
    public function findSkuBySerialNumber($sn, $databaseSource = 'syihab')
    {
        $config = $this->getHeaders($databaseSource);

        try {
            $response = Http::withHeaders($config['headers'])
                ->get($config['host'] . '/item/search-by-item-or-sn.do', [
                    // Tetap pertahankan strtoupper + trim agar aman dari masalah case-sensitive kemarin
                    'keywords' => trim($sn)
                ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['s']) && $data['s'] === true && !empty($data['d'])) {
                    // 1. Ambil data hit pertama dari Accurate
                    $firstHit = $data['d'][0];

                    // 2. FILTER BARU: Validasi apakah searchHitType benar-benar 'serialNumber'
                    if (isset($firstHit['searchHitType']) && $firstHit['searchHitType'] === 'serialNumber') {
                        return $firstHit['no'] ?? null; // Kembalikan SKU jika valid
                    }

                    // BARU: Jika ketemu tapi BUKAN serialNumber, kembalikan penanda khusus
                    return 'invalid_type';
                    // Jika tipenya bukan serialNumber (misal: 'item'), catat log dan otomatis return null (tolak)
                    \Illuminate\Support\Facades\Log::warning("Scan SN diabaikan karena searchHitType berjenis '" . ($firstHit['searchHitType'] ?? 'unknown') . "' untuk input: {$sn}");
                }
            }
            return null;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("API Accurate Find SKU by SN ({$databaseSource}) Error: " . $e->getMessage());
            return 'error';
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

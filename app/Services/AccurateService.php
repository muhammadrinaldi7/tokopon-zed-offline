<?php

namespace App\Services;

use App\Models\Employe;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AccurateService
{

    public function getCredentials($databaseSource)
    {
        // Bersihkan spasi, jadikan huruf kecil, dan berikan default 'syihab' jika kosong
        $code = trim(strtolower($databaseSource)) ?: 'syihab';

        $businessUnit = \App\Models\BusinessUnit::where("code", $code)->first();

        if (!$businessUnit) {
            throw new \Exception("Kredensial Accurate untuk unit usaha '{$databaseSource}' tidak ditemukan di database.");
        }

        return [
            $businessUnit->accurate_host,
            $businessUnit->accurate_token,
            $businessUnit->accurate_secret_key
        ];
    }


    /**
     * Fetch Item Detail from Accurate
     * 
     * @param string $itemNo
     * @return array
     * @throws \Exception
     */
    public function itemDetailDo($itemNo, $databaseSource = 'syihab')
    {
        $config = $this->getHeaders($databaseSource);

        $param = [
            "no" => $itemNo
        ];
        $response = Http::withHeaders($config['headers'])
            ->get($config['host'] . '/item/detail.do', $param);

        Log::info("API Accurate Item Detail ({$databaseSource}): " . $response->body());
        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['s']) && $data['s'] === false) {
                $errorMsg = isset($data['d']) && is_array($data['d']) ? implode(', ', $data['d']) : json_encode($data);
                throw new \Exception('API Accurate Error: ' . $errorMsg);
            }
            if (isset($data)) {
                $result = $data['d'];
                return $result;
            }
            return [];
        } else {
            Log::info("API Accurate Item Detail Error ({$databaseSource}): " . $response->body());
            throw new \Exception('API Accurate Error: ' . $response->body());
        }
    }

    /**
     * Save Purchase Invoice to Accurate
     * * @param array $payload
     * @param string $databaseSource
     * @return array|bool
     * @throws \Exception
     */
    public function savePurchaseInvoiceDo(array $payload, $databaseSource = 'syihab')
    {
        // Memanfaatkan fungsi getHeaders() yang sudah Anda miliki
        $config = $this->getHeaders($databaseSource);

        // API tujuan adalah purchase-invoice/save.do dengan method POST
        $response = Http::withHeaders($config['headers'])
            ->post($config['host'] . '/purchase-invoice/save.do', $payload);

        Log::info("API Accurate Save PI ({$databaseSource}) Payload: " . json_encode($payload));
        Log::info("API Accurate Save PI ({$databaseSource}) Response: " . $response->body());

        if ($response->successful()) {
            $data = $response->json();

            // Validasi sukses/gagal dari struktur JSON Accurate
            if (isset($data['s']) && $data['s'] === false) {
                $errorMsg = isset($data['d']) && is_array($data['d']) ? implode(', ', $data['d']) : json_encode($data);
                throw new \Exception('API Accurate Error: ' . $errorMsg);
            }

            // Mengembalikan data hasil save (biasanya berisi id dan number faktur baru)
            return $data['d'] ?? true;
        } else {
            Log::error("API Accurate Save PI Error ({$databaseSource}): " . $response->body());
            throw new \Exception('API Accurate HTTP Error: ' . $response->status() . ' - ' . $response->body());
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
        $bu = \App\Models\BusinessUnit::where('code', $databaseSource)->first();
        if (!$bu) return;

        // Jika sudah punya vendor ID, tidak perlu hit API lagi
        $existingVendor = $user->accurateVendors()->where('business_unit_id', $bu->id)->first();
        if ($existingVendor && ($existingVendor->accurate_vendor_id || $existingVendor->accurate_vendor_no)) {
            return;
        }
        list($host, $token, $secretKey) = $this->getCredentials($databaseSource);


        // Ambil alamat primary
        $address = $user->addresses()->where('is_primary', true)->first();

        // Generate prefix dynamically for any new business unit, preserving legacy ones
        $prefix = strtoupper(trim($databaseSource)) . '_';
        if (trim(strtolower($databaseSource)) === 'second') $prefix = 'GSK_';
        if (trim(strtolower($databaseSource)) === 'syihab') $prefix = 'SYB_';

        // Data yang akan dikirim ke Accurate
        $vendorData = [
            'name' => $prefix . 'VENDOR_' . ($user->profile ? $user->profile->full_name : $user->name),
            'vendorNo' => $prefix . 'VENDOR_' . str_pad($user->id, 5, '0', STR_PAD_LEFT),
            // 'transDate' => date('d/m/Y'),
            'currencyCode' => 'IDR',
            'mobilePhone' => $user->profile ? $user->profile->phone_number : null,
            'email' => $user->email,
            'npwpNo' => $user->npwp,
            'notes' => 'VENDOR ' . rtrim($prefix, '_') . ' - NIK:' . $user->identity,
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
                $idAccurate = $result['id'];        // Hasilnya: 601
                $noVendor   = $result['vendorNo'];  // Hasilnya: "GSK_VENDOR_00002"
                // 2. Update database user
                $user->accurateVendors()->updateOrCreate(
                    ['business_unit_id' => $bu->id],
                    [
                        'accurate_vendor_id' => $idAccurate,
                        'accurate_vendor_no' => $noVendor,
                    ]
                );
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
    public function getWarehouseList($databaseSource = 'syihab')
    {
        $config = $this->getHeaders($databaseSource);

        $response = Http::withHeaders($config['headers'])
            ->get($config['host'] . '/warehouse/list.do');

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
    public function getBranchList($databaseSource = 'syihab')
    {
        $config = $this->getHeaders($databaseSource);

        $response = Http::withHeaders($config['headers'])
            ->get($config['host'] . '/branch/list.do');

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
        list($host, $token, $secretKey) = $this->getCredentials($databaseSource);


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

    public function postPurchasePayment($purchasePaymentData, $databaseSource = 'syihab')
    {
        list($host, $token, $secretKey) = $this->getCredentials($databaseSource);

        if (!$host || !$token) {
            throw new \Exception("Kredensial API Accurate untuk sumber '{$databaseSource}' belum diatur.");
        }

        $timestamp = now()->toIso8601String();
        $signature = hash_hmac('sha256', $timestamp, $secretKey);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'X-Api-Timestamp' => $timestamp,
            'X-Api-Signature'  => $signature,
            'Content-Type'  => 'application/json',
        ])->post($host . '/purchase-payment/save.do', $purchasePaymentData);

        Log::info('API Accurate Purchase Payment Success: ' . $response->body());
        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['s']) && $data['s'] === false) {
                $errorMsg = isset($data['d']) && is_array($data['d']) ? implode(', ', $data['d']) : json_encode($data);
                throw new \Exception('API Accurate Purchase Payment Error: ' . $errorMsg);
            }
            if (isset($data)) {
                return $data;
            }
            return [];
        } else {
            Log::info('API Accurate Purchase Payment Error: ' . $response->body());
            throw new \Exception('API Accurate Purchase Payment Error: ' . $response->body());
        }
    }

    public function getItemList($page = 1, $pageSize = 100, $databaseSource = 'syihab')
    {
        // Tentukan kredensial berdasarkan sumber database
        list($host, $token, $secretKey) = $this->getCredentials($databaseSource);


        if (!$host || !$token) {
            throw new \Exception("Kredensial API Accurate untuk sumber '{$databaseSource}' belum diatur.");
        }

        $timestamp = now()->toIso8601String();
        $signature = hash_hmac('sha256', $timestamp, $secretKey);

        // PERUBAHAN: Gunakan variabel dinamis untuk parameter halaman
        $param = [
            "sp.page"     => $page,
            "sp.pageSize" => $pageSize,
            "fields"      => "no,name,unitPrice,availableToSell,itemBranchName,balanceUnitCost,itemBrand,itemCategory,manageSN,itemType",
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
            Log::info('data accurate products ' . json_encode($data['d']));
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
        list($host, $token, $secretKey) = $this->getCredentials($databaseSource);


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

    public function getNearestCost($itemNo, $databaseSource = 'syihab')
    {
        list($host, $token, $secretKey) = $this->getCredentials($databaseSource);


        if (!$host || !$token) {
            throw new \Exception("Kredensial API Accurate untuk sumber '{$databaseSource}' belum diatur.");
        }

        $timestamp = now()->toIso8601String();
        $signature = hash_hmac('sha256', $timestamp, $secretKey);

        $param = [
            "itemNo" => $itemNo
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'X-Api-Timestamp' => $timestamp,
            'X-Api-Signature'  => $signature,
            'Content-Type'  => 'application/json',
        ])->get($host . '/item/get-nearest-cost.do', $param);

        Log::info("API Accurate Get Nearest Cost ({$itemNo}) Success: " . $response->body());

        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['s']) && $data['s'] === false) {
                $errorMsg = isset($data['d']) && is_array($data['d']) ? implode(', ', $data['d']) : json_encode($data);
                throw new \Exception('API Accurate Error: ' . $errorMsg);
            }
            if (isset($data['d'])) {
                return $data['d'];
            }
            return null;
        } else {
            Log::error("API Accurate Get Nearest Cost Error: " . $response->body());
            throw new \Exception('API Accurate Get Nearest Cost Error: ' . $response->body());
        }
    }

    public function fetchCustomers($page = 1, $databaseSource = 'syihab')
    {
        list($host, $token, $secretKey) = $this->getCredentials($databaseSource);


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
        $businessUnit = \App\Models\BusinessUnit::where('code', $databaseSource)->first();
        if (!$businessUnit) {
            return;
        }

        $existingPivot = $user->accurateCustomers()->where('business_unit_id', $businessUnit->id)->first();
        if ($existingPivot) {
            return; // Customer is already synced to this business unit
        }

        list($host, $token, $secretKey) = $this->getCredentials($databaseSource);


        $address = $user->addresses()->where('is_primary', true)->first();

        // Ambil prefix dinamis dari database, default ke KODE unit jika kosong
        $prefix = $businessUnit->customer_prefix
            ? strtoupper($businessUnit->customer_prefix)
            : strtoupper(trim($businessUnit->code)) . '_';

        $customerData = [
            'name' => $user->profile ? $user->profile->full_name : $user->name,
            'customerNo' => $prefix . 'CUSTOMER_' . str_pad($user->id, 5, '0', STR_PAD_LEFT),
            'currencyCode' => 'IDR',
            'mobilePhone' => $user->profile ? $user->profile->phone_number : null,
            'email' => $user->email,
            'npwpNo' => $user->npwp,
            'notes' => 'CUSTOMER ' . $prefix . ' - NIK:' . $user->identity,
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
                // If it already exists, Accurate usually returns an error. 
                // We should handle if the error is "Customer No. already exists"
                $errorMsg = isset($data['d']) && is_array($data['d']) ? implode(', ', $data['d']) : json_encode($data);
                throw new \Exception('API Accurate Error: ' . $errorMsg);
            }
            if (isset($data['r'])) {
                $result = $data['r'];

                \App\Models\UserAccurateCustomer::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'business_unit_id' => $businessUnit->id,
                    ],
                    [
                        'accurate_customer_id' => $result['id'],
                        'accurate_customer_no' => $result['customerNo'],
                    ]
                );
            }
        } else {
            Log::info('API Accurate Customer Error: ' . $response->body());
            throw new \Exception('API Accurate Customer Error: ' . $response->body());
        }
    }

    public function updateCustomer(User $user, $databaseSource = 'syihab')
    {
        $businessUnit = \App\Models\BusinessUnit::where('code', $databaseSource)->first();
        if (!$businessUnit) return;

        $existingPivot = $user->accurateCustomers()->where('business_unit_id', $businessUnit->id)->first();
        if (!$existingPivot) {
            // Jika belum ada di Accurate, panggil syncCustomer
            return $this->syncCustomer($user, $databaseSource);
        }

        list($host, $token, $secretKey) = $this->getCredentials($databaseSource);

        // Ambil prefix dinamis dari database, default ke KODE unit jika kosong
        $prefix = $businessUnit->customer_prefix
            ? strtoupper($businessUnit->customer_prefix)
            : strtoupper(trim($businessUnit->code)) . '_';

        $customerData = [
            'id' => $existingPivot->accurate_customer_id, // Sertakan ID untuk UPDATE
            'name' => $user->profile ? $user->profile->full_name : $user->name,
            'mobilePhone' => $user->profile ? $user->profile->phone_number : null,
            'email' => $user->email,
        ];

        $timestamp = now()->toIso8601String();
        $signature = hash_hmac('sha256', $timestamp, $secretKey);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'X-Api-Timestamp' => $timestamp,
            'X-Api-Signature'  => $signature,
            'Content-Type'  => 'application/json',
        ])->post($host . '/customer/save.do', $customerData);

        if (!$response->successful()) {
            Log::error('API Accurate Customer Update Error: ' . $response->body());
            throw new \Exception('API Accurate Customer Update Error: ' . $response->body());
        }
    }

    public function postSalesOrder($salesOrderData, $databaseSource = 'syihab')
    {
        list($host, $token, $secretKey) = $this->getCredentials($databaseSource);


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


    public function postDeliveryOrder($deliveryOrderData, $databaseSource = 'syihab')
    {
        list($host, $token, $secretKey) = $this->getCredentials($databaseSource);


        $timestamp = now()->toIso8601String();
        $signature = hash_hmac('sha256', $timestamp, $secretKey);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'X-Api-Timestamp' => $timestamp,
            'X-Api-Signature'  => $signature,
            'Content-Type'  => 'application/json',
        ])->post($host . '/delivery-order/save.do', $deliveryOrderData);

        Log::info('API Accurate Delivery Order Success: ' . $response->body());
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
            Log::info('API Accurate Delivery Order Error: ' . $response->body());
            throw new \Exception('API Accurate Delivery Order Error: ' . $response->body());
        }
    }

    public function postSalesInvoice($salesInvoiceData, $databaseSource = 'syihab')
    {
        list($host, $token, $secretKey) = $this->getCredentials($databaseSource);


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

    public function postSalesReturn($salesReturnData, $databaseSource = 'syihab')
    {
        list($host, $token, $secretKey) = $this->getCredentials($databaseSource);

        $timestamp = now()->toIso8601String();
        $signature = hash_hmac('sha256', $timestamp, $secretKey);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'X-Api-Timestamp' => $timestamp,
            'X-Api-Signature'  => $signature,
            'Content-Type'  => 'application/json',
        ])->post($host . '/sales-return/save.do', $salesReturnData);

        Log::info('API Accurate Sales Return Success: ' . $response->body());
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
            Log::info('API Accurate Sales Return Error: ' . $response->body());
            throw new \Exception('API Accurate Sales Return Error: ' . $response->body());
        }
    }

    public function getEmployees($databaseSource = 'syihab')
    {
        list($host, $token, $secretKey) = $this->getCredentials($databaseSource);

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

    public function getVendors($databaseSource = 'syihab')
    {
        list($host, $token, $secretKey) = $this->getCredentials($databaseSource);

        $allVendors = [];
        $page = 1;
        $hasMore = true;

        while ($hasMore) {
            $timestamp = now()->toIso8601String();
            $signature = hash_hmac('sha256', $timestamp, $secretKey);
            $paramBody = [
                "sp.pageSize" => 100,
                "sp.page" => $page,
                "fields" => "id,vendorNo,name,email,mobilePhone,suspended"
            ];

            $response = Http::withHeaders([
                'Authorization'   => 'Bearer ' . $token,
                'X-Api-Timestamp' => $timestamp,
                'X-Api-Signature' => $signature,
                'Content-Type'    => 'application/json',
            ])->get($host . '/vendor/list.do', $paramBody);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['s']) && $data['s'] === false) {
                    \Illuminate\Support\Facades\Log::error("Accurate API Get Vendors Error ({$databaseSource}): " . json_encode($data));
                    break;
                }

                $chunk = $data['d'] ?? [];
                $allVendors = array_merge($allVendors, $chunk);

                $pageCount = $data['sp']['pageCount'] ?? 1;

                if ($page >= $pageCount || count($chunk) < 100) {
                    $hasMore = false;
                } else {
                    $page++;
                }
            } else {
                \Illuminate\Support\Facades\Log::error("Accurate API Get Vendors Failed ({$databaseSource}): " . $response->body());
                $hasMore = false;
            }
        }

        return $allVendors;
    }

    /**
     * Fetch Vendor Detail from Accurate
     * 
     * @param string $vendorNo
     * @param string $databaseSource
     * @return array
     * @throws \Exception
     */
    public function getVendorDetail($vendorNo, $databaseSource = 'syihab')
    {
        list($host, $token, $secretKey) = $this->getCredentials($databaseSource);


        if (!$host || !$token) {
            throw new \Exception("Kredensial API Accurate untuk sumber '{$databaseSource}' belum diatur.");
        }

        $timestamp = now()->toIso8601String();
        $signature = hash_hmac('sha256', $timestamp, $secretKey);

        $param = [
            "vendorNo" => $vendorNo
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'X-Api-Timestamp' => $timestamp,
            'X-Api-Signature'  => $signature,
            'Content-Type'  => 'application/json',
        ])->get($host . '/vendor/detail.do', $param);

        Log::info("API Accurate Get Vendor Detail ({$vendorNo}) Success: " . $response->body());

        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['s']) && $data['s'] === false) {
                $errorMsg = isset($data['d']) && is_array($data['d']) ? implode(', ', $data['d']) : json_encode($data);
                throw new \Exception('API Accurate Error: ' . $errorMsg);
            }
            if (isset($data['d'])) {
                return $data['d'];
            }
            return [];
        } else {
            Log::info("API Accurate Get Vendor Detail Error: " . $response->body());
            throw new \Exception('API Accurate Get Vendor Detail Error: ' . $response->body());
        }
    }
    public function getCustomerDetail($customerNo, $databaseSource = 'syihab')
    {
        list($host, $token, $secretKey) = $this->getCredentials($databaseSource);


        if (!$host || !$token) {
            throw new \Exception("Kredensial API Accurate untuk sumber '{$databaseSource}' belum diatur.");
        }

        $timestamp = now()->toIso8601String();
        $signature = hash_hmac('sha256', $timestamp, $secretKey);

        $param = [
            "customerNo" => $customerNo
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'X-Api-Timestamp' => $timestamp,
            'X-Api-Signature'  => $signature,
            'Content-Type'  => 'application/json',
        ])->get($host . '/customer/detail.do', $param);

        Log::info("API Accurate Get Customer Detail ({$customerNo}) Success: " . $response->body());

        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['s']) && $data['s'] === false) {
                $errorMsg = isset($data['d']) && is_array($data['d']) ? implode(', ', $data['d']) : json_encode($data);
                throw new \Exception('API Accurate Error: ' . $errorMsg);
            }
            if (isset($data['d'])) {
                return $data['d'];
            }
            return [];
        } else {
            Log::info("API Accurate Get Vendor Detail Error: " . $response->body());
            throw new \Exception('API Accurate Get Vendor Detail Error: ' . $response->body());
        }
    }
    public function postDownPaymentInvoice($data, $databaseSource = 'syihab')
    {
        list($host, $token, $secretKey) = $this->getCredentials($databaseSource);

        $timestamp = now()->toIso8601String();
        $signature = hash_hmac('sha256', $timestamp, $secretKey);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'X-Api-Timestamp' => $timestamp,
            'X-Api-Signature'  => $signature,
            'Content-Type'  => 'application/json',
        ])->post($host . '/sales-invoice/create-down-payment.do', $data);

        Log::info('API Accurate DP Invoice Success: ' . $response->body());
        if ($response->successful()) {
            $responseData = $response->json();
            if (isset($responseData['s']) && $responseData['s'] === false) {
                $errorMsg = isset($responseData['d']) && is_array($responseData['d']) ? implode(', ', $responseData['d']) : json_encode($responseData);
                throw new \Exception('API Accurate Error: ' . $errorMsg);
            }
            if (isset($responseData)) {
                return $responseData;
            }
            return [];
        } else {
            Log::info('API Accurate DP Invoice Error: ' . $response->body());
            throw new \Exception('API Accurate DP Invoice Error: ' . $response->body());
        }
    }


    public function postSalesReceipt($salesReceiptData, $databaseSource = 'syihab')
    {
        list($host, $token, $secretKey) = $this->getCredentials($databaseSource);


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
            throw new \Exception('API Accurate Sales Receipt Error: ' . $response->body());
        }
    }

    public function getDetailSalesReceipt($id, $databaseSource = 'syihab')
    {
        list($host, $token, $secretKey) = $this->getCredentials($databaseSource);

        $timestamp = now()->toIso8601String();
        $signature = hash_hmac('sha256', $timestamp, $secretKey);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'X-Api-Timestamp' => $timestamp,
            'X-Api-Signature'  => $signature,
            'Content-Type'  => 'application/json',
        ])->get($host . '/sales-receipt/detail.do', [
            'id' => $id
        ]);

        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['s']) && $data['s'] === false) {
                $errorMsg = isset($data['d']) && is_array($data['d']) ? implode(', ', $data['d']) : json_encode($data);
                throw new \Exception('API Accurate Error: ' . $errorMsg);
            }
            if (isset($data['d'])) {
                return $data['d'];
            }
            return null;
        } else {
            Log::error('API Accurate Get Detail Sales Receipt Error: ' . $response->body());
            throw new \Exception('API Accurate Get Detail Sales Receipt Error: ' . $response->body());
        }
    }

    // public function getStockPerWarehouse($warehouseName, $databaseSource = 'syihab')
    // {
    //     list($host, $token, $secretKey) = $this->getCredentials($databaseSource);

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
        list($host, $token, $secretKey) = $this->getCredentials($databaseSource);


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
        list($host, $token, $secretKey) = $this->getCredentials($databaseSource);


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
        list($host, $token, $secretKey) = $this->getCredentials($databaseSource);


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
        list($host, $token, $secretKey) = $this->getCredentials($databaseSource);


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
        list($host, $token, $secretKey) = $this->getCredentials($databaseSource);


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
    //     list($host, $token, $secretKey) = $this->getCredentials($databaseSource);

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
        list($host, $token, $secretKey) = $this->getCredentials($databaseSource);


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
        list($host, $token, $secretKey) = $this->getCredentials($databaseSource);


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

    public function getReceiveItemList($databaseSource = 'syihab')
    {
        list($host, $token, $secretKey) = $this->getCredentials($databaseSource);


        if (!$host || !$token) {
            throw new \Exception("Kredensial API Accurate untuk sumber '{$databaseSource}' belum diatur.");
        }

        $allIds = [];
        $page = 1;
        $hasMore = true;

        while ($hasMore) {
            $timestamp = now()->toIso8601String();
            $signature = hash_hmac('sha256', $timestamp, $secretKey);

            $response = Http::withHeaders([
                'Authorization'   => 'Bearer ' . $token,
                'X-Api-Timestamp' => $timestamp,
                'X-Api-Signature' => $signature,
                'Content-Type'    => 'application/json',
            ])->get($host . '/receive-item/list.do', [
                'sp.pageSize' => 100,
                'sp.page' => $page,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['s']) && $data['s'] === true) {
                    $chunk = $data['d'] ?? [];
                    foreach ($chunk as $item) {
                        if (isset($item['id'])) {
                            $allIds[] = $item['id'];
                        }
                    }

                    $pageCount = $data['sp']['pageCount'] ?? 1;
                    if ($page >= $pageCount || count($chunk) < 100) {
                        $hasMore = false;
                    } else {
                        $page++;
                    }
                } else {
                    Log::error("Accurate API Get Receive Item List Error ({$databaseSource}): " . json_encode($data));
                    break;
                }
            } else {
                Log::error("Accurate API Get Receive Item List Failed ({$databaseSource}): " . $response->body());
                $hasMore = false;
            }
        }

        return $allIds;
    }

    public function getReceiveItemDetail($id, $databaseSource = 'syihab')
    {
        list($host, $token, $secretKey) = $this->getCredentials($databaseSource);


        $timestamp = now()->toIso8601String();
        $signature = hash_hmac('sha256', $timestamp, $secretKey);

        $response = Http::withHeaders([
            'Authorization'   => 'Bearer ' . $token,
            'X-Api-Timestamp' => $timestamp,
            'X-Api-Signature' => $signature,
            'Content-Type'    => 'application/json',
        ])->get($host . '/receive-item/detail.do', [
            'id' => $id,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['s']) && $data['s'] === true) {
                return $data['d'] ?? null;
            }
        }

        return null;
    }

    public function getPurchaseInvoiceDetail($id, $databaseSource = 'syihab')
    {
        list($host, $token, $secretKey) = $this->getCredentials($databaseSource);

        $timestamp = now()->toIso8601String();
        $signature = hash_hmac('sha256', $timestamp, $secretKey);

        $response = Http::withHeaders([
            'Authorization'   => 'Bearer ' . $token,
            'X-Api-Timestamp' => $timestamp,
            'X-Api-Signature' => $signature,
            'Content-Type'    => 'application/json',
        ])->get($host . '/purchase-invoice/detail.do', [
            'id' => $id,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['s']) && $data['s'] === true) {
                return $data['d'] ?? null;
            }
        }

        Log::error("Accurate API Get Purchase Invoice Detail Failed ({$databaseSource}): " . $response->body());
        return null;
    }

    public function getPurchaseOrders($databaseSource = 'syihab', $status = null)
    {
        list($host, $token, $secretKey) = $this->getCredentials($databaseSource);

        $allOrders = [];
        $page = 1;
        $hasMore = true;

        while ($hasMore) {
            $timestamp = now()->toIso8601String();
            $signature = hash_hmac('sha256', $timestamp, $secretKey);

            $params = [
                'sp.pageSize' => 100,
                'sp.page' => $page,
                'sp.sort' => 'id|desc'
            ];

            // Filter for unreceived POs typically status is OPEN or PARTIAL, wait we will just pull list and filter if needed
            // Accurate PO statuses: UNAPPROVED, APPROVED, CLOSED, REJECTED, WAITING_RECEIPT

            $response = Http::withHeaders([
                'Authorization'   => 'Bearer ' . $token,
                'X-Api-Timestamp' => $timestamp,
                'X-Api-Signature' => $signature,
                'Content-Type'    => 'application/json',
            ])->get($host . '/purchase-order/list.do', $params);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['s']) && $data['s'] === true) {
                    $chunk = $data['d'] ?? [];
                    $allOrders = array_merge($allOrders, $chunk);

                    $pageCount = $data['sp']['pageCount'] ?? 1;
                    if ($page >= $pageCount || count($chunk) < 100) {
                        $hasMore = false;
                    } else {
                        $page++;
                    }
                } else {
                    Log::error("Accurate API Get Purchase Order List Error ({$databaseSource}): " . json_encode($data));
                    break;
                }
            } else {
                Log::error("Accurate API Get Purchase Order List Failed ({$databaseSource}): " . $response->body());
                $hasMore = false;
            }
        }

        return $allOrders;
    }

    public function getPurchaseOrderDetail($id, $databaseSource = 'syihab')
    {
        list($host, $token, $secretKey) = $this->getCredentials($databaseSource);

        $timestamp = now()->toIso8601String();
        $signature = hash_hmac('sha256', $timestamp, $secretKey);

        $response = Http::withHeaders([
            'Authorization'   => 'Bearer ' . $token,
            'X-Api-Timestamp' => $timestamp,
            'X-Api-Signature' => $signature,
            'Content-Type'    => 'application/json',
        ])->get($host . '/purchase-order/detail.do', [
            'id' => $id,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['s']) && $data['s'] === true) {
                return $data['d'] ?? null;
            }
        }

        Log::error("Accurate API Get Purchase Order Detail Failed ({$databaseSource}): " . $response->body());
        return null;
    }

    /**
     * Proses penggantian unit garansi (Retur).
     * Memanggil API Accurate untuk Sales Return (menarik stok rusak) 
     * dan Sales Invoice (mengeluarkan stok baru).
     */
    public function processWarrantyReplacement(\App\Models\WarrantyClaim $claim, $newImei, $newItemNo = null, $newPrice = 0, $priceDifference = 0, $replacementType = 'same', $bankNo = null, $originalPriceFromUI = null)
    {
        $businessUnitCode = $claim->warranty->policy->businessUnit->code ?? 'syihab';

        // 1. Ambil Data Referensi dari Database
        // Ambil No Pelanggan yang TEPAT sesuai dengan Business Unit tempat garansi ini diterbitkan
        $customerNo = $claim->customer ? $claim->customer->getAccurateCustomerNo($businessUnitCode) : 'UMUM';
        $customerName = $claim->customer->name ?? 'Pelanggan Garansi';

        // Simulasi mendapatkan nomor invoice lama dan item lama dari order system
        $order = $claim->warranty->orderItem->order ?? null;
        $originalInvoiceNo = $order->accurate_invoice_no ?? $order->order_number ?? 'INV-UNKNOWN';

        // Ambil Item No (SKU) dari relasi Variant
        $variant = $claim->warranty->orderItem->variant ?? null;
        $originalItemNo = 'UNKNOWN-SKU';

        if ($variant) {
            // Jika variant langsung dari ProductAccurate
            if (isset($variant->item_no)) {
                $originalItemNo = $variant->item_no;
            }
            // Jika dari SecondProductVariant atau ProductVariant (punya relasi ke accurateData)
            elseif ($variant->accurateData) {
                $originalItemNo = $variant->accurateData->item_no;
            }
            // Fallback: coba panggil accurateData() sebagai method
            elseif (method_exists($variant, 'accurateData') && $variant->accurateData()->first()) {
                $originalItemNo = $variant->accurateData()->first()->item_no;
            }
        }

        $targetItemNo = $newItemNo ?? $originalItemNo;
        $originalPrice = $originalPriceFromUI ?? ($claim->warranty->orderItem->price_at_checkout ?? 0);
        $targetPrice = $newPrice > 0 ? $newPrice : $originalPrice;
        $chequeAmount = $priceDifference;

        // Ambil Nama Cabang berdasarkan User yang login
        $branchName = Auth::user()->branch->name ?? 'Cabang Utama';

        // Gudang Retur idealnya diambil dari settingan Business Unit,
        // Contoh: $claim->warranty->policy->businessUnit->settings['return_warehouse'] ?? 'GSK - Return'
        $warehouseReturnName = 'GSK - Return';
        $warehouseMainName = Auth::user()->warehouse->name ?? 'Gudang Utama';

        // --- PROSES 1: SALES RETURN (MENARIK IMEI LAMA) ---
        Log::info("Mempersiapkan Sales Return ke Accurate untuk IMEI Lama: " . $claim->serial_number);

        // Payload standar retur industri (Terhubung ke faktur lama agar memotong piutang/jadi overpayment)
        $returnPayload = [
            'customerNo' => $customerNo,
            'invoiceNumber' => $originalInvoiceNo, // <--- Relasi ke faktur lama dihidupkan kembali
            'returnDate' => now()->format('d/m/Y'),
            'branchName' => $branchName,
            'taxable' => false, // Nonaktifkan pajak agar nilai retur pas
            'inclusiveTax' => false,
            'description' => "Retur Klaim Garansi Ganti Unit. Referensi Faktur: {$originalInvoiceNo}. SN Rusak: {$claim->serial_number}",
            'detailItem' => [
                [
                    'itemNo' => $originalItemNo,
                    'quantity' => 1,
                    'unitPrice' => $originalPrice, // WAJIB ADA agar menjadi deposit walau invoice tak ketemu
                    'itemDiscount' => 0, // Hindari diskon bawaan
                    'warehouseName' => $warehouseReturnName,
                    'detailSerialNumber' => [
                        ['serialNumberNo' => $claim->serial_number, 'quantity' => 1]
                    ]
                ]
            ]
        ];

        Log::info("Payload Sales Return:", $returnPayload);
        $this->postSalesReturn($returnPayload, $businessUnitCode);

        // --- PROSES 2: SALES INVOICE (MENGELUARKAN IMEI BARU) ---
        Log::info("Mempersiapkan Sales Invoice ke Accurate untuk IMEI Baru: " . $newImei);

        // Payload standar pengeluaran barang pengganti
        $invoicePayload = [
            'customerNo' => $customerNo,
            'transDate' => now()->format('d/m/Y'),
            'branchName' => $branchName,
            'taxable' => false, // Nonaktifkan pajak agar piutang pas
            'inclusiveTax' => false,
            'description' => "Penggantian Unit Klaim Garansi untuk Faktur: {$originalInvoiceNo}. SN Pengganti: {$newImei}",
            'detailItem' => [
                [
                    'itemNo' => $targetItemNo,
                    'quantity' => 1,
                    'unitPrice' => $targetPrice, // Harga menggunakan harga asli/baru
                    'itemDiscount' => 0, // Hindari diskon bawaan
                    'warehouseName' => $warehouseMainName,
                    'detailSerialNumber' => [
                        ['serialNumberNo' => $newImei, 'quantity' => 1]
                    ]
                ]
            ]
        ];

        Log::info("Payload Sales Invoice:", $invoicePayload);
        $invoiceResponse = $this->postSalesInvoice($invoicePayload, $businessUnitCode);
        $newInvoiceNo = $invoiceResponse['r']['number'] ?? null;

        if (!$newInvoiceNo) {
            Log::warning("Gagal mendapatkan nomor invoice baru dari respon Accurate, otomatisasi pelunasan dilewati.");
            return true;
        }

        // --- PROSES 3: SALES RECEIPT (SETTLEMENT / REFUND) ---
        Log::info("Mempersiapkan Sales Receipt untuk pelunasan Invoice Baru: {$newInvoiceNo} menggunakan overpayment Invoice Lama: {$originalInvoiceNo}");

        $finalBankNo = $bankNo ?: '110101'; // Gunakan parameter bank, jika kosong fallback ke Kas default

        // Logika Offsetting Piutang (Kelebihan bayar vs Tagihan baru)
        if ($chequeAmount < 0) {
            // Downgrade: Kita hanya menarik saldo dari faktur lama SEBESAR harga faktur baru
            // Sisa overpayment akan tetap tertinggal di faktur lama
            $oldInvoiceDeduction = -$targetPrice;
            $actualCheque = 0;
            $descSuffix = " (Sisa overpayment Rp " . number_format(abs($chequeAmount), 0, ',', '.') . " tertinggal di faktur lama)";
        } else {
            // Upgrade atau 1:1: Kita menarik SELURUH saldo faktur lama (originalPrice)
            $oldInvoiceDeduction = -$originalPrice;
            $actualCheque = $chequeAmount; // >0 jika upgrade, 0 jika 1:1
            $descSuffix = "";
        }

        $receiptPayload = [
            'customerNo' => $customerNo,
            'bankNo' => $finalBankNo,
            'transDate' => now()->format('d/m/Y'),
            'branchName' => $branchName,
            'chequeAmount' => $actualCheque,
            'useCredit' => false, // Kita tidak pakai deposit terpisah, kita pakai sistem offset invoice
            'description' => "Pelunasan Ganti Unit. Faktur Baru: {$newInvoiceNo}. Potong Faktur Lama: {$originalInvoiceNo}{$descSuffix}",
            'detailInvoice' => [
                [
                    'invoiceNo' => $originalInvoiceNo,
                    'paymentAmount' => $oldInvoiceDeduction // Tarik saldo minus dari faktur lama
                ],
                [
                    'invoiceNo' => $newInvoiceNo,
                    'paymentAmount' => $targetPrice // Bayar faktur baru
                ]
            ]
        ];

        Log::info("Payload Sales Receipt (Offsetting):", $receiptPayload);

        try {
            $this->postSalesReceipt($receiptPayload, $businessUnitCode);
        } catch (\Exception $e) {
            Log::error("Sales Receipt Gagal: " . $e->getMessage());
            throw $e;
        }

        return true;
    }


    /**
     * Delete Sales Receipt in Accurate
     * 
     * @param int|string $id
     * @param string $databaseSource
     * @return array|bool
     * @throws \Exception
     */
    public function deleteSalesReceipt($id, $databaseSource = 'syihab')
    {
        $config = $this->getHeaders($databaseSource);

        $response = Http::withHeaders($config['headers'])
            ->post($config['host'] . '/sales-receipt/delete.do', ['id' => $id]);

        Log::info("API Accurate Delete Sales Receipt ({$databaseSource}) ID: {$id} Response: " . $response->body());

        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['s']) && $data['s'] === false) {
                $errorMsg = isset($data['d']) && is_array($data['d']) ? implode(', ', $data['d']) : json_encode($data);
                throw new \Exception('API Accurate Error Delete SR: ' . $errorMsg);
            }
            return $data['d'] ?? true;
        } else {
            Log::error("API Accurate Delete SR Error ({$databaseSource}): " . $response->body());
            throw new \Exception('API Accurate HTTP Error: ' . $response->status() . ' - ' . $response->body());
        }
    }

    /**
     * Delete Sales Invoice in Accurate
     * 
     * @param int|string $id
     * @param string $databaseSource
     * @return array|bool
     * @throws \Exception
     */
    public function deleteSalesInvoice($id, $databaseSource = 'syihab')
    {
        $config = $this->getHeaders($databaseSource);

        $response = Http::withHeaders($config['headers'])
            ->post($config['host'] . '/sales-invoice/delete.do', ['id' => $id]);

        Log::info("API Accurate Delete Sales Invoice ({$databaseSource}) ID: {$id} Response: " . $response->body());

        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['s']) && $data['s'] === false) {
                $errorMsg = isset($data['d']) && is_array($data['d']) ? implode(', ', $data['d']) : json_encode($data);
                throw new \Exception('API Accurate Error Delete SI: ' . $errorMsg);
            }
            return $data['d'] ?? true;
        } else {
            Log::error("API Accurate Delete SI Error ({$databaseSource}): " . $response->body());
            throw new \Exception('API Accurate HTTP Error: ' . $response->status() . ' - ' . $response->body());
        }
    }
    public function deleteDeliveryOrder($id, $databaseSource = 'syihab')
    {
        $config = $this->getHeaders($databaseSource);

        $response = Http::withHeaders($config['headers'])
            ->post($config['host'] . '/delivery-order/delete.do', ['id' => $id]);

        Log::info("API Accurate Delete Delivery Order ({$databaseSource}) ID: {$id} Response: " . $response->body());

        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['s']) && $data['s'] === false) {
                $errorMsg = isset($data['d']) && is_array($data['d']) ? implode(', ', $data['d']) : json_encode($data);
                throw new \Exception('API Accurate Error Delete DO: ' . $errorMsg);
            }
            return $data['d'] ?? true;
        } else {
            Log::error("API Accurate Delete DO Error ({$databaseSource}): " . $response->body());
            throw new \Exception('API Accurate HTTP Error: ' . $response->status() . ' - ' . $response->body());
        }
    }

    public function deleteSalesOrder($id, $databaseSource = 'syihab')
    {
        $config = $this->getHeaders($databaseSource);

        $response = Http::withHeaders($config['headers'])
            ->post($config['host'] . '/sales-order/delete.do', ['id' => $id]);

        Log::info("API Accurate Delete Sales Order ({$databaseSource}) ID: {$id} Response: " . $response->body());

        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['s']) && $data['s'] === false) {
                $errorMsg = isset($data['d']) && is_array($data['d']) ? implode(', ', $data['d']) : json_encode($data);
                throw new \Exception('API Accurate Error Delete SO: ' . $errorMsg);
            }
            return $data['d'] ?? true;
        } else {
            Log::error("API Accurate Delete SO Error ({$databaseSource}): " . $response->body());
            throw new \Exception('API Accurate HTTP Error: ' . $response->status() . ' - ' . $response->body());
        }
    }

    /**
     * Rollback order documents in Accurate with correct Bottom-Up order.
     */
    public function rollbackOrderDocuments(\App\Models\Order $order)
    {
        $dbSource = strtolower($order->businessUnit->code ?? 'syihab');
        $docs = $order->accurateDocs;

        // Correct Deletion Order: SALES_RECEIPT -> SALES_INVOICE -> DP_RECEIPT -> DP_INVOICE -> DELIVERY_ORDER -> SALES_ORDER
        $orderedTypes = ['SALES_RECEIPT', 'receipt', 'DP_RECEIPT', 'SALES_INVOICE', 'invoice', 'DP_INVOICE', 'DELIVERY_ORDER', 'SALES_ORDER'];

        foreach ($orderedTypes as $type) {
            $matchedDocs = $docs->where('doc_type', $type)->where('status', 'SUCCESS');
            foreach ($matchedDocs as $doc) {
                if ($doc->accurate_id) {
                    if (in_array($type, ['SALES_RECEIPT', 'receipt', 'DP_RECEIPT'])) {
                        $this->deleteSalesReceipt($doc->accurate_id, $dbSource);
                    } elseif (in_array($type, ['SALES_INVOICE', 'invoice', 'DP_INVOICE'])) {
                        $this->deleteSalesInvoice($doc->accurate_id, $dbSource);
                    } elseif ($type === 'DELIVERY_ORDER') {
                        $this->deleteDeliveryOrder($doc->accurate_id, $dbSource);
                    } elseif ($type === 'SALES_ORDER') {
                        $this->deleteSalesOrder($doc->accurate_id, $dbSource);
                    }
                    $doc->update(['status' => 'CANCELLED']);
                }
            }
        }
    }

    /**
     * Memproses pencairan refund (uang keluar) untuk kasus Downgrade.
     * Menggunakan Sales Receipt dengan nilai chequeAmount minus.
     */
    public function processDowngradeRefund(\App\Models\WarrantyClaim $claim, $bankNo, $refundAmount)
    {
        $businessUnitCode = $claim->warranty->policy->businessUnit->code ?? 'syihab';

        $customerNo = $claim->customer ? $claim->customer->getAccurateCustomerNo($businessUnitCode) : 'UMUM';
        $order = $claim->warranty->orderItem->order ?? null;
        $originalInvoiceNo = $order->accurate_invoice_no ?? $order->order_number ?? 'INV-UNKNOWN';

        $branchName = 'GSK - Banjarbaru'; // Harusnya dari BusinessUnit, tapi sementara hardcode sesuai current logic

        // Payload Penerimaan Penjualan (Uang Keluar)
        $receiptPayload = [
            'customerNo' => $customerNo,
            'bankNo' => $bankNo,
            'transDate' => now()->format('d/m/Y'),
            'branchName' => $branchName,
            'chequeAmount' => -$refundAmount, // Minus = Uang Keluar
            'useCredit' => false,
            'description' => "Pencairan Tunai / Refund Sisa Kelebihan Bayar atas Downgrade Klaim Garansi untuk Faktur Lama: {$originalInvoiceNo}",
            'detailInvoice' => [
                [
                    'invoiceNo' => $originalInvoiceNo,
                    'paymentAmount' => -$refundAmount // Tarik dari sisa saldo faktur
                ]
            ]
        ];

        Log::info("Payload Pencairan Refund (Uang Keluar):", $receiptPayload);

        try {
            $this->postSalesReceipt($receiptPayload, $businessUnitCode);
        } catch (\Exception $e) {
            Log::error("Pencairan Refund Gagal: " . $e->getMessage());
            throw $e;
        }

        return true;
    }
}

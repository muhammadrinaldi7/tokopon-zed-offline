<?php

namespace App\Webhooks\Accurate;

use App\Http\Controllers\Controller;
use App\Models\AccurateWebhookLog;
use App\Models\User;
use App\Services\AccurateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CustomerSaveHandler extends Controller implements WebhookHandlerInterface
{
        public function handle(AccurateWebhookLog $log): void
    {
        $payload = $log->payload;
        $dbSource = $log->database_source;

        if (isset($payload['data']) && is_array($payload['data'])) {
            foreach ($payload['data'] as $vendorData) {
                $customerNo = $vendorData['customerNo'] ?? null;
                $vendorId = $vendorData['vendorId'] ?? null;
                $action = $vendorData['action'] ?? 'WRITE';

                if ($customerNo) {
                    if ($action === 'WRITE') {
                        $this->syncCustomerDetail($customerNo, $vendorId, $dbSource);
                    } elseif ($action === 'DELETE') {
                        $this->handleDeletedCustomer($customerNo);
                    }
                }
            }
        }
    }

    private function syncCustomerDetail($customerNo, $customerId, $dbSource)
    {
        try {
            $service = app(AccurateService::class);
            $accurateCustomer = $service->getCustomerDetail($customerNo, $dbSource);

            if ($accurateCustomer) {
                // Mapping field dari response Accurate
                $name = $accurateCustomer['name'] ?? null;
                $email = $accurateCustomer['email'] ?: "customer_{$customerNo}@no-email.com";
                // Kadang field phone ada di mobilePhone atau workPhone
                $phone = $accurateCustomer['mobilePhone'] ?? $accurateCustomer['workPhone'] ?? null;
                $accurateCustomerId = $accurateCustomer['id'] ?? $customerId;
                $customerNo = $accurateCustomer['customerNo'] ?? $customerNo;
                $user = User::updateOrCreate(
                    [
                        // Kita gunakan accurate_customer_id sebagai acuan pencarian yang utama
                        'accurate_customer_id' => $accurateCustomerId
                    ],
                    [
                        'accurate_customer_no' => $accurateCustomer['customerNo'] ?? $customerNo,
                        'name' => $name,
                        'email' => $email,
                    ]
                );

                if ($user) {
                    $user->profile()->updateOrCreate(
                        ['user_id' => $user->id],
                        [
                            'full_name' => $name,
                            'phone_number' => $phone
                        ]
                    );
                }

                Log::info("Webhook Berhasil: Customer Updated via Webhook: Customer No {$customerNo} | Name: {$name}");
            }
        } catch (\Exception $e) {
            Log::error("Webhook Gagal: Gagal update customer Customer No {$customerNo}. Error: " . $e->getMessage());
        }
    }

    private function handleDeletedCustomer($customerNo)
    {
        try {
            $user = User::where('accurate_customer_no', $customerNo)->first();
            if ($user) {
                // Hapus langsung customer di DB lokal sesuai konfirmasi User
                $user->delete();
                Log::info("Customer Dihapus via Webhook Accurate: Customer No {$customerNo}");
            }
        } catch (\Exception $e) {
            Log::error("Webhook Gagal: Gagal menghapus customer Customer No {$customerNo}. Error: " . $e->getMessage());
        }
    }
}

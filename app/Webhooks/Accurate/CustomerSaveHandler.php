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
                        $this->handleDeletedCustomer($customerNo, $dbSource);
                    }
                }
            }
        }
    }

    private function syncCustomerDetail($customerNo, $customerId, $dbSource)
    {
        try {
            $businessUnitId = \App\Models\BusinessUnit::where('code', $dbSource)->value('id');

            // Cek apakah customer sudah tersinkron di DB lokal
            $existsLocal = \App\Models\UserAccurateCustomer::where('accurate_customer_no', $customerNo)
                ->when($businessUnitId, function($q) use ($businessUnitId) {
                    return $q->where('business_unit_id', $businessUnitId);
                })
                ->exists();

            if ($existsLocal) {
                Log::info("Webhook Customer diabaikan (Echo): Customer No {$customerNo} sudah tersinkronisasi dari POS.");
                return;
            }

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

                $userAccurate = \App\Models\UserAccurateCustomer::where('accurate_customer_id', $accurateCustomerId)
                    ->when($businessUnitId, function($q) use ($businessUnitId) {
                        return $q->where('business_unit_id', $businessUnitId);
                    })
                    ->first();

                if ($userAccurate && $userAccurate->user) {
                    $user = $userAccurate->user;
                    $user->update([
                        'name' => $name,
                        'email' => $email,
                    ]);
                    $userAccurate->update([
                        'accurate_customer_no' => $accurateCustomer['customerNo'] ?? $customerNo
                    ]);
                } else {
                    $user = User::create([
                        'name' => $name,
                        'email' => $email,
                        'business_unit_id' => $businessUnitId,
                    ]);

                    \App\Models\UserAccurateCustomer::create([
                        'user_id' => $user->id,
                        'business_unit_id' => $businessUnitId,
                        'accurate_customer_id' => $accurateCustomerId,
                        'accurate_customer_no' => $accurateCustomer['customerNo'] ?? $customerNo,
                    ]);
                }

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

    private function handleDeletedCustomer($customerNo, $dbSource)
    {
        try {
            $businessUnitId = \App\Models\BusinessUnit::where('code', $dbSource)->value('id');

            $userAccurate = \App\Models\UserAccurateCustomer::where('accurate_customer_no', $customerNo)
                ->when($businessUnitId, function($q) use ($businessUnitId) {
                    return $q->where('business_unit_id', $businessUnitId);
                })
                ->first();

            if ($userAccurate && $userAccurate->user) {
                $user = $userAccurate->user;
                // Hapus langsung customer di DB lokal sesuai konfirmasi User
                $user->delete();
                Log::info("Customer Dihapus via Webhook Accurate: Customer No {$customerNo}");
            }
        } catch (\Exception $e) {
            Log::error("Webhook Gagal: Gagal menghapus customer Customer No {$customerNo}. Error: " . $e->getMessage());
        }
    }
}

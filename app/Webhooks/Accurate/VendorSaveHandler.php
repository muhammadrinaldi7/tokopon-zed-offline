<?php

namespace App\Webhooks\Accurate;

use App\Models\AccurateWebhookLog;
use App\Models\Vendor;
use App\Services\AccurateService;
use Illuminate\Support\Facades\Log;

class VendorSaveHandler implements WebhookHandlerInterface
{
    public function handle(AccurateWebhookLog $log): void
    {
        $payload = $log->payload;
        $dbSource = $log->database_source;

        if (isset($payload['data']) && is_array($payload['data'])) {
            foreach ($payload['data'] as $vendorData) {
                $vendorNo = $vendorData['vendorNo'] ?? null;
                $vendorId = $vendorData['vendorId'] ?? null;
                $action = $vendorData['action'] ?? 'WRITE';

                if ($vendorNo) {
                    if ($action === 'WRITE') {
                        $this->syncVendorDetail($vendorNo, $vendorId, $dbSource);
                    } elseif ($action === 'DELETE') {
                        $this->handleDeletedVendor($vendorNo, $dbSource);
                    }
                }
            }
        }
    }

    private function syncVendorDetail($vendorNo, $vendorId, $dbSource)
    {
        try {
            $service = app(AccurateService::class);
            $accurateVendor = $service->getVendorDetail($vendorNo, $dbSource);

            if ($accurateVendor) {
                // Mapping field dari response Accurate
                $name = $accurateVendor['name'] ?? null;
                $email = $accurateVendor['email'] ?? null;
                // Kadang field phone ada di mobilePhone atau workPhone
                $phone = $accurateVendor['mobilePhone'] ?? $accurateVendor['workPhone'] ?? null;
                $accurateVendorId = $accurateVendor['id'] ?? $vendorId;

                Vendor::updateOrCreate(
                    [
                        // Kita gunakan vendor_no sebagai acuan pencarian yang utama atau accurate_vendor_id
                        'accurate_vendor_id' => $accurateVendorId,
                        'database_source' => $dbSource,
                    ],
                    [
                        'vendor_no' => $accurateVendor['vendorNo'] ?? $vendorNo,
                        'vendor_name' => $name,
                        'email' => $email,
                        'phone' => $phone,
                    ]
                );

                Log::info("Webhook Berhasil: Vendor Updated via Webhook: Vendor No {$vendorNo} | Name: {$name}");
            }
        } catch (\Exception $e) {
            Log::error("Webhook Gagal: Gagal update vendor Vendor No {$vendorNo}. Error: " . $e->getMessage());
        }
    }

    private function handleDeletedVendor($vendorNo, $dbSource)
    {
        try {
            $vendor = Vendor::where('vendor_no', $vendorNo)
                            ->where('database_source', $dbSource)
                            ->first();
            if ($vendor) {
                // Hapus langsung vendor di DB lokal sesuai konfirmasi User
                $vendor->delete();
                Log::info("Vendor Dihapus via Webhook Accurate: Vendor No {$vendorNo} ({$dbSource})");
            }
        } catch (\Exception $e) {
            Log::error("Webhook Gagal: Gagal menghapus vendor Vendor No {$vendorNo}. Error: " . $e->getMessage());
        }
    }
}

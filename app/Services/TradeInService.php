<?php

namespace App\Services;

use App\Models\User;
use App\Models\TradeIn as TradeInModel;
use App\Models\BuybackDevice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TradeInService
{
    /**
     * Calculate the final appraised value of the old device based on selected condition rules.
     */
    public function calculatePrice(BuybackDevice $device, array $selectedRules): float
    {
        $price = $device->base_price;
        $rulesByKey = collect($device->getFlatRules())->keyBy('key');

        foreach ($selectedRules as $ruleKey => $isChecked) {
            if ($isChecked) {
                $rule = $rulesByKey->get($ruleKey);
                if ($rule) {
                    $type = $rule['type'];
                    $val = $rule['value'];

                    if ($type === 'fixed') {
                        $price -= $val;
                    } elseif ($type === 'percentage') {
                        $price -= ($device->base_price * ($val / 100));
                    }
                }
            }
        }

        return max(0, $price); // Ensure price doesn't go below 0
    }

    /**
     * Format the selected condition rules into a readable string description.
     */
    public function formatMinusDescription(array $deviceRules, array $selectedRules, ?string $additionalNote): string
    {
        $rulesByKey = collect($deviceRules)->keyBy('key');
        $groupedSelections = [];

        foreach ($selectedRules as $key => $value) {
            $ruleId = null;
            if (is_bool($value) && $value) {
                $ruleId = $key;
            } elseif (is_string($value) && !empty($value)) {
                $ruleId = $value;
            }

            if ($ruleId) {
                $rule = $rulesByKey->get($ruleId);
                if ($rule) {
                    $categoryName = $rule['category'];
                    $groupedSelections[$categoryName][] = $rule['name'];
                }
            }
        }

        $formattedConditions = [];
        foreach ($groupedSelections as $category => $items) {
            $joinedItems = implode(', ', $items);
            $formattedConditions[] = "{$category}: {$joinedItems}";
        }

        $kondisi = !empty($formattedConditions)
            ? implode(' | ', $formattedConditions)
            : 'Mulus / Normal';

        $catatanText = $additionalNote ? ". Catatan Tambahan: {$additionalNote}" : "";

        return "{$kondisi}{$catatanText}";
    }

    /**
     * Register a new offline customer when the transaction is handled by a FrontLiner.
     */
    public function registerOfflineCustomer(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $newUser = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => bcrypt('tokopun123'),
            ]);
            $newUser->assignRole('user');

            $newUser->profile()->create([
                'full_name' => $data['name'],
                'phone_number' => $data['mobilePhone'],
            ]);

            $newUser->update([
                'identity' => $data['nik'],
                'npwp' => $data['npwp'] ?? null,
            ]);

            if (isset($data['foto_ktp']) && $data['foto_ktp']) {
                $newUser->addMedia($data['foto_ktp']->getRealPath())
                    ->usingFileName($data['foto_ktp']->getClientOriginalName())
                    ->toMediaCollection('ktp_photo');
            }

            $newUser->bankAccounts()->create([
                'bank_name' => $data['bank_name'],
                'account_number' => $data['account_number'],
                'account_name' => $data['account_name'],
                'is_primary' => true,
            ]);


            return $newUser;
        });
    }

    /**
     * Create the Trade In record and attach uploaded photos.
     */
    public function createTradeInRequest(array $data, array $photos): TradeInModel
    {
        return DB::transaction(function () use ($data, $photos) {
            $tradeIn = TradeInModel::create($data);

            $slots = [
                'photo_depan' => 'Tampak Depan',
                'photo_belakang' => 'Tampak Belakang',
                'photo_kiri' => 'Samping Kiri',
                'photo_kanan' => 'Samping Kanan',
                'photo_kelengkapan' => 'Kelengkapan',
            ];

            foreach ($slots as $propertyName => $label) {
                if (isset($photos[$propertyName]) && $photos[$propertyName]) {
                    $photo = $photos[$propertyName];

                    $tradeIn->addMedia($photo->getRealPath())
                        ->usingFileName($photo->getClientOriginalName())
                        ->withCustomProperties([
                            'position' => str_replace('photo_', '', $propertyName),
                            'label' => $label
                        ])
                        ->toMediaCollection('photos');
                }
            }

            return $tradeIn;
        });
    }
}

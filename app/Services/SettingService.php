<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class SettingService
{
    private const CACHE_PREFIX = 'system_setting_';

    /**
     * Set a configuration value.
     */
    public function set(string $key, $value, string $type = 'string'): void
    {
        $storeValue = $value;

        if ($type === 'encrypted' && !empty($value)) {
            $storeValue = Crypt::encryptString((string) $value);
        } elseif ($type === 'json' && is_array($value)) {
            $storeValue = json_encode($value);
        } elseif ($type === 'boolean') {
            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            $storeValue = $value ? '1' : '0';
        }

        Setting::updateOrCreate(
            ['key' => $key],
            [
                'value' => $storeValue,
                'type' => $type,
            ]
        );

        Cache::put(self::CACHE_PREFIX . $key, $value);
    }

    /**
     * Get a configuration value.
     */
    public function get(string $key, $default = null)
    {
        return Cache::rememberForever(self::CACHE_PREFIX . $key, function () use ($key, $default) {
            $setting = Setting::where('key', $key)->first();

            if (!$setting) {
                return $default;
            }

            if ($setting->type === 'encrypted' && !empty($setting->value)) {
                try {
                    return Crypt::decryptString($setting->value);
                } catch (\Exception $e) {
                    return $default; // if decryption fails (e.g., APP_KEY changed)
                }
            }

            if ($setting->type === 'json') {
                return json_decode($setting->value, true);
            }

            if ($setting->type === 'boolean') {
                return filter_var($setting->value, FILTER_VALIDATE_BOOLEAN);
            }

            if ($setting->type === 'integer') {
                return (int) $setting->value;
            }

            return $setting->value;
        });
    }

    /**
     * Remove a configuration value.
     */
    public function delete(string $key): void
    {
        Setting::where('key', $key)->delete();
        Cache::forget(self::CACHE_PREFIX . $key);
    }
}

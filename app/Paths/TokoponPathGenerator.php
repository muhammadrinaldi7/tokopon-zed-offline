<?php

namespace App\Paths;

use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

class TokoponPathGenerator implements PathGenerator
{
    /*
     * Path untuk file asli
     */
    public function getPath(Media $media): string
    {
        // 1. TradeIn -> tradein/{model_id}/
        if ($media->model_type === 'App\Models\TradeIn') {
            return 'tradein/' . $media->model_id . '/';
        }

        // 2. SellPhone (Beli HP / Buyback) -> sellphone/{model_id}/
        if ($media->model_type === 'App\Models\SellPhone') {
            return 'sellphone/' . $media->model_id . '/';
        }

        // 3. DeviceInspection (QC / Garansi) -> device_inspections/{model_id}/
        if ($media->model_type === 'App\Models\DeviceInspection') {
            return 'device_inspections/' . $media->model_id . '/';
        }

        // 4. User (Foto KTP / Profil) -> users/{model_id}/
        if ($media->model_type === 'App\Models\User') {
            return 'users/' . $media->model_id . '/';
        }

        // 5. Product / ProductVariant -> products/{model_id}/
        if ($media->model_type === 'App\Models\Product' || $media->model_type === 'App\Models\ProductAccurate') {
            return 'products/' . $media->model_id . '/';
        }

        // 6. Generic Fallback Otomatis untuk model masa depan -> {plural_snake_case_model}/{model_id}/
        if ($media->model_type && $media->model_id) {
            $folderName = Str::snake(Str::plural(class_basename($media->model_type)));
            return $folderName . '/' . $media->model_id . '/';
        }

        // Fallback jika tidak ada model_id
        return $media->id . '/';
    }

    /*
     * Path untuk konversi (thumbnail dll)
     */
    public function getPathForConversions(Media $media): string
    {
        return $this->getPath($media) . 'conversions/';
    }

    /*
     * Path untuk responsive images
     */
    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getPath($media) . 'responsive/';
    }
}

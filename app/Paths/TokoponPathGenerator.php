<?php

namespace App\Paths;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

class TokoponPathGenerator implements PathGenerator
{
    /*
     * Path untuk file asli
     */
    public function getPath(Media $media): string
    {
        // Jika modelnya adalah TradeIn, gunakan folder tradein/{id}/
        if ($media->model_type === 'App\Models\TradeIn') {
            return 'tradein/' . $media->model_id . '/';
        }

        // Jika modelnya adalah SellPhone, gunakan folder sellphone/{id}/
        if ($media->model_type === 'App\Models\SellPhone') {
            return 'sellphone/' . $media->model_id . '/';
        }

        // Default untuk model lain (misal Product tetap di folder ID Media)
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

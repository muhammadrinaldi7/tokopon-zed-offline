<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class R2MediaProxyController extends Controller
{
    /**
     * Stream file media langsung dari Cloudflare R2 Bucket.
     * Mengatasi blokir domain public r2.dev oleh ISP Indonesia (MyRepublic/Internet Positif).
     */
    public function show(string $path)
    {
        $disk = Storage::disk('r2');

        if (!$disk->exists($path)) {
            abort(404, 'File media tidak ditemukan di Cloudflare R2.');
        }

        $mime = $disk->mimeType($path) ?: 'application/octet-stream';
        $size = $disk->size($path);

        return response()->stream(function () use ($disk, $path) {
            $stream = $disk->readStream($path);
            if (is_resource($stream)) {
                fpassthru($stream);
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $mime,
            'Content-Length' => $size,
            'Cache-Control' => 'public, max-age=2592000, immutable',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, HEAD, OPTIONS',
        ]);
    }
}

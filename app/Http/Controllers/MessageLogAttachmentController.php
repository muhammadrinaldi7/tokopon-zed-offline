<?php

namespace App\Http\Controllers;

use App\Models\MessageLog;
use App\Models\Order;
use App\Models\SellPhone;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MessageLogAttachmentController extends Controller
{
    /**
     * Menampilkan atau mengunduh berkas lampiran (PDF Struk / Bukti Bayar) dari Message Log.
     */
    public function show($id)
    {
        $messageLog = MessageLog::findOrFail($id);
        $filename = $messageLog->attachment_name ?: 'Struk_Dokumen.pdf';

        // 1. Cek jika file fisik sudah ada di storage disk public
        if ($messageLog->attachment_url) {
            $storagePrefix = asset('storage') . '/';
            if (str_starts_with($messageLog->attachment_url, $storagePrefix)) {
                $relative = substr($messageLog->attachment_url, strlen($storagePrefix));
                if (Storage::disk('public')->exists($relative)) {
                    return Storage::disk('public')->response($relative, $filename, [
                        'Content-Disposition' => 'inline; filename="' . $filename . '"'
                    ]);
                }
            } elseif (filter_var($messageLog->attachment_url, FILTER_VALIDATE_URL) && !str_contains($messageLog->attachment_url, request()->getHost())) {
                return redirect()->away($messageLog->attachment_url);
            }
        }

        // Cek path standar receipts/
        $standardReceiptPath = 'receipts/' . $filename;
        if (Storage::disk('public')->exists($standardReceiptPath)) {
            return Storage::disk('public')->response($standardReceiptPath, $filename, [
                'Content-Disposition' => 'inline; filename="' . $filename . '"'
            ]);
        }

        // 2. Jika sumber berasal dari transaksi Penjualan (Order)
        $isOrder = ($messageLog->source_type === Order::class || $messageLog->source_type === 'App\Models\Order')
            || $messageLog->message_type === 'receipt_order'
            || str_starts_with($messageLog->reference_number ?? '', 'POS-')
            || str_starts_with($messageLog->reference_number ?? '', 'INV-');

        if ($isOrder) {
            $order = null;
            if ($messageLog->source_id) {
                $order = Order::with(['user.profile', 'items.variant', 'branch', 'paymentMethod', 'handledBy', 'salesBy', 'businessUnit'])
                    ->find($messageLog->source_id);
            }

            if (!$order && $messageLog->reference_number) {
                $order = Order::with(['user.profile', 'items.variant', 'branch', 'paymentMethod', 'handledBy', 'salesBy', 'businessUnit'])
                    ->where('order_number', $messageLog->reference_number)
                    ->first();
            }

            if ($order) {
                $order->loadMissing(['user.profile', 'items.variant', 'branch', 'paymentMethod', 'handledBy', 'salesBy', 'businessUnit']);

                if (!$order->created_at) {
                    $order->created_at = now();
                }

                $pdf = Pdf::loadView('pdf.receipt', compact('order'))
                    ->setPaper([0, 0, 226, 600], 'portrait');

                $outputFilename = $filename ?: ('Struk_' . $order->order_number . '.pdf');

                // Simpan ke storage agar request berikutnya langsung mengambil file
                try {
                    $path = 'receipts/' . $outputFilename;
                    Storage::disk('public')->put($path, $pdf->output());
                    $messageLog->update([
                        'attachment_url' => asset('storage/' . $path),
                        'attachment_name' => $outputFilename,
                    ]);
                } catch (\Throwable $e) {
                    // Abaikan jika storage read-only
                }

                return $pdf->stream($outputFilename);
            }
        }

        // 3. Jika sumber berasal dari Sell Phone (Beli HP Bekas)
        $isSellPhone = ($messageLog->source_type === SellPhone::class || $messageLog->source_type === 'App\Models\SellPhone')
            || $messageLog->message_type === 'receipt_sellphone'
            || $messageLog->message_type === 'payment_proof'
            || str_starts_with($messageLog->reference_number ?? '', 'SPL-');

        if ($isSellPhone) {
            $sellPhone = null;
            if ($messageLog->source_id) {
                $sellPhone = SellPhone::with(['handledBy', 'user.profile', 'user.bankAccounts', 'businessUnit', 'branch'])
                    ->find($messageLog->source_id);
            }

            if (!$sellPhone && $messageLog->reference_number) {
                $rawId = str_replace('SPL-', '', $messageLog->reference_number);
                $sellPhone = SellPhone::with(['handledBy', 'user.profile', 'user.bankAccounts', 'businessUnit', 'branch'])->find($rawId);
            }

            if ($sellPhone) {
                // Jika berupa bukti bayar transfer gambar/file
                if ($messageLog->message_type === 'payment_proof' && $sellPhone->payment_receipt_path) {
                    if (Storage::disk('public')->exists($sellPhone->payment_receipt_path)) {
                        return Storage::disk('public')->response($sellPhone->payment_receipt_path);
                    }
                }

                $sellPhone->loadMissing(['handledBy', 'user.profile', 'user.bankAccounts', 'businessUnit', 'branch']);
                $pdf = Pdf::loadView('pdf.sell-phone-receipt', ['sellPhone' => $sellPhone]);
                $pdf->setPaper([0, 0, 226.77, 520], 'portrait');

                $outputFilename = $filename ?: ('Tanda_Terima_SPL_' . $sellPhone->id . '.pdf');

                try {
                    $path = 'sell_phone_receipts/' . $outputFilename;
                    Storage::disk('public')->put($path, $pdf->output());
                    $messageLog->update([
                        'attachment_url' => asset('storage/' . $path),
                        'attachment_name' => $outputFilename,
                    ]);
                } catch (\Throwable $e) {
                    // Abaikan jika storage read-only
                }

                return $pdf->stream($outputFilename);
            }
        }

        abort(404, 'Dokumen lampiran tidak ditemukan atau data transaksi terkait sudah tidak tersedia.');
    }
}

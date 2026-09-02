<?php

namespace App\Mail;

use App\Models\SellPhone;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SellPhonePaymentReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public SellPhone $sellPhone;

    public function __construct(SellPhone $sellPhone)
    {
        $this->sellPhone = $sellPhone->loadMissing(['user.profile', 'user.bankAccounts', 'businessUnit', 'inspections']);
    }

    public function envelope(): Envelope
    {
        $storeName = $this->sellPhone->businessUnit->store_title ?? env('MAIL_POS_FROM_NAME', 'TOKOPON');
        $fromAddress = env('MAIL_POS_FROM_ADDRESS', config('mail.from.address', 'noreply@syihabstore.id'));

        return new Envelope(
            from: new Address($fromAddress, $storeName),
            subject: 'Bukti Pembayaran Jual HP - SPL-' . $this->sellPhone->id . ' (' . $this->sellPhone->phone_brand . ' ' . $this->sellPhone->phone_model . ')',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.sell_phone_payment_receipt',
        );
    }

    public function attachments(): array
    {
        $attachments = [];

        if ($this->sellPhone->payment_receipt_path) {
            $path = null;
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($this->sellPhone->payment_receipt_path)) {
                $path = \Illuminate\Support\Facades\Storage::disk('public')->path($this->sellPhone->payment_receipt_path);
            } elseif (file_exists(storage_path('app/public/' . $this->sellPhone->payment_receipt_path))) {
                $path = storage_path('app/public/' . $this->sellPhone->payment_receipt_path);
            } elseif (file_exists(public_path('storage/' . $this->sellPhone->payment_receipt_path))) {
                $path = public_path('storage/' . $this->sellPhone->payment_receipt_path);
            }

            if ($path && file_exists($path)) {
                $ext = pathinfo($path, PATHINFO_EXTENSION) ?: 'jpg';
                $mime = mime_content_type($path) ?: 'image/jpeg';
                $filename = 'Bukti_Transfer_SPL_' . $this->sellPhone->id . '.' . $ext;

                $attachments[] = Attachment::fromPath($path)
                    ->as($filename)
                    ->withMime($mime);
            }
        }

        return $attachments;
    }
}

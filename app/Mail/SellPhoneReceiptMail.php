<?php

namespace App\Mail;

use App\Models\SellPhone;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SellPhoneReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public $sellPhone;
    protected $pdfContent;
    protected $filename;

    public function __construct(SellPhone $sellPhone, $pdfContent, $filename)
    {
        $this->sellPhone = $sellPhone;
        $this->pdfContent = $pdfContent;
        $this->filename = $filename;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address(
                env('MAIL_POS_FROM_ADDRESS', 'noreply@syihabstore.id'),
                env('MAIL_POS_FROM_NAME', 'TOKOPON')
            ),
            subject: 'Struk Tanda Terima Pembelian HP - SPL-' . $this->sellPhone->id,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.sell_phone_receipt',
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn() => $this->pdfContent, $this->filename)
                ->withMime('application/pdf'),
        ];
    }
}

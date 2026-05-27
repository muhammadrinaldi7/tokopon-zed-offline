<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SalesReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    protected $pdfContent;
    protected $filename;

    // Konstruktor menerima 3 parameter sekarang
    public function __construct(Order $order, $pdfContent, $filename)
    {
        $this->order = $order;
        $this->pdfContent = $pdfContent;
        $this->filename = $filename;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address(
                env('MAIL_POS_FROM_ADDRESS', 'sales@tokopon.com'),
                env('MAIL_POS_FROM_NAME', 'TOKOPON Sales')
            ),
            subject: 'Struk Transaksi TOKOPON #' . $this->order->order_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.sales_receipt', // Mengarah ke tulisan body email
        );
    }

    public function attachments(): array
    {
        return [
            // Melampirkan PDF dari memori langsung
            Attachment::fromData(fn() => $this->pdfContent, $this->filename)
                ->withMime('application/pdf'),
        ];
    }
}

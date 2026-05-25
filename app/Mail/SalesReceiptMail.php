<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SalesReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    // ─── TAMBAHKAN BARIS INI CUY ──────────────────────────────
    public $order; 
    // ──────────────────────────────────────────────────────────

    /**
     * Create a new message instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            // Ambil dari variabel POS, jika kosong baru beralih ke teks default
            from: new \Illuminate\Mail\Mailables\Address(
                env('MAIL_POS_FROM_ADDRESS', 'sales@tokopun.com'),
                env('MAIL_POS_FROM_NAME', 'TOKOPUN Sales')
            ),
            subject: 'Struk Transaksi TOKOPUN #' . $this->order->order_number,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.sales-receipt',
            with: [
                'order' => $this->order,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}

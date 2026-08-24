<?php

namespace App\Mail;

use App\Models\PatientDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentReceived extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public PatientDocument $document) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.payment_received.subject', [
                'hospital' => setting('site_name', config('app.name')),
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.payment-received',
            text: 'mail.text.payment-received',
            with: [
                'document' => $this->document,
                'amount' => '৳'.number_format($this->document->amount),
                'paidDate' => now()->translatedFormat('j M, Y'),
            ],
        );
    }
}

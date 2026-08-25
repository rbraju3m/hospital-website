<?php

namespace App\Mail;

use App\Mail\Concerns\PresentsAppointment;
use App\Mail\Concerns\RecordsDelivery;
use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent when the desk confirms or cancels a booking.
 *
 * Only those two statuses reach the patient: `pending` is where a booking
 * starts, and `completed` is bookkeeping after a visit that already happened.
 * Rendered in the locale the patient booked in, not the staff member's.
 */
class AppointmentStatusChanged extends Mailable implements ShouldQueue
{
    use RecordsDelivery, PresentsAppointment, Queueable, SerializesModels;

    public const NOTIFIABLE = ['confirmed', 'cancelled'];

    public function __construct(public Appointment $appointment) {}

    public function envelope(): Envelope
    {
        $this->alignCarbonLocale();

        return new Envelope(
            subject: __("mail.patient_status.subject_{$this->appointment->status}", [
                'reference' => $this->appointment->reference,
            ]),
        );
    }

    public function content(): Content
    {
        $this->alignCarbonLocale();

        return new Content(
            view: 'mail.appointment-status',
            text: 'mail.text.appointment-status',
            with: [
                'appointment' => $this->appointment,
                'rows' => $this->patientRows($this->appointment),
                'date' => $this->longDate($this->appointment),
                'confirmationUrl' => $this->confirmationUrl($this->appointment),
            ],
        );
    }
}

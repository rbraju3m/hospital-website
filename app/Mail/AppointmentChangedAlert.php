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
 * A patient moved or cancelled their own booking from the portal.
 *
 * The desk is the audience, and the desk's next action differs by which:
 * a slot given up is a slot to offer somebody else, while a slot moved is a
 * consultant's day that has changed shape. So the two share a template and
 * differ in one line rather than being one vague "something changed".
 *
 * Fallback locale, like the other desk mail: there is no one person whose
 * language this is.
 */
class AppointmentChangedAlert extends Mailable implements ShouldQueue
{
    use RecordsDelivery, PresentsAppointment, Queueable, SerializesModels;

    public function __construct(
        public Appointment $appointment,
        /** `cancelled` or `moved`. */
        public string $change,
        /** Where it was before, for a move. */
        public ?string $previous = null,
    ) {}

    public function envelope(): Envelope
    {
        $this->alignCarbonLocale();

        return new Envelope(
            subject: __("mail.patient_change.subject_{$this->change}", [
                'reference' => $this->appointment->reference,
                'patient' => $this->appointment->patient_name,
            ]),
            replyTo: array_filter([$this->appointment->email]),
        );
    }

    public function content(): Content
    {
        $this->alignCarbonLocale();

        return new Content(
            view: 'mail.appointment-changed',
            text: 'mail.text.appointment-changed',
            with: [
                'appointment' => $this->appointment,
                'change' => $this->change,
                'previous' => $this->previous,
                'rows' => $this->staffRows($this->appointment),
                'date' => $this->longDate($this->appointment),
            ],
        );
    }
}

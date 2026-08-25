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
 * The desk moved a booking, and the patient is not in the room.
 *
 * It leads with where the appointment *is* now rather than where it was: the
 * thing somebody needs off this email is when to turn up. The old time is
 * there underneath, so a patient who has written it in a diary can see which
 * entry this is about.
 *
 * The patient's own locale, like everything else sent to them.
 */
class AppointmentMoved extends Mailable implements ShouldQueue
{
    use RecordsDelivery, PresentsAppointment, Queueable, SerializesModels;

    public function __construct(
        public Appointment $appointment,
        public string $previous,
    ) {}

    public function envelope(): Envelope
    {
        $this->alignCarbonLocale();

        return new Envelope(
            subject: __('mail.patient_moved.subject', ['reference' => $this->appointment->reference]),
        );
    }

    public function content(): Content
    {
        $this->alignCarbonLocale();

        return new Content(
            view: 'mail.appointment-moved',
            text: 'mail.text.appointment-moved',
            with: [
                'appointment' => $this->appointment,
                'previous' => $this->previous,
                'rows' => $this->patientRows($this->appointment),
                'date' => $this->longDate($this->appointment),
            ],
        );
    }
}

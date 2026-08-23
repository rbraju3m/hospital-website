<?php

namespace App\Mail;

use App\Mail\Concerns\PresentsAppointment;
use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The day-before nudge. Sent only for confirmed appointments — see
 * SendAppointmentReminders for why.
 */
class AppointmentReminder extends Mailable implements ShouldQueue
{
    use PresentsAppointment, Queueable, SerializesModels;

    public function __construct(public Appointment $appointment) {}

    public function envelope(): Envelope
    {
        $this->alignCarbonLocale();

        return new Envelope(
            subject: __('mail.reminder.subject', ['reference' => $this->appointment->reference]),
        );
    }

    public function content(): Content
    {
        $this->alignCarbonLocale();

        return new Content(
            view: 'mail.appointment-reminder',
            text: 'mail.text.appointment-reminder',
            with: [
                'appointment' => $this->appointment,
                'rows' => $this->patientRows($this->appointment),
                'date' => $this->longDate($this->appointment),
                'confirmationUrl' => $this->confirmationUrl($this->appointment),
            ],
        );
    }
}

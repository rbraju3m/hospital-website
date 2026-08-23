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
 * Sent to the appointment desk when a booking arrives from the website, so
 * nobody has to sit watching the panel. Not sent for bookings the desk took
 * itself — they were there.
 *
 * Rendered in the site's default locale: unlike a patient email there is no
 * one person whose language this is, and the desk reads both.
 */
class NewAppointmentAlert extends Mailable implements ShouldQueue
{
    use PresentsAppointment, Queueable, SerializesModels;

    public function __construct(public Appointment $appointment) {}

    public function envelope(): Envelope
    {
        $this->alignCarbonLocale();

        return new Envelope(
            subject: __('mail.staff_alert.subject', [
                'reference' => $this->appointment->reference,
                'doctor' => $this->appointment->doctor?->name,
            ]),
            replyTo: array_filter([$this->appointment->email]),
        );
    }

    public function content(): Content
    {
        $this->alignCarbonLocale();

        return new Content(
            view: 'mail.appointment-alert',
            text: 'mail.text.appointment-alert',
            with: [
                'appointment' => $this->appointment,
                'rows' => $this->staffRows($this->appointment),
                'date' => $this->longDate($this->appointment),
            ],
        );
    }
}

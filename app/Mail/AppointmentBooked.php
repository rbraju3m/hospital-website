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
 * Sent to the patient the moment a booking exists — from the website or from
 * the front desk. Says what was booked and, crucially, whether it still needs
 * confirming: a website booking starts as `pending`.
 */
class AppointmentBooked extends Mailable implements ShouldQueue
{
    use PresentsAppointment, Queueable, SerializesModels;

    public function __construct(public Appointment $appointment) {}

    public function envelope(): Envelope
    {
        $this->alignCarbonLocale();

        return new Envelope(
            subject: __('mail.patient_booked.subject', [
                'reference' => $this->appointment->reference,
                'hospital' => setting('site_name', config('app.name')),
            ]),
        );
    }

    public function content(): Content
    {
        $this->alignCarbonLocale();

        return new Content(
            view: 'mail.appointment-booked',
            text: 'mail.text.appointment-booked',
            with: [
                'appointment' => $this->appointment,
                'confirmed' => $this->appointment->status === 'confirmed',
                'rows' => $this->patientRows($this->appointment),
                'date' => $this->longDate($this->appointment),
                'confirmationUrl' => $this->confirmationUrl($this->appointment),
            ],
        );
    }
}

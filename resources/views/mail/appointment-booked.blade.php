@extends('mail.layout')

@section('subject', __('mail.patient_booked.subject', ['reference' => $appointment->reference, 'hospital' => setting('site_name')]))
@section('preheader', __('mail.patient_booked.preheader', ['reference' => $appointment->reference]))
@section('heading', $confirmed ? __('mail.patient_booked.heading_confirmed') : __('mail.patient_booked.heading_pending'))

@section('body')
    <p style="margin:0 0 16px 0; font-size:15px; line-height:1.65; color:#0b2c4d;">
        {{ __('mail.greeting', ['name' => $appointment->patient_name]) }}
    </p>

    <p style="margin:0 0 8px 0; font-size:15px; line-height:1.65; color:#0b2c4d; opacity:0.8;">
        {{ $confirmed
            ? __('mail.patient_booked.intro_confirmed')
            : __('mail.patient_booked.intro_pending', ['phone' => $appointment->phone]) }}
    </p>

    <x-mail.details :rows="$rows" />

    <x-mail.button :url="route('appointment.confirmed', $appointment)" :label="__('mail.patient_booked.cta')" />

    <p style="margin:0 0 16px 0; font-size:14px; line-height:1.65; color:#0b2c4d; opacity:0.7;">
        {{ __('mail.patient_booked.change_body', ['number' => setting('appointment_number', setting('hotline'))]) }}
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
           style="margin:8px 0 0 0; background-color:#fef2f2; border-radius:12px;">
        <tr>
            <td style="padding:16px 18px; font-size:13px; line-height:1.6; color:#b91c1c;">
                {{ __('mail.emergency_note', ['number' => setting('ambulance_number', setting('hotline'))]) }}
            </td>
        </tr>
    </table>
@endsection

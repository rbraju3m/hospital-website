@extends('mail.layout')

@section('subject', __("mail.patient_status.subject_{$appointment->status}", ['reference' => $appointment->reference]))
@section('preheader', __("mail.patient_status.preheader_{$appointment->status}", ['date' => $date]))
@section('heading', __("mail.patient_status.heading_{$appointment->status}"))

@section('body')
    <p style="margin:0 0 16px 0; font-size:15px; line-height:1.65; color:#0b2c4d;">
        {{ __('mail.greeting', ['name' => $appointment->patient_name]) }}
    </p>

    <p style="margin:0 0 8px 0; font-size:15px; line-height:1.65; color:#0b2c4d; opacity:0.8;">
        {{ __("mail.patient_status.intro_{$appointment->status}", [
            'doctor' => $appointment->doctor?->name,
            'date' => $date,
        ]) }}
    </p>

    <x-mail.details :rows="$rows" />

    @if ($appointment->status === 'confirmed')
        <x-mail.button :url="route('appointment.confirmed', $appointment)" :label="__('mail.patient_booked.cta')" />

        <p style="margin:0 0 16px 0; font-size:14px; line-height:1.65; color:#0b2c4d; opacity:0.7;">
            {{ __('mail.patient_booked.change_body', ['number' => setting('appointment_number', setting('hotline'))]) }}
        </p>
    @else
        <x-mail.button :url="route('appointment.create')" :label="__('mail.patient_status.cta_rebook')" />

        <p style="margin:0 0 16px 0; font-size:14px; line-height:1.65; color:#0b2c4d; opacity:0.7;">
            {{ __('mail.patient_status.rebook_body', ['number' => setting('appointment_number', setting('hotline'))]) }}
        </p>
    @endif
@endsection

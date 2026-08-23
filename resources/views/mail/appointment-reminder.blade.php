@extends('mail.layout')

@section('subject', __('mail.reminder.subject', ['reference' => $appointment->reference]))
@section('preheader', __('mail.reminder.preheader', ['doctor' => $appointment->doctor?->name, 'time' => $appointment->formattedTime()]))
@section('heading', __('mail.reminder.heading'))

@section('body')
    <p style="margin:0 0 16px 0; font-size:15px; line-height:1.65; color:#0b2c4d;">
        {{ __('mail.greeting', ['name' => $appointment->patient_name]) }}
    </p>

    <p style="margin:0 0 8px 0; font-size:15px; line-height:1.65; color:#0b2c4d; opacity:0.8;">
        {{ __('mail.reminder.intro', ['doctor' => $appointment->doctor?->name]) }}
    </p>

    <x-mail.details :rows="$rows" />

    <p style="margin:0 0 8px 0; font-size:14px; font-weight:700; color:#0b2c4d;">
        {{ __('mail.reminder.bring_title') }}
    </p>

    <ul style="margin:0 0 20px 0; padding-left:20px; font-size:14px; line-height:1.7; color:#0b2c4d; opacity:0.75;">
        @foreach (['bring_1', 'bring_2', 'bring_3', 'bring_4'] as $item)
            <li>{{ __("appointment.confirmed.{$item}") }}</li>
        @endforeach
    </ul>

    <x-mail.button :url="$confirmationUrl" :label="__('mail.patient_booked.cta')" />

    <p style="margin:0 0 16px 0; font-size:14px; line-height:1.65; color:#0b2c4d; opacity:0.7;">
        {{ __('mail.patient_booked.change_body', ['number' => setting('appointment_number', setting('hotline'))]) }}
    </p>
@endsection

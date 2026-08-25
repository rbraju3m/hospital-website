@extends('mail.layout')

@section('subject', __('mail.patient_moved.subject', ['reference' => $appointment->reference]))
@section('preheader', __('mail.patient_moved.preheader', ['date' => $date, 'time' => $appointment->formattedTime()]))
@section('heading', __('mail.patient_moved.heading'))

@section('body')
    <p style="margin:0 0 16px 0; font-size:15px; line-height:1.65; color:#0b2c4d;">
        {{ __('mail.greeting', ['name' => $appointment->patient_name]) }}
    </p>

    <p style="margin:0 0 8px 0; font-size:15px; line-height:1.65; color:#0b2c4d; opacity:0.8;">
        {{ __('mail.patient_moved.intro', [
            'doctor' => $appointment->doctor?->name,
            'date' => $date,
            'time' => $appointment->formattedTime(),
        ]) }}
    </p>

    {{-- Which diary entry this is about. --}}
    <p style="margin:0 0 16px 0; font-size:14px; line-height:1.65; color:#0b2c4d; opacity:0.65;">
        {{ __('mail.patient_moved.was', ['previous' => $previous]) }}
    </p>

    <x-mail.details :rows="$rows" />

    <p style="margin:0 0 16px 0; font-size:14px; line-height:1.65; color:#0b2c4d; opacity:0.7;">
        {{ __('mail.patient_moved.body', ['number' => setting('appointment_number', setting('hotline'))]) }}
    </p>
@endsection

@extends('mail.layout')

@section('subject', __('mail.staff_alert.subject', ['reference' => $appointment->reference, 'doctor' => $appointment->doctor?->name]))
@section('preheader', __('mail.staff_alert.preheader', [
    'patient' => $appointment->patient_name,
    'date' => $date,
    'time' => $appointment->formattedTime(),
]))
@section('heading', __('mail.staff_alert.heading'))

@section('body')
    <p style="margin:0 0 8px 0; font-size:15px; line-height:1.65; color:#0b2c4d; opacity:0.8;">
        {{ __('mail.staff_alert.intro', ['patient' => $appointment->patient_name]) }}
    </p>

    <x-mail.details :rows="$rows" />

    <x-mail.button :url="route('admin.appointments.show', $appointment)" :label="__('mail.staff_alert.cta')" />
@endsection

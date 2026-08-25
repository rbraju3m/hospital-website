@extends('mail.layout')

@section('subject', __("mail.patient_change.subject_{$change}", ['reference' => $appointment->reference, 'patient' => $appointment->patient_name]))
@section('preheader', __("mail.patient_change.preheader_{$change}", [
    'patient' => $appointment->patient_name,
    'date' => $date,
    'time' => $appointment->formattedTime(),
]))
@section('heading', __("mail.patient_change.heading_{$change}"))

@section('body')
    <p style="margin:0 0 8px 0; font-size:15px; line-height:1.65; color:#0b2c4d; opacity:0.8;">
        {{ __("mail.patient_change.intro_{$change}", ['patient' => $appointment->patient_name]) }}
    </p>

    @if ($previous)
        {{-- Where it was, so the desk can see the slot that has just come free
             without opening the record. --}}
        <p style="margin:0 0 16px 0; font-size:15px; line-height:1.65; color:#0b2c4d; opacity:0.8;">
            {{ __('mail.patient_change.moved_from', ['previous' => $previous]) }}
        </p>
    @endif

    <x-mail.details :rows="$rows" />

    <x-mail.button :url="route('admin.appointments.show', $appointment)" :label="__('mail.staff_alert.cta')" />
@endsection

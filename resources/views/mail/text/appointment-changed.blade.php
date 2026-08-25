{{ __("mail.patient_change.heading_{$change}") }}

{{ __("mail.patient_change.intro_{$change}", ['patient' => $appointment->patient_name]) }}
@if ($previous)

{{ __('mail.patient_change.moved_from', ['previous' => $previous]) }}
@endif
@foreach (array_filter($rows, fn ($value) => filled($value)) as $label => $value)
{{ $label }}: {{ $value }}
@endforeach

{{ __('mail.staff_alert.cta') }}: {{ route('admin.appointments.show', $appointment) }}

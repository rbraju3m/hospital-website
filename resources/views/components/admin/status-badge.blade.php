@props(['status'])

@php
    $style = [
        'pending' => 'badge-amber',
        'confirmed' => 'badge-teal',
        'completed' => 'badge-navy',
        'cancelled' => 'badge-slate',
    ][$status] ?? 'badge-slate';
@endphp

<span {{ $attributes->merge(['class' => $style]) }}>{{ __("admin.appointments.status.{$status}") }}</span>

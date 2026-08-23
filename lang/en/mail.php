<?php

/*
|--------------------------------------------------------------------------
| Notification emails
|--------------------------------------------------------------------------
| Field labels are reused from `appointment.confirmed.*` rather than repeated
| here — the email says the same things as the confirmation page, and the two
| should never drift apart. Only copy specific to email lives in this file.
*/

return [
    'greeting' => 'Dear :name,',
    'signoff' => 'The appointment desk',
    'auto_note' => 'This message was sent automatically — please do not reply to it. For anything at all, call :hotline.',
    'emergency_note' => 'If your symptoms become severe, do not wait for your appointment. Come to the Emergency Department at any hour, or call :number for an ambulance.',

    'patient_booked' => [
        'subject' => 'Appointment :reference — :hospital',
        'preheader' => 'Your booking reference is :reference.',
        'heading_pending' => 'We have your booking',
        'heading_confirmed' => 'Your appointment is confirmed',
        'intro_pending' => 'Our appointment desk will confirm this slot by phone or SMS to :phone shortly. Nothing more is needed from you until then.',
        'intro_confirmed' => 'Your slot is held. Please arrive 15 minutes early so registration is finished before your consultation time.',
        'cta' => 'View your appointment',
        'change_body' => 'To change or cancel it, call :number and quote your reference number.',
    ],

    'patient_status' => [
        'subject_confirmed' => 'Confirmed — appointment :reference',
        'subject_cancelled' => 'Cancelled — appointment :reference',
        'preheader_confirmed' => 'Your slot on :date is held.',
        'preheader_cancelled' => 'Your appointment on :date will not go ahead.',
        'heading_confirmed' => 'Your appointment is confirmed',
        'heading_cancelled' => 'Your appointment has been cancelled',
        'intro_confirmed' => 'Your slot with :doctor is held. Please arrive 15 minutes early so registration is finished before your consultation time.',
        'intro_cancelled' => 'Your appointment with :doctor on :date will not go ahead. Nothing has been charged.',
        'cta_rebook' => 'Book another appointment',
        'rebook_body' => 'To book another time, call :number or use the website.',
    ],

    'staff_alert' => [
        'subject' => 'New booking :reference — :doctor',
        'preheader' => ':patient, :date at :time.',
        'heading' => 'A new appointment came in',
        'intro' => ':patient booked through the website and is waiting for confirmation.',
        'cta' => 'Open in the staff panel',
    ],

    'labels' => [
        'phone' => 'Phone',
        'email' => 'Email',
        'age' => 'Age',
        'visit_type' => 'Visit type',
        'notes' => 'Patient notes',
        'booked_at' => 'Booked',
    ],
];

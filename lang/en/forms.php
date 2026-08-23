<?php

/*
 | Validation messages and field labels for the public forms. Referenced from
 | app/Http/Requests/ so the wording lives with the rest of the copy.
 */

return [
    'phone_invalid' => 'Enter a valid Bangladeshi mobile number, e.g. 01712345678.',
    'booking_window' => 'Appointments can be booked up to :days days ahead.',
    'slot_taken' => 'That slot has just been taken. Please pick another time.',
    'no_online_booking' => 'This consultant does not take online appointments. Please call our hotline.',

    'attributes' => [
        'doctor_id' => 'doctor',
        'appointment_date' => 'date',
        'appointment_time' => 'time slot',
        'patient_name' => 'patient name',
    ],
];

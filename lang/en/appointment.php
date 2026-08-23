<?php

return [
    'meta_title' => 'Book an Appointment',
    'meta_description' => 'Book an appointment online with a specialist consultant at :name. Choose your department, doctor, date and time slot in a few steps.',
    'eyebrow' => 'Online Booking',
    'title' => 'Book an appointment',
    'lede' => 'Choose a department, pick your consultant, and confirm a time. You will get a reference number immediately — no payment is taken online.',
    'crumb' => 'Book Appointment',

    'errors_title' => 'Please check the following',

    'step_1' => [
        'title' => 'Choose your consultant',
        'lede' => 'Filter by department, then select a doctor.',
        'department' => 'Department',
        'consultant' => 'Consultant',
        'loading' => 'loading…',
        'select_consultant' => 'Select a consultant',
    ],

    'step_2' => [
        'title' => 'Pick a date and time',
        'lede' => 'Only dates with open slots are shown.',
        'select_first' => 'Select a consultant first.',
        'dates_label' => 'Available dates',
        'checking' => 'Checking availability…',
        'none_in_window' => 'No open slots in the next three weeks. Please call',
        'times_label' => 'Available times',
        'no_times' => 'No slots left on this date — please choose another.',
        'slots_open_short' => ':count open',
    ],

    'step_3' => [
        'title' => 'Patient details',
        'lede' => 'We only ask for what the clinic needs.',
        'patient_name' => "Patient's full name",
        'patient_name_placeholder' => 'As it should appear on the prescription',
        'phone' => 'Mobile number',
        'phone_hint' => 'We send the confirmation SMS to this number.',
        'email' => 'Email',
        'gender' => 'Gender',
        'gender_unspecified' => 'Prefer not to say',
        'gender_female' => 'Female',
        'gender_male' => 'Male',
        'gender_other' => 'Other',
        'age' => 'Age',
        'age_placeholder' => 'Years',
        'visit_type' => 'Visit type',
        'visit_new' => 'First visit',
        'visit_follow_up' => 'Follow-up',
        'notes' => 'Reason for visit',
        'notes_placeholder' => 'Main symptoms, how long they have lasted, and any current medication.',
    ],

    'summary_incomplete' => 'Complete the steps above to confirm.',
    'summary_at' => 'at',
    'no_online_payment' => 'No payment is taken online. Pay at the reception desk.',
    'confirm' => 'Confirm appointment',

    'aside' => [
        'selected' => 'Selected consultant',
        'view_profile' => 'View full profile →',
        'before_title' => 'Before you come',
        'before_1' => 'Arrive 15 minutes early to complete registration.',
        'before_2' => 'Bring any previous prescriptions, reports and scans.',
        'before_3' => 'Bring a list of medicines you currently take, including doses.',
        'before_4' => 'Fasting is only needed if your doctor has told you so.',
        'not_emergency_title' => 'This is not for emergencies',
        'not_emergency_body' => 'If symptoms are severe or sudden, do not book — come straight to the Emergency Department or call an ambulance.',
    ],

    'confirmed' => [
        'meta_title' => 'Appointment Confirmed — :reference',
        'title' => 'Appointment requested',
        'lede' => 'We have your booking. Our appointment desk will confirm by SMS to :phone shortly.',
        'reference_label' => 'Reference number',
        'reference_hint' => 'Quote this at the reception desk.',

        'consultant' => 'Consultant',
        'department' => 'Department',
        'date' => 'Date',
        'time' => 'Time',
        'arrive_early' => 'Please arrive 15 minutes early',
        'patient' => 'Patient',
        'fee' => 'Consultation fee',
        'fee_hint' => 'Payable at reception',
        'age_years' => ':count yrs',

        'print' => 'Print this page',
        'view_consultant' => 'View consultant',
        'change_it' => 'Need to change it?',

        'bring_title' => 'What to bring',
        'bring_1' => 'This reference number',
        'bring_2' => 'Previous prescriptions, reports and scans',
        'bring_3' => 'A list of your current medicines and doses',
        'bring_4' => 'National ID or passport for registration',

        'worse_title' => 'If things get worse',
        'worse_body' => 'Do not wait for your appointment date if symptoms become severe. Come to the Emergency Department at any hour, or call for an ambulance.',

        'back_home' => '← Back to homepage',
    ],
];

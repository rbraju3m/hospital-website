<?php

/*
|--------------------------------------------------------------------------
| Patient portal
|--------------------------------------------------------------------------
| Everything behind /portal. The audience is patients, not staff: plain
| words, no system vocabulary, and nothing that assumes an email address.
*/

return [
    'name' => 'Patient Portal',
    'meta_description' => 'View your appointments and download reports, prescriptions and bills from :name.',
    'back_to_site' => 'Back to the website',

    'nav' => [
        'dashboard' => 'Overview',
        'appointments' => 'Appointments',
        'documents' => 'Reports & documents',
        'profile' => 'My details',
    ],

    'fields' => [
        'name' => 'Full name',
        'phone' => 'Mobile number',
        'email' => 'Email address',
        'password' => 'Password',
        'password_confirmation' => 'Confirm password',
        'current_password' => 'Current password',
        'code' => 'Six-digit code',
        'date_of_birth' => 'Date of birth',
        'gender' => 'Gender',
    ],

    'login' => [
        'title' => 'Sign in',
        'lede' => 'Use the mobile number you booked with.',
        'submit' => 'Sign in',
        'remember' => 'Keep me signed in on this device',
        'failed' => 'That mobile number and password do not match.',
        'throttled' => 'Too many attempts. Try again in :seconds seconds.',
        'signed_out' => 'You have been signed out.',
        'forgot' => 'Forgotten your password?',
        'no_account' => 'Not registered yet?',
        'register_link' => 'Create an account',
    ],

    'register' => [
        'title' => 'Create your account',
        'lede' => 'Register with the mobile number you use at the hospital, and your appointments and reports will already be here.',
        'submit' => 'Create account',
        'phone_help' => 'This is how your records are found, so use the number you gave at reception.',
        'email_help' => 'Optional. We will email your reports too if you add one.',
        'phone_taken' => 'An account already exists for that mobile number.',
        'welcome' => 'Welcome, :name. Anything already filed against your mobile number is below.',
        'have_account' => 'Already registered?',
        'login_link' => 'Sign in',
    ],

    'forgot' => [
        'title' => 'Forgotten password',
        'lede' => 'Enter your mobile number and we will text you a six-digit code.',
        'submit' => 'Send me a code',
        'sent' => 'If that number has an account, a code is on its way. It expires in ten minutes.',
        'back' => 'Back to sign in',
    ],

    'reset' => [
        'title' => 'Choose a new password',
        'lede' => 'Enter the code we texted you, then pick a new password.',
        'submit' => 'Save new password',
        'invalid_code' => 'That code is wrong or has expired. Ask for a new one.',
        'done' => 'Your password has been changed. Sign in with it.',
        'resend' => 'Send another code',
    ],

    'dashboard' => [
        'greeting' => 'Hello, :name',
        'stat_appointments' => 'Appointments',
        'stat_documents' => 'Documents',
        'upcoming_title' => 'Coming up',
        'no_upcoming' => 'Nothing booked at the moment.',
        'book_cta' => 'Book an appointment',
        'documents_title' => 'Recent documents',
        'no_documents' => 'Nothing has been filed against your mobile number yet.',
        'documents_hint' => 'Reports appear here once the laboratory has verified them.',
        'view_all' => 'View all',
    ],

    'appointments' => [
        'title' => 'Your appointments',
        'upcoming_title' => 'Coming up',
        'past_title' => 'Past appointments',
        'none' => 'Nothing booked at the moment.',
        'none_past' => 'No past appointments.',
        'change_note' => 'To change or cancel an appointment, call :phone and quote the reference.',
        'reference' => 'Reference',
    ],

    'documents' => [
        'title' => 'Reports & documents',
        'lede' => 'Everything filed against your mobile number. Reports appear once the laboratory has verified them.',
        'all' => 'All',
        'none' => 'Nothing here yet.',
        'download' => 'Download',
        'issued' => 'Issued :date',
        'filed' => 'Filed :date',
    ],

    'status' => [
        'pending' => 'Awaiting confirmation',
        'confirmed' => 'Confirmed',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],

    'categories' => [
        'report' => 'Report',
        'prescription' => 'Prescription',
        'bill' => 'Bill',
    ],

    'profile' => [
        'title' => 'My details',
        'lede' => 'What we show on your records here. It does not change what reception holds.',
        'phone_locked' => 'Your mobile number is how your records are found and cannot be changed here. Call :phone if it needs correcting.',
        'password_title' => 'Change password',
        'password_hint' => 'Leave these blank to keep your current password.',
        'save' => 'Save changes',
        'saved' => 'Your details have been saved.',
    ],
];

<?php

/*
 | Strings shared across more than one page. Anything used by a single page
 | belongs in that page's file instead.
 */

return [

    'theme' => [
        'toggle' => 'Switch between light and dark',
        'light' => 'Light mode',
        'dark' => 'Dark mode',
    ],
    'book_appointment' => 'Book an Appointment',
    'book_appointment_short' => 'Book Appointment',
    'book_now' => 'Book',
    'find_a_doctor' => 'Find a Doctor',
    'view_all' => 'View all',
    'learn_more' => 'Learn more',
    'details' => 'Details',
    'explore_department' => 'Explore department',
    'call_to_book' => 'Call to book',
    'call' => 'Call',
    'hotline' => 'Hotline',
    'emergency' => 'Emergency',
    'ambulance' => 'Ambulance',
    'appointments' => 'Appointments',
    'consultation' => 'consultation',
    'years_short' => ':count yrs',
    'consultants_count' => ':count consultants',
    'open_in_maps' => 'Open in maps',
    'skip_to_content' => 'Skip to main content',
    'read_time' => ':count min read',
    'optional' => '(optional)',
    'all_departments' => 'All departments',
    'any_department' => 'Any department',
    'toggle_menu' => 'Toggle navigation menu',
    'home_aria' => ':name home',
    'breadcrumb' => 'Breadcrumb',
    'home' => 'Home',
    'rating_aria' => ':rating out of 5',
    'switch_language' => 'Change language',
    'back_to_top' => 'Back to top',
    'scroll_progress' => 'Reading progress',

    // Keyed to Carbon::dayOfWeek (0 = Sunday), matching DoctorSchedule::DAYS.
    'days' => [
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
    ],
];

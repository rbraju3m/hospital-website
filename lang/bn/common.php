<?php

/*
 | Bangla — shared strings.
 |
 | Any key omitted here falls back to lang/en per-key, so this file can be
 | filled in gradually. Translations should be reviewed by a native speaker
 | before launch; clinical copy in particular has not been translated.
 */

return [

    'theme' => [
        'toggle' => 'হালকা ও গাঢ় থিমের মধ্যে বদল করুন',
        'light' => 'হালকা থিম',
        'dark' => 'গাঢ় থিম',
    ],
    'book_appointment' => 'অ্যাপয়েন্টমেন্ট নিন',
    'book_appointment_short' => 'অ্যাপয়েন্টমেন্ট',
    'book_now' => 'বুক করুন',
    'find_a_doctor' => 'ডাক্তার খুঁজুন',
    'view_all' => 'সব দেখুন',
    'learn_more' => 'বিস্তারিত',
    'details' => 'বিস্তারিত',
    'explore_department' => 'বিভাগ দেখুন',
    'call_to_book' => 'কল করে বুক করুন',
    'call' => 'কল করুন',
    'hotline' => 'হটলাইন',
    'emergency' => 'জরুরি',
    'ambulance' => 'অ্যাম্বুলেন্স',
    'appointments' => 'অ্যাপয়েন্টমেন্ট',
    'consultation' => 'কনসালটেশন',
    'years_short' => ':count বছর',
    'consultants_count' => ':count জন কনসালট্যান্ট',
    'open_in_maps' => 'ম্যাপে দেখুন',
    'skip_to_content' => 'মূল অংশে যান',
    'read_time' => ':count মিনিটের পড়া',
    'optional' => '(ঐচ্ছিক)',
    'all_departments' => 'সব বিভাগ',
    'any_department' => 'যেকোনো বিভাগ',
    'toggle_menu' => 'মেনু খুলুন বা বন্ধ করুন',
    'home_aria' => ':name হোম',
    'breadcrumb' => 'ব্রেডক্রাম্ব',
    'home' => 'হোম',
    'rating_aria' => '৫ এর মধ্যে :rating',
    'switch_language' => 'ভাষা পরিবর্তন করুন',
    'back_to_top' => 'উপরে ফিরুন',
    'scroll_progress' => 'পড়ার অগ্রগতি',

    // Keyed to Carbon::dayOfWeek (0 = Sunday), matching DoctorSchedule::DAYS.
    'days' => [
        0 => 'রবিবার',
        1 => 'সোমবার',
        2 => 'মঙ্গলবার',
        3 => 'বুধবার',
        4 => 'বৃহস্পতিবার',
        5 => 'শুক্রবার',
        6 => 'শনিবার',
    ],
];

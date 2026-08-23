<?php

return [
    'index' => [
        'meta_title' => 'Health Check-up Packages',
        'meta_description' => 'Executive, cardiac, diabetes, women\'s and senior health check packages at :name — completed in a single visit with a consultant review.',
        'eyebrow' => 'Health Packages',
        'title' => 'Preventive check-ups worth the morning they take',
        'lede' => 'Structured screening completed in one visit, in a lounge separate from the main outpatient area, ending with a consultant who explains what the results mean.',
        'crumb' => 'Health Packages',

        'all' => 'All packages',
        'empty' => 'No packages in this category yet.',

        'corporate_title' => 'Screening for your organisation?',
        'corporate_body' => 'We run corporate health screening on site or at the hospital, with consolidated anonymised reporting for HR and individual reports for each employee.',
        'corporate_cta' => 'Request a corporate quote',
    ],

    'show' => [
        'eyebrow' => ':category Package',
        'tests_title' => 'Tests included',
        'save' => 'Save :percent%',
        'save_limited' => 'Save :percent% — limited period',

        'how_title' => 'How the visit works',
        'step_1_title' => 'Book a morning slot',
        'step_1_body' => 'Most tests need an 8–10 hour fast, so an early appointment is easiest. Water is fine.',
        'step_2_title' => 'Samples and imaging first',
        'step_2_body' => 'Blood, urine and imaging are taken in sequence in the health check lounge. Breakfast is provided afterwards.',
        'step_3_title' => 'Consultant review',
        'step_3_body' => 'A physician goes through every result with you and writes an action plan — this is included, not an add-on.',
        'step_4_title' => 'Report to keep',
        'step_4_body' => 'You leave with a printed report, and the same file is available in the patient portal for download later.',

        'duration' => 'Duration',
        'suitable_for' => 'Suitable for',
        'tests_included' => 'Tests included',
        'parameters' => ':count parameters',
        'book_cta' => 'Book this package',

        'related_eyebrow' => 'Compare',
        'related_title' => 'Other packages',
        'related_link' => 'All packages',
    ],

    'more_tests' => '+ :count more tests included',

    // Category slugs stored on the model. Unknown values fall back to a
    // title-cased version of the slug itself.
    'categories' => [
        'executive' => 'Executive',
        'cardiac' => 'Cardiac',
        'diabetes' => 'Diabetes',
        'women' => "Women's Health",
        'men' => "Men's Health",
        'senior' => 'Senior Citizen',
        'basic' => 'Basic',
    ],
];

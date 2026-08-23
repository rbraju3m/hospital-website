<?php

return [
    'index' => [
        'meta_title' => 'Health Hub',
        'meta_description' => 'Health guidance from :name consultants — dengue warning signs, chest pain, diabetes control and preventive screening.',
        'eyebrow' => 'Health Hub',
        'title' => 'Guidance from our consultants',
        'lede' => 'Practical, locally relevant health writing — when to worry, when not to, and what to do about it.',
        'crumb' => 'Health Hub',

        'all' => 'All articles',
        'empty' => 'No articles in this category yet.',
    ],

    'show' => [
        'crumb' => 'Health Hub',

        'disclaimer_lead' => 'A note on this article.',
        'disclaimer_body' => 'It is written for general guidance and cannot account for your particular history. If something here applies to you, book a consultation rather than acting on it alone — and in an emergency, call',

        'specialist_title' => 'Speak to a specialist',
        'specialist_body' => 'Our consultants see patients seven days a week across :count departments.',

        'emergency_title' => 'Emergency',
        'emergency_body' => 'Open 24 hours. No appointment needed.',

        'related_eyebrow' => 'Keep reading',
        'related_title' => 'Related articles',
        'related_link' => 'All articles',
    ],

    // Category slugs stored on the model. Unknown values fall back to a
    // title-cased version of the slug itself.
    'categories' => [
        'health-tips' => 'Health Tips',
        'news' => 'News',
        'events' => 'Events',
        'achievements' => 'Achievements',
    ],
];

<?php

/*
| Icon names the <x-icon> component can render, offered as choices wherever
| the admin picks an icon. Adding a name here without adding its path to
| resources/views/components/icon.blade.php falls back to the default glyph.
*/

return [
    'clinical' => [
        'stethoscope', 'heart-pulse', 'brain', 'bone', 'baby', 'eye', 'wind',
        'droplet', 'microscope', 'scan', 'syringe', 'pill', 'activity',
    ],
    'general' => [
        'ambulance', 'shield-check', 'award', 'users', 'user-round', 'bed',
        'calendar', 'calendar-check', 'clock', 'map-pin', 'phone', 'mail',
        'globe', 'plane', 'building', 'file-text', 'credit-card', 'package',
    ],
];

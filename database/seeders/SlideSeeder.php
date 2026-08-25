<?php

namespace Database\Seeders;

use App\Models\Slide;
use Illuminate\Database\Seeder;

/**
 * Three slides, so the slider layout is a working slider the moment somebody
 * switches to it rather than an empty band with a note about adding content.
 *
 * No images: `Slide::url()` falls back to the stand-in hero photography, the
 * same as every other picture on the site. Keyed on the English title so
 * re-seeding is safe and never duplicates.
 */
class SlideSeeder extends Seeder
{
    public function run(): void
    {
        $slides = [
            [
                'eyebrow' => 'Open around the clock',
                'title' => 'Emergency care that does not wait for paperwork',
                'subtitle' => 'A consultant-led emergency department, staffed every hour of every day, with the ambulance line answered in under a minute.',
                'cta_label' => 'Call the hotline',
                'cta_url' => 'tel:+8809610001234',
                'cta_secondary_label' => 'Emergency & ambulance',
                'cta_secondary_url' => '/emergency',
            ],
            [
                'eyebrow' => 'Book in ninety seconds',
                'title' => 'See the right consultant, on a day that suits you',
                'subtitle' => 'Choose a department, pick a chamber time and confirm it online. You will have a booking reference before you close the page.',
                'cta_label' => 'Book an appointment',
                'cta_url' => '/appointment',
                'cta_secondary_label' => 'Find a doctor',
                'cta_secondary_url' => '/doctors',
            ],
            [
                'eyebrow' => 'Health packages',
                'title' => 'A full check-up, finished before lunch',
                'subtitle' => 'Screening packages with the results explained rather than printed — and every report waiting in your portal afterwards.',
                'cta_label' => 'See the packages',
                'cta_url' => '/health-packages',
                'cta_secondary_label' => 'Diagnostics price list',
                'cta_secondary_url' => '/diagnostics',
            ],
        ];

        foreach ($slides as $i => $data) {
            Slide::updateOrCreate(
                ['title' => $data['title']],
                [...$data, 'sort_order' => $i + 1, 'is_active' => true],
            );
        }
    }
}

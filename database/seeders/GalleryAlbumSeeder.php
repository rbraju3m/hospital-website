<?php

namespace Database\Seeders;

use App\Models\GalleryAlbum;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Albums for the photo gallery.
 *
 * The photograph rows carry captions but no files. That is deliberate and it is
 * the same trade the rest of the site makes: a hospital rarely has its own
 * photography ready on day one, so an empty `path` renders stand-in imagery
 * from DemoImages and the gallery reads as a gallery from the first deploy.
 * Uploading a real picture replaces it, one photograph at a time.
 */
class GalleryAlbumSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->albums() as $order => $data) {
            $captions = $data['photos'];
            unset($data['photos']);

            $album = GalleryAlbum::updateOrCreate(
                ['slug' => Str::slug($data['title'])],
                [...$data, 'sort_order' => $order + 1, 'is_active' => true],
            );

            foreach ($captions as $i => $caption) {
                // Keyed on the position so re-seeding rewrites the row it wrote
                // last time rather than stacking another copy of the album.
                $album->photos()->updateOrCreate(
                    ['sort_order' => $i + 1],
                    ['caption' => $caption],
                );
            }
        }
    }

    private function albums(): array
    {
        return [
            [
                'title' => 'Cardiac catheterisation and theatres',
                'is_featured' => true,
                'summary' => 'Two cath labs and the hybrid theatre, where primary angioplasty happens around the clock.',
                'description' => "The cardiac floor runs two catheterisation laboratories and one hybrid theatre, staffed for emergency angioplasty at any hour.\nPhotographs were taken between lists, with no patients present.",
                'photos' => [
                    'Cath lab one, set up for a primary angioplasty',
                    'The control room, where the images are read live',
                    'Hybrid theatre — surgery and catheterisation in one room',
                    'Scrub area outside the theatre suite',
                    'Recovery bay, six beds under continuous monitoring',
                    'The on-call team at handover, 7am',
                ],
            ],
            [
                'title' => 'Critical care and the neonatal unit',
                'is_featured' => true,
                'summary' => 'Intensive care, high dependency and the neonatal unit, one nurse to one bed at the top level.',
                'description' => "Twenty-two intensive care beds, sixteen of them cardiac, plus a neonatal unit with its own ventilators and transport incubator.",
                'photos' => [
                    'A ventilated bed in general intensive care',
                    'The central monitoring station',
                    'Neonatal intensive care — incubator and phototherapy',
                    'Transport incubator, ready for a referral',
                    'High dependency, stepped down from intensive care',
                    'Family room, next to the unit rather than down a corridor',
                ],
            ],
            [
                'title' => 'Emergency department and the ambulance fleet',
                'summary' => 'The 24-hour emergency floor, its resuscitation bays and the ambulances that reach it.',
                'description' => "Triage is inside the door, not behind it. The fleet includes two advanced life-support ambulances carrying a defibrillator and a paramedic.",
                'photos' => [
                    'Triage, first desk inside the emergency entrance',
                    'Resuscitation bay one',
                    'Advanced life-support ambulance, fully equipped',
                    'The ambulance bay at night',
                    'Emergency observation beds',
                ],
            ],
            [
                'title' => 'Imaging and the laboratory',
                'summary' => 'CT, MRI, ultrasound and the pathology laboratory that runs behind them.',
                'description' => "Imaging runs to a same-day standard for inpatients. The laboratory is on the same floor, which is why most routine results are back within hours rather than days.",
                'photos' => [
                    '128-slice CT scanner',
                    'MRI suite, with its own control room',
                    'Ultrasound room, set up for obstetric scanning',
                    'Digital X-ray',
                    'The pathology laboratory',
                    'Sample reception, where the counter hands over',
                ],
            ],
        ];
    }
}

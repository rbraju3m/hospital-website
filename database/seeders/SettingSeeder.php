<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'general' => [
                'site_name' => 'RBR Hospital',
                'site_tagline' => 'Advanced medicine, delivered with care',
                'established_year' => '2009',
                'bed_count' => '450',
                'accreditation' => 'JCI Accredited · ISO 9001:2015 Certified',
            ],
            'contact' => [
                'hotline' => '10666',
                'emergency_number' => '+880 9612 345 600',
                'ambulance_number' => '+880 9612 345 999',
                'appointment_number' => '+880 9612 345 610',
                'international_desk' => '+880 9612 345 700',
                'email' => 'info@rbrhospital.com.bd',
                'appointment_email' => 'appointment@rbrhospital.com.bd',
                'international_email' => 'international@rbrhospital.com.bd',
                'address_line' => 'Plot 42, Sector 11, Uttara Model Town',
                'address_city' => 'Dhaka 1230, Bangladesh',
                'map_url' => 'https://www.openstreetmap.org/?mlat=23.8759&mlon=90.3795#map=16/23.8759/90.3795',
                'opening_hours' => 'Outpatient: 8:00 AM – 10:00 PM · Emergency: 24 hours',
            ],
            'social' => [
                'facebook' => 'https://facebook.com/rbrhospital',
                'youtube' => 'https://youtube.com/@rbrhospital',
                'linkedin' => 'https://linkedin.com/company/rbrhospital',
                'instagram' => 'https://instagram.com/rbrhospital',
            ],
            'stats' => [
                'stat_doctors' => '180',
                'stat_beds' => '450',
                'stat_departments' => '16',
                'stat_patients_yearly' => '400,000',
                'stat_icu_beds' => '64',
                'stat_years' => '17',
            ],
        ];

        foreach ($settings as $group => $pairs) {
            foreach ($pairs as $key => $value) {
                Setting::updateOrCreate(['key' => $key], ['value' => $value, 'group' => $group]);
            }
        }
    }
}

<?php

namespace Database\Seeders\Translations;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Bangla values for the settings that are labels rather than data.
 *
 * Phone numbers, emails, URLs and the numeric statistics stay in the base
 * columns: they read identically in both locales, and keeping figures in
 * Latin digits matches the prices and fees that come straight from the
 * database elsewhere on the page.
 */
class SettingTranslationSeeder extends Seeder
{
    public function run(): void
    {
        $translations = [
            'site_name' => 'আরবিআর হাসপাতাল',
            'site_tagline' => 'উন্নত চিকিৎসা, যত্নের সাথে',
            'accreditation' => 'জেসিআই স্বীকৃত · আইএসও ৯০০১:২০১৫ সনদপ্রাপ্ত',
            'address_line' => 'প্লট ৪২, সেক্টর ১১, উত্তরা মডেল টাউন',
            'address_city' => 'ঢাকা ১২৩০, বাংলাদেশ',
            'opening_hours' => 'বহির্বিভাগ: সকাল ৮টা – রাত ১০টা · জরুরি: ২৪ ঘণ্টা',
            // Bangla groups by lakh (৪,০০,০০০), not by thousand (400,000).
            'stat_patients_yearly' => '৪,০০,০০০',
        ];

        foreach ($translations as $key => $value) {
            Setting::where('key', $key)->first()?->setTranslations('bn', ['value' => $value])->save();
        }

        Setting::flushCache();
    }
}

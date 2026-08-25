<?php

namespace Database\Seeders\Translations;

use App\Models\Slide;
use Illuminate\Database\Seeder;

class SlideTranslationSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->translations() as $englishTitle => $bn) {
            Slide::where('title', $englishTitle)->first()?->setTranslations('bn', $bn)->save();
        }
    }

    private function translations(): array
    {
        return [
            'Emergency care that does not wait for paperwork' => [
                'eyebrow' => 'দিনরাত খোলা',
                'title' => 'কাগজপত্রের অপেক্ষা ছাড়াই জরুরি চিকিৎসা',
                'subtitle' => 'বিশেষজ্ঞ চিকিৎসকের তত্ত্বাবধানে জরুরি বিভাগ, দিনের প্রতিটি ঘণ্টায় খোলা, আর অ্যাম্বুলেন্স লাইনে এক মিনিটের মধ্যে সাড়া।',
                'cta_label' => 'হটলাইনে কল করুন',
                'cta_secondary_label' => 'জরুরি ও অ্যাম্বুলেন্স',
            ],
            'See the right consultant, on a day that suits you' => [
                'eyebrow' => 'নব্বই সেকেন্ডে বুকিং',
                'title' => 'আপনার সুবিধামতো দিনে সঠিক বিশেষজ্ঞের কাছে',
                'subtitle' => 'বিভাগ বেছে নিন, চেম্বারের সময় ঠিক করুন এবং অনলাইনেই নিশ্চিত করুন। পাতা বন্ধ করার আগেই বুকিং রেফারেন্স পেয়ে যাবেন।',
                'cta_label' => 'অ্যাপয়েন্টমেন্ট নিন',
                'cta_secondary_label' => 'চিকিৎসক খুঁজুন',
            ],
            'A full check-up, finished before lunch' => [
                'eyebrow' => 'হেলথ প্যাকেজ',
                'title' => 'দুপুরের আগেই সম্পূর্ণ স্বাস্থ্য পরীক্ষা',
                'subtitle' => 'স্ক্রিনিং প্যাকেজ, যেখানে রিপোর্ট শুধু ছাপা হয় না — বুঝিয়েও দেওয়া হয়; আর প্রতিটি রিপোর্ট পরে আপনার পোর্টালে অপেক্ষা করে।',
                'cta_label' => 'প্যাকেজ দেখুন',
                'cta_secondary_label' => 'ডায়াগনস্টিক মূল্যতালিকা',
            ],
        ];
    }
}

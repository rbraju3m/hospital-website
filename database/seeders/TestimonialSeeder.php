<?php

namespace Database\Seeders;

use App\Models\Testimonial;
use Illuminate\Database\Seeder;

class TestimonialSeeder extends Seeder
{
    public function run(): void
    {
        $testimonials = [
            ['patient_name' => 'Shahnaz Begum', 'location' => 'Uttara, Dhaka', 'treatment' => 'Cardiac Angioplasty', 'rating' => 5,
             'quote' => 'My husband was brought in with chest pain at two in the morning. He was in the cath lab within forty minutes. Nobody asked us for paperwork before they started treating him — that came afterwards. I will not forget that.'],
            ['patient_name' => 'Mahmudul Karim', 'location' => 'Gulshan, Dhaka', 'treatment' => 'Total Knee Replacement', 'rating' => 5,
             'quote' => 'I had put off the surgery for three years out of fear. Professor Sarwar explained exactly what would happen and how long recovery would take. I stood up the same evening. Six weeks later I climbed the stairs to my flat without stopping.'],
            ['patient_name' => 'Rehana Parvin', 'location' => 'Mirpur, Dhaka', 'treatment' => 'Maternity Care', 'rating' => 5,
             'quote' => 'It was a high-risk pregnancy and I was frightened the whole way through. The team never once made me feel like I was asking too many questions. My daughter needed the NICU for four days and they let me stay beside her the entire time.'],
            ['patient_name' => 'Abdul Mannan Sikder', 'location' => 'Narayanganj', 'treatment' => 'Diabetes Management', 'rating' => 5,
             'quote' => 'For years I only ever got a prescription. Here I sat with an educator who went through what I actually eat and what I could change. My HbA1c dropped from 9.4 to 6.8 in eight months without any new medicine.'],
            ['patient_name' => 'Farida Yasmin', 'location' => 'Dhanmondi, Dhaka', 'treatment' => 'Breast Cancer Treatment', 'rating' => 5,
             'quote' => 'Professor Nasrin told me my case would be discussed by a full board before anything was decided. Knowing that several specialists had looked at my scans, not just one, is what let me sleep at night.'],
            ['patient_name' => 'Tanjil Hossain', 'location' => 'Chattogram', 'treatment' => 'Stroke Thrombolysis', 'rating' => 5,
             'quote' => 'My father lost his speech on a Friday evening. We reached the emergency department in fifty minutes and he had the clot-dissolving injection shortly after. He is speaking normally today. Fifty minutes decided the rest of his life.'],
            ['patient_name' => 'Nusrat Sharmin', 'location' => 'Bashundhara, Dhaka', 'treatment' => 'Executive Health Check', 'rating' => 4,
             'quote' => 'The whole check was finished before lunch and the report actually explained what the numbers meant instead of just printing them. They picked up a thyroid problem I had no symptoms of.'],
            ['patient_name' => 'Dr. Iqbal Hossain', 'location' => 'London, United Kingdom', 'treatment' => 'Spine Surgery', 'rating' => 5,
             'quote' => 'I travelled from the UK for my mother\'s spine surgery. The international desk arranged the visa letter, the airport pickup and an interpreter. The standard of the operating theatre was no different from what I am used to in London.'],
        ];

        foreach ($testimonials as $i => $data) {
            Testimonial::updateOrCreate(
                ['patient_name' => $data['patient_name'], 'treatment' => $data['treatment']],
                [...$data, 'sort_order' => $i + 1, 'is_active' => true]
            );
        }
    }
}

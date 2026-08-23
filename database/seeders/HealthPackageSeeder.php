<?php

namespace Database\Seeders;

use App\Models\HealthPackage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class HealthPackageSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->packages() as $i => $data) {
            HealthPackage::updateOrCreate(
                ['slug' => Str::slug($data['name'])],
                [...$data, 'sort_order' => $i + 1, 'is_active' => true]
            );
        }
    }

    private function packages(): array
    {
        return [
            [
                'name' => 'Executive Health Check — Comprehensive', 'category' => 'executive',
                'price' => 24000, 'discount_price' => 18500, 'duration' => 'Half day (4–5 hours)',
                'suitable_for' => 'Adults 35+ or anyone in a demanding role', 'is_featured' => true,
                'summary' => 'Our most complete screening: 70+ parameters, cardiac and imaging work-up, and a consultant review that ties the results together.',
                'description' => 'The comprehensive executive check is designed for people who want a genuinely thorough baseline rather than a handful of routine tests. It covers cardiovascular risk, metabolic health, liver and kidney function, thyroid, cancer markers appropriate to age and sex, and imaging of the chest and abdomen. The visit ends with a consultant physician who explains the findings in plain language and writes an action plan.',
                'tests' => ['Complete blood count & ESR', 'Fasting glucose & HbA1c', 'Lipid profile', 'Liver function tests', 'Kidney function tests & electrolytes', 'Thyroid profile (TSH, FT3, FT4)', 'Urine routine & microscopy', 'Stool routine', 'ECG', 'Echocardiography', 'Treadmill / stress test', 'Chest X-ray', 'Ultrasound of whole abdomen', 'Vitamin D & B12', 'Serum uric acid', 'PSA (men) / Pap smear (women)', 'Eye and vision check', 'Consultant physician review', 'Dietitian consultation'],
            ],
            [
                'name' => 'Executive Health Check — Essential', 'category' => 'executive',
                'price' => 12000, 'discount_price' => 8900, 'duration' => '2–3 hours',
                'suitable_for' => 'Adults 25–40 with no known conditions', 'is_featured' => true,
                'summary' => 'A solid annual baseline covering blood, metabolic, cardiac and abdominal screening, finished in a single morning.',
                'description' => 'The essential check gives you an accurate annual picture without the extended imaging and cancer marker panel of the comprehensive package. It is the right starting point for a healthy adult who has never had a structured check-up, and it includes the same consultant review at the end.',
                'tests' => ['Complete blood count & ESR', 'Fasting glucose & HbA1c', 'Lipid profile', 'Liver function tests', 'Kidney function tests', 'Thyroid stimulating hormone (TSH)', 'Urine routine', 'ECG', 'Chest X-ray', 'Ultrasound of whole abdomen', 'Body composition & BMI', 'Consultant physician review'],
            ],
            [
                'name' => 'Cardiac Care Package', 'category' => 'cardiac',
                'price' => 16000, 'discount_price' => 12500, 'duration' => '3–4 hours',
                'suitable_for' => 'Family history of heart disease, smokers, hypertension', 'is_featured' => true,
                'summary' => 'Focused cardiovascular risk assessment including echocardiography, stress testing and a cardiologist consultation.',
                'description' => 'This package is built for people with a reason to be concerned about their heart: a family history, high blood pressure, diabetes, a smoking history, or symptoms that have not yet been investigated. It combines structural assessment through echocardiography with functional assessment through a treadmill stress test, and ends with a cardiologist who interprets both together.',
                'tests' => ['ECG', 'Echocardiography', 'Treadmill exercise stress test', 'Lipid profile', 'Fasting glucose & HbA1c', 'hs-CRP', 'Homocysteine', 'Kidney function tests', 'Chest X-ray', 'Blood pressure profiling', 'Cardiologist consultation', 'Dietitian consultation'],
            ],
            [
                'name' => 'Diabetes Care Package', 'category' => 'diabetes',
                'price' => 9500, 'discount_price' => 6900, 'duration' => '2–3 hours',
                'suitable_for' => 'Known diabetics and those with a strong family history',
                'summary' => 'Annual diabetes review covering glycaemic control plus screening for eye, kidney, nerve and foot complications.',
                'description' => 'Good diabetes care is about more than a glucose reading. This package checks glycaemic control and then systematically screens the four areas where diabetes does its lasting damage: the eyes, the kidneys, the nerves and the feet. It includes retinal screening, urine albumin testing, a foot examination and an endocrinologist review, plus a session with a diabetes educator.',
                'tests' => ['Fasting & post-prandial glucose', 'HbA1c', 'Lipid profile', 'Kidney function tests', 'Urine albumin-creatinine ratio', 'Dilated retinal examination', 'Diabetic foot assessment', 'Nerve conduction screening', 'ECG', 'Endocrinologist consultation', 'Diabetes educator session', 'Dietitian consultation'],
            ],
            [
                'name' => "Women's Wellness Package", 'category' => 'women',
                'price' => 14000, 'discount_price' => 10500, 'duration' => '3–4 hours',
                'suitable_for' => 'Women 30+', 'is_featured' => true,
                'summary' => 'Screening built around the conditions that actually affect women: breast and cervical health, thyroid, bone density and anaemia.',
                'description' => 'This package addresses areas that general health checks routinely under-cover in women. It includes breast examination with imaging appropriate to age, cervical screening, thyroid assessment, iron studies for anaemia, and bone density screening. All examinations are performed by female clinicians, and a female consultant reviews the results.',
                'tests' => ['Complete blood count & iron studies', 'Fasting glucose & HbA1c', 'Lipid profile', 'Thyroid profile', 'Vitamin D & calcium', 'Pap smear (cervical screening)', 'Clinical breast examination', 'Mammography (40+) or breast ultrasound (under 40)', 'Ultrasound of pelvis', 'Bone mineral density (DEXA)', 'Urine routine', 'Gynaecologist consultation'],
            ],
            [
                'name' => "Men's Wellness Package", 'category' => 'men',
                'price' => 13000, 'discount_price' => 9800, 'duration' => '3 hours',
                'suitable_for' => 'Men 35+',
                'summary' => 'Cardiovascular, metabolic, prostate and liver screening, with a consultant review at the end.',
                'description' => 'Men present later and less often, so this package is deliberately broad. It covers cardiovascular and metabolic risk, prostate screening for men over 45, liver assessment, and testosterone where clinically indicated, finishing with a consultant physician review and a written action plan.',
                'tests' => ['Complete blood count', 'Fasting glucose & HbA1c', 'Lipid profile', 'Liver function tests', 'Kidney function tests', 'Serum uric acid', 'PSA (prostate screening)', 'Thyroid stimulating hormone', 'ECG', 'Chest X-ray', 'Ultrasound of whole abdomen & prostate', 'Consultant physician review'],
            ],
            [
                'name' => 'Senior Citizen Package', 'category' => 'senior',
                'price' => 15000, 'discount_price' => 11500, 'duration' => '4 hours',
                'suitable_for' => 'Adults 60 and over',
                'summary' => 'Age-appropriate screening including bone density, vision, hearing, cognition and falls risk assessment.',
                'description' => 'Screening priorities change with age. This package covers the standard blood and cardiac work but adds the assessments that matter most later in life: bone density, vision and hearing, cognitive screening, and a falls risk assessment. A geriatric medicine consultant reviews the whole picture, including a medication review to identify drugs that may no longer be needed.',
                'tests' => ['Complete blood count & ESR', 'Fasting glucose & HbA1c', 'Lipid profile', 'Liver & kidney function tests', 'Thyroid profile', 'Vitamin D, B12 & calcium', 'ECG & echocardiography', 'Chest X-ray', 'Ultrasound of whole abdomen', 'Bone mineral density (DEXA)', 'Vision and hearing assessment', 'Cognitive screening', 'Falls risk assessment', 'Medication review', 'Geriatric consultant review'],
            ],
            [
                'name' => 'Basic Health Screening', 'category' => 'basic',
                'price' => 4500, 'discount_price' => 2900, 'duration' => '1–2 hours',
                'suitable_for' => 'Anyone wanting a quick baseline check',
                'summary' => 'An affordable entry-level check covering the core blood tests, ECG and a physician consultation.',
                'description' => 'A short, affordable screening for anyone who has not had a check-up recently. It covers the core blood work, urine analysis, an ECG and a physician consultation — enough to detect the most common undiagnosed conditions in Bangladeshi adults, particularly diabetes, high cholesterol and anaemia.',
                'tests' => ['Complete blood count', 'Fasting blood glucose', 'Lipid profile', 'Serum creatinine', 'SGPT (liver)', 'Urine routine', 'ECG', 'Blood pressure & BMI', 'Physician consultation'],
            ],
            [
                'name' => 'Pre-Employment Medical', 'category' => 'basic',
                'price' => 3500, 'duration' => '1–2 hours',
                'suitable_for' => 'Job applicants and corporate onboarding',
                'summary' => 'Standard fitness-for-work medical with a certified report issued the same day.',
                'description' => 'A structured pre-employment examination covering general fitness, vision, chest imaging and the standard screening blood work, issued as a signed certificate on the same day. Corporate rates are available for bulk onboarding.',
                'tests' => ['Complete blood count', 'Fasting blood glucose', 'Blood grouping & Rh', 'HBsAg & anti-HCV', 'Urine routine', 'Chest X-ray', 'Vision and colour vision', 'Physical fitness examination', 'Same-day certificate'],
            ],
        ];
    }
}

<?php

namespace Database\Seeders;

use App\Models\DiagnosticTest;
use Illuminate\Database\Seeder;

/**
 * The published price list.
 *
 * Prices are whole taka and are what the counter charges — no minor units, no
 * "from". Preparation text matters more than it looks: fasting and previous
 * films are what patients most often get wrong and have to come back for.
 */
class DiagnosticTestSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->tests() as $order => $test) {
            DiagnosticTest::updateOrCreate(
                ['slug' => $test['slug']],
                $test + ['sort_order' => $order * 10]
            );
        }
    }

    private function tests(): array
    {
        return [
            // — Laboratory ————————————————————————————————
            [
                'slug' => 'complete-blood-count',
                'name' => 'Complete Blood Count (CBC) with ESR',
                'code' => 'CBC',
                'category' => 'pathology',
                'summary' => 'Counts red cells, white cells and platelets. The first test for anaemia, infection and most unexplained fatigue.',
                'preparation' => 'No fasting needed. Come at any time during collection hours.',
                'sample_type' => 'Blood (EDTA)',
                'report_time' => 'Same day, by 6:00 PM',
                'price' => 500,
                'is_home_collection' => true,
                'is_featured' => true,
            ],
            [
                'slug' => 'blood-glucose-fasting',
                'name' => 'Blood Glucose — Fasting',
                'code' => 'FBS',
                'category' => 'pathology',
                'summary' => 'A single fasting reading, used for diabetes screening and for checking control alongside HbA1c.',
                'preparation' => 'Fast for 8 to 10 hours. Water is fine; no tea, no sugar.',
                'sample_type' => 'Blood (fluoride)',
                'report_time' => 'Same day, within 4 hours',
                'price' => 200,
                'is_home_collection' => true,
            ],
            [
                'slug' => 'hba1c',
                'name' => 'HbA1c — Glycated Haemoglobin',
                'code' => 'HBA1C',
                'category' => 'pathology',
                'summary' => 'Average blood sugar over the past two to three months. Not affected by what you ate this morning.',
                'preparation' => 'No fasting needed.',
                'sample_type' => 'Blood (EDTA)',
                'report_time' => 'Same day, by 6:00 PM',
                'price' => 1000,
                'is_home_collection' => true,
                'is_featured' => true,
            ],
            [
                'slug' => 'lipid-profile',
                'name' => 'Lipid Profile',
                'code' => 'LIPID',
                'category' => 'pathology',
                'summary' => 'Total cholesterol, LDL, HDL and triglycerides — the standard cardiac risk panel.',
                'preparation' => 'Fast for 12 hours. Water only. Take your regular medicines unless a doctor says otherwise.',
                'sample_type' => 'Blood (serum)',
                'report_time' => 'Same day, by 6:00 PM',
                'price' => 1400,
                'discount_price' => 1200,
                'is_home_collection' => true,
                'is_featured' => true,
            ],
            [
                'slug' => 'liver-function-test',
                'name' => 'Liver Function Test (LFT)',
                'code' => 'LFT',
                'category' => 'pathology',
                'summary' => 'Bilirubin, SGPT, SGOT, alkaline phosphatase and proteins. Ordered for jaundice, hepatitis follow-up and before some long-term medicines.',
                'preparation' => 'Fast for 8 hours where the test is ordered with a lipid profile; otherwise no preparation.',
                'sample_type' => 'Blood (serum)',
                'report_time' => 'Same day, by 8:00 PM',
                'price' => 1800,
                'is_home_collection' => true,
            ],
            [
                'slug' => 'kidney-function-test',
                'name' => 'Kidney Function Test (KFT)',
                'code' => 'KFT',
                'category' => 'pathology',
                'summary' => 'Urea, creatinine and electrolytes, with eGFR calculated from your age and sex.',
                'preparation' => 'No fasting needed. Keep drinking water normally.',
                'sample_type' => 'Blood (serum)',
                'report_time' => 'Same day, by 8:00 PM',
                'price' => 1600,
                'is_home_collection' => true,
            ],
            [
                'slug' => 'thyroid-profile',
                'name' => 'Thyroid Profile (TSH, FT3, FT4)',
                'code' => 'TFT',
                'category' => 'pathology',
                'summary' => 'Thyroid hormone levels, for tiredness, weight change, hair loss and pregnancy screening.',
                'preparation' => 'No fasting needed. Take thyroid medicine after the sample, not before.',
                'sample_type' => 'Blood (serum)',
                'report_time' => 'Within 24 hours',
                'price' => 2200,
                'is_home_collection' => true,
            ],
            [
                'slug' => 'urine-routine-microscopic',
                'name' => 'Urine Routine and Microscopic Examination',
                'code' => 'URINE-RME',
                'category' => 'pathology',
                'summary' => 'Screens for urinary infection, protein, sugar and blood.',
                'preparation' => 'Bring a mid-stream sample in a sterile container from the counter, or collect one here.',
                'sample_type' => 'Urine',
                'report_time' => 'Same day, within 4 hours',
                'price' => 300,
            ],
            [
                'slug' => 'serum-electrolytes',
                'name' => 'Serum Electrolytes',
                'code' => 'ELEC',
                'category' => 'pathology',
                'summary' => 'Sodium, potassium and chloride. Ordered after prolonged vomiting or diarrhoea, and alongside several heart and blood-pressure medicines.',
                'preparation' => 'No fasting needed.',
                'sample_type' => 'Blood (serum)',
                'report_time' => 'Same day, within 4 hours',
                'price' => 1200,
                'is_home_collection' => true,
            ],
            [
                'slug' => 'c-reactive-protein',
                'name' => 'C-Reactive Protein (CRP)',
                'code' => 'CRP',
                'category' => 'pathology',
                'summary' => 'A general marker of inflammation, used to judge how an infection is responding to treatment.',
                'preparation' => 'No fasting needed.',
                'sample_type' => 'Blood (serum)',
                'report_time' => 'Same day, by 6:00 PM',
                'price' => 900,
                'is_home_collection' => true,
            ],
            [
                'slug' => 'dengue-ns1-antigen',
                'name' => 'Dengue NS1 Antigen',
                'code' => 'NS1',
                'category' => 'pathology',
                'summary' => 'Detects dengue in the first five days of fever, when antibody tests are still negative.',
                'preparation' => 'No preparation. Come as early in the fever as possible — NS1 falls away after day five.',
                'sample_type' => 'Blood (serum)',
                'report_time' => 'Same day, within 4 hours',
                'price' => 900,
                'is_home_collection' => true,
                'is_featured' => true,
            ],
            [
                'slug' => 'vitamin-d-25-oh',
                'name' => 'Vitamin D (25-OH)',
                'code' => 'VITD',
                'category' => 'pathology',
                'summary' => 'Vitamin D level, for bone pain, repeated fractures and unexplained muscle weakness.',
                'preparation' => 'No fasting needed.',
                'sample_type' => 'Blood (serum)',
                'report_time' => 'Within 48 hours',
                'price' => 3500,
                'is_home_collection' => true,
            ],

            // — Radiology and imaging —————————————————————
            [
                'slug' => 'chest-x-ray',
                'name' => 'Chest X-ray (P/A view)',
                'code' => 'CXR',
                'category' => 'imaging',
                'summary' => 'A single chest film for cough, breathlessness, chest injury and pre-operative clearance.',
                'preparation' => 'Remove metal jewellery. Tell the radiographer if you are or might be pregnant.',
                'report_time' => 'Film immediately; reported within 4 hours',
                'price' => 700,
                'is_featured' => true,
            ],
            [
                'slug' => 'ultrasonogram-whole-abdomen',
                'name' => 'Ultrasonogram of Whole Abdomen',
                'code' => 'USG-ABD',
                'category' => 'imaging',
                'summary' => 'Liver, gallbladder, kidneys, pancreas and spleen, with the pelvis included.',
                'preparation' => 'Fast for 6 hours. Drink four glasses of water an hour before and do not pass urine.',
                'report_time' => 'Same day, within 3 hours',
                'price' => 1500,
            ],
            [
                'slug' => 'ct-scan-brain-plain',
                'name' => 'CT Scan of Brain (plain)',
                'code' => 'CT-BRAIN',
                'category' => 'imaging',
                'summary' => 'A fast scan for head injury, stroke and persistent headache. No contrast, so no injection.',
                'preparation' => 'No preparation. Bring any previous scans and films with you.',
                'report_time' => 'Within 24 hours',
                'price' => 5000,
            ],
            [
                'slug' => 'mri-brain-plain',
                'name' => 'MRI of Brain (plain)',
                'code' => 'MRI-BRAIN',
                'category' => 'imaging',
                'summary' => 'Detailed soft-tissue imaging, ordered where a CT is normal but symptoms continue.',
                'preparation' => 'Tell us before booking if you have a pacemaker, cochlear implant, metal implant or metal fragments in the eye. Leave jewellery at home.',
                'report_time' => 'Within 24 hours',
                'price' => 9000,
            ],
            [
                'slug' => 'digital-mammogram',
                'name' => 'Digital Mammogram (both breasts)',
                'code' => 'MAMMO',
                'category' => 'imaging',
                'summary' => 'Breast screening and assessment of a lump. Reported by a consultant radiologist.',
                'preparation' => 'Book for the week after your period, when the breasts are least tender. Do not use talcum powder or deodorant on the day.',
                'report_time' => 'Within 24 hours',
                'price' => 3000,
            ],

            // — Cardiac diagnostics ————————————————————————
            [
                'slug' => 'electrocardiogram',
                'name' => 'Electrocardiogram (ECG)',
                'code' => 'ECG',
                'category' => 'cardiology',
                'summary' => 'A twelve-lead trace of the heart rhythm. Takes about five minutes.',
                'preparation' => 'No preparation. Wear something that opens at the front if you can.',
                'report_time' => 'Immediately',
                'price' => 500,
                'is_featured' => true,
            ],
            [
                'slug' => 'echocardiogram-colour-doppler',
                'name' => 'Echocardiogram with Colour Doppler',
                'code' => 'ECHO',
                'category' => 'cardiology',
                'summary' => 'Ultrasound of the heart: pumping function, valves and chamber sizes.',
                'preparation' => 'No preparation. Bring previous echo reports for comparison.',
                'report_time' => 'Same day, within 3 hours',
                'price' => 3000,
            ],
            [
                'slug' => 'exercise-tolerance-test',
                'name' => 'Exercise Tolerance Test (ETT)',
                'code' => 'ETT',
                'category' => 'cardiology',
                'summary' => 'A treadmill test that looks for changes appearing only under exertion.',
                'preparation' => 'Light meal 2 hours before. Wear walking shoes and loose clothing. Ask the referring doctor which heart medicines to hold.',
                'report_time' => 'Same day, within 3 hours',
                'price' => 3500,
            ],
            [
                'slug' => 'holter-monitoring-24-hour',
                'name' => 'Holter Monitoring (24 hours)',
                'code' => 'HOLTER',
                'category' => 'cardiology',
                'summary' => 'A recorder worn for a day, for palpitations and blackouts that a short ECG misses.',
                'preparation' => 'Bathe before fitting — the device cannot get wet. Keep a written note of any symptoms and the time.',
                'report_time' => 'Within 48 hours of returning the device',
                'price' => 4500,
            ],

            // — Endoscopy ——————————————————————————————————
            [
                'slug' => 'upper-gi-endoscopy',
                'name' => 'Upper GI Endoscopy',
                'code' => 'UGIE',
                'category' => 'endoscopy',
                'summary' => 'Examines the oesophagus, stomach and duodenum, with biopsy if needed.',
                'preparation' => 'Nothing to eat for 8 hours and nothing to drink for 4 hours. Bring someone with you if sedation is planned.',
                'report_time' => 'Immediately; biopsy within 5 days',
                'price' => 4000,
            ],
            [
                'slug' => 'colonoscopy',
                'name' => 'Colonoscopy',
                'code' => 'COLON',
                'category' => 'endoscopy',
                'summary' => 'Examines the large bowel, for bleeding, persistent change in bowel habit and screening.',
                'preparation' => 'Bowel preparation starts the day before; instructions are given when you book. Come with someone who can take you home.',
                'report_time' => 'Immediately; biopsy within 5 days',
                'price' => 9000,
            ],
        ];
    }
}

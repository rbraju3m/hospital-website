<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DoctorSeeder extends Seeder
{
    /**
     * Weekly chamber patterns. Sunday = 0 … Saturday = 6, matching
     * Carbon::dayOfWeek and DoctorSchedule::DAYS. Friday (5) is left mostly
     * clear to reflect the local weekend.
     */
    private const PATTERNS = [
        'morning_sat_wed' => [['days' => [6, 0, 1, 2, 3], 'start' => '09:00', 'end' => '13:00', 'slot' => 20]],
        'evening_sat_wed' => [['days' => [6, 0, 1, 2, 3], 'start' => '17:00', 'end' => '21:00', 'slot' => 20]],
        'alt_days_evening' => [['days' => [6, 1, 3], 'start' => '18:00', 'end' => '21:00', 'slot' => 15]],
        'alt_days_morning' => [['days' => [0, 2, 4], 'start' => '10:00', 'end' => '13:30', 'slot' => 15]],
        'split_shift' => [
            ['days' => [6, 0, 1, 2, 3], 'start' => '10:00', 'end' => '13:00', 'slot' => 20],
            ['days' => [6, 0, 1, 2, 3], 'start' => '18:00', 'end' => '20:30', 'slot' => 20],
        ],
        'three_day_long' => [['days' => [0, 2, 4], 'start' => '16:00', 'end' => '21:00', 'slot' => 20]],
        'weekend_heavy' => [['days' => [4, 5, 6], 'start' => '10:00', 'end' => '14:00', 'slot' => 20]],
    ];

    public function run(): void
    {
        foreach ($this->doctors() as $i => $data) {
            $department = Department::where('slug', $data['department'])->first();

            if (! $department) {
                continue;
            }

            $doctor = Doctor::updateOrCreate(
                ['slug' => Str::slug($data['name'])],
                [
                    'department_id' => $department->id,
                    'name' => $data['name'],
                    'designation' => $data['designation'],
                    'qualifications' => $data['qualifications'],
                    'speciality' => $data['speciality'],
                    'expertise' => $data['expertise'],
                    'gender' => $data['gender'],
                    'experience_years' => $data['experience'],
                    'about' => $data['about'],
                    'languages' => $data['languages'] ?? ['Bangla', 'English'],
                    'chamber' => $data['chamber'],
                    'consultation_fee' => $data['fee'],
                    'follow_up_fee' => (int) round($data['fee'] * 0.6 / 50) * 50,
                    'accepts_online_appointment' => $data['online'] ?? true,
                    'is_featured' => $data['featured'] ?? false,
                    'is_active' => true,
                    'sort_order' => $i + 1,
                ]
            );

            $doctor->schedules()->delete();

            foreach (self::PATTERNS[$data['pattern']] as $block) {
                foreach ($block['days'] as $day) {
                    DoctorSchedule::create([
                        'doctor_id' => $doctor->id,
                        'day_of_week' => $day,
                        'start_time' => $block['start'],
                        'end_time' => $block['end'],
                        'slot_minutes' => $block['slot'],
                        'capacity_per_slot' => 1,
                        'location' => $data['chamber'],
                        'is_active' => true,
                    ]);
                }
            }
        }
    }

    private function doctors(): array
    {
        return [
            // ---- Cardiac Sciences -------------------------------------------------
            [
                'name' => 'Prof. Dr. Ashraful Haque', 'department' => 'cardiac-sciences',
                'designation' => 'Senior Consultant & Head, Interventional Cardiology',
                'qualifications' => 'MBBS, FCPS (Medicine), MD (Cardiology), FACC',
                'speciality' => 'Interventional Cardiology', 'gender' => 'male', 'experience' => 26, 'fee' => 2000,
                'expertise' => ['Coronary angioplasty', 'Complex PCI', 'Structural heart intervention', 'Heart failure'],
                'chamber' => 'Room 302, Level 3, Tower A', 'pattern' => 'evening_sat_wed', 'featured' => true,
                'about' => 'Professor Haque has performed over 12,000 coronary interventions and led the establishment of the hospital\'s 24/7 primary angioplasty service. He trained in interventional cardiology in the United Kingdom and teaches on the national cardiology fellowship programme.',
            ],
            [
                'name' => 'Dr. Nusrat Jahan Mim', 'department' => 'cardiac-sciences',
                'designation' => 'Consultant, Non-Invasive Cardiology',
                'qualifications' => 'MBBS, MD (Cardiology), Fellow in Echocardiography',
                'speciality' => 'Echocardiography & Preventive Cardiology', 'gender' => 'female', 'experience' => 12, 'fee' => 1500,
                'expertise' => ['Stress echocardiography', 'Preventive cardiology', 'Hypertension', 'Women\'s heart health'],
                'chamber' => 'Room 305, Level 3, Tower A', 'pattern' => 'morning_sat_wed', 'featured' => true,
                'about' => 'Dr. Mim runs the hospital\'s preventive cardiology clinic with a particular interest in cardiovascular risk in women, a group whose symptoms are frequently under-investigated. She holds a fellowship in advanced echocardiography.',
            ],
            [
                'name' => 'Dr. Kamrul Hasan Shuvo', 'department' => 'cardiac-sciences',
                'designation' => 'Consultant, Cardiac Surgery',
                'qualifications' => 'MBBS, MS (Cardiothoracic Surgery), MRCS',
                'speciality' => 'Cardiothoracic Surgery', 'gender' => 'male', 'experience' => 16, 'fee' => 2000,
                'expertise' => ['CABG', 'Valve replacement', 'Beating heart surgery', 'Aortic surgery'],
                'chamber' => 'Room 310, Level 3, Tower A', 'pattern' => 'alt_days_evening',
                'about' => 'Dr. Shuvo specialises in off-pump coronary artery bypass grafting and complex valve repair. He has a research interest in reducing transfusion requirements during cardiac surgery.',
            ],
            [
                'name' => 'Dr. Farhana Yeasmin', 'department' => 'cardiac-sciences',
                'designation' => 'Consultant, Electrophysiology',
                'qualifications' => 'MBBS, MD (Cardiology), Fellowship in Cardiac Electrophysiology',
                'speciality' => 'Arrhythmia & Device Therapy', 'gender' => 'female', 'experience' => 11, 'fee' => 1800,
                'expertise' => ['Pacemaker implantation', 'Radiofrequency ablation', 'Atrial fibrillation', 'Syncope evaluation'],
                'chamber' => 'Room 308, Level 3, Tower A', 'pattern' => 'alt_days_morning',
                'about' => 'Dr. Yeasmin leads the arrhythmia service, covering device implantation and catheter ablation. She established the hospital\'s dedicated atrial fibrillation clinic.',
            ],

            // ---- Neurosciences ----------------------------------------------------
            [
                'name' => 'Prof. Dr. Mahbubur Rahman', 'department' => 'neurosciences',
                'designation' => 'Senior Consultant & Head, Neurology',
                'qualifications' => 'MBBS, FCPS (Medicine), MD (Neurology), FRCP (Edin)',
                'speciality' => 'Stroke & Neurocritical Care', 'gender' => 'male', 'experience' => 28, 'fee' => 2000,
                'expertise' => ['Acute stroke', 'Thrombolysis', 'Neurocritical care', 'Movement disorders'],
                'chamber' => 'Room 402, Level 4, Tower A', 'pattern' => 'evening_sat_wed', 'featured' => true,
                'about' => 'Professor Rahman designed the hospital\'s acute stroke pathway, which has cut median door-to-needle time to under 40 minutes. He is a Fellow of the Royal College of Physicians of Edinburgh and publishes on stroke outcomes in South Asian populations.',
            ],
            [
                'name' => 'Dr. Sadia Afrin Rupa', 'department' => 'neurosciences',
                'designation' => 'Consultant, Neurology',
                'qualifications' => 'MBBS, MD (Neurology), Fellow in Epileptology',
                'speciality' => 'Epilepsy & Headache', 'gender' => 'female', 'experience' => 10, 'fee' => 1500,
                'expertise' => ['Epilepsy', 'Migraine and headache', 'EEG interpretation', 'Women with epilepsy'],
                'chamber' => 'Room 406, Level 4, Tower A', 'pattern' => 'split_shift', 'featured' => true,
                'about' => 'Dr. Rupa runs the epilepsy monitoring service and a dedicated headache clinic. She has a specific interest in managing epilepsy through pregnancy, where medication choices carry particular trade-offs.',
            ],
            [
                'name' => 'Dr. Tanvir Ahmed Chowdhury', 'department' => 'neurosciences',
                'designation' => 'Consultant, Neurosurgery',
                'qualifications' => 'MBBS, MS (Neurosurgery), Fellowship in Skull Base Surgery',
                'speciality' => 'Brain & Spine Surgery', 'gender' => 'male', 'experience' => 15, 'fee' => 2000,
                'expertise' => ['Brain tumour surgery', 'Minimally invasive spine surgery', 'Skull base surgery', 'Hydrocephalus'],
                'chamber' => 'Room 410, Level 4, Tower A', 'pattern' => 'alt_days_evening',
                'about' => 'Dr. Chowdhury performs neuro-navigation guided tumour resection and minimally invasive spinal procedures. He completed a skull base surgery fellowship in Singapore.',
            ],

            // ---- Oncology ---------------------------------------------------------
            [
                'name' => 'Prof. Dr. Shamima Nasrin', 'department' => 'oncology',
                'designation' => 'Senior Consultant & Head, Medical Oncology',
                'qualifications' => 'MBBS, FCPS (Medicine), MD (Oncology), Fellowship in Medical Oncology (India)',
                'speciality' => 'Medical Oncology', 'gender' => 'female', 'experience' => 22, 'fee' => 2000,
                'expertise' => ['Breast cancer', 'Lung cancer', 'Targeted therapy', 'Immunotherapy'],
                'chamber' => 'Room 210, Level 2, Tower B', 'pattern' => 'morning_sat_wed', 'featured' => true,
                'about' => 'Professor Nasrin chairs the hospital\'s multidisciplinary tumour board and has built the breast cancer service around early diagnosis and breast-conserving surgery wherever it is oncologically safe.',
            ],
            [
                'name' => 'Dr. Imtiaz Mahmud', 'department' => 'oncology',
                'designation' => 'Consultant, Radiation Oncology',
                'qualifications' => 'MBBS, MD (Radiation Oncology), FRCR',
                'speciality' => 'Radiation Oncology', 'gender' => 'male', 'experience' => 14, 'fee' => 1800,
                'expertise' => ['IMRT and IGRT', 'Stereotactic radiotherapy', 'Head-neck cancer', 'Palliative radiotherapy'],
                'chamber' => 'Room 214, Level 2, Tower B', 'pattern' => 'three_day_long',
                'about' => 'Dr. Mahmud plans and delivers image-guided radiotherapy with a focus on head-neck and thoracic malignancy. He holds fellowship of the Royal College of Radiologists.',
            ],
            [
                'name' => 'Dr. Rezwana Karim', 'department' => 'oncology',
                'designation' => 'Consultant, Surgical Oncology',
                'qualifications' => 'MBBS, MS (Surgery), Fellowship in Surgical Oncology',
                'speciality' => 'Surgical Oncology', 'gender' => 'female', 'experience' => 13, 'fee' => 1800,
                'expertise' => ['Breast conserving surgery', 'Gastrointestinal cancer surgery', 'Sentinel node biopsy', 'Oncoplastic reconstruction'],
                'chamber' => 'Room 216, Level 2, Tower B', 'pattern' => 'alt_days_morning',
                'about' => 'Dr. Karim specialises in oncoplastic breast surgery and gastrointestinal cancer resection, and introduced sentinel lymph node biopsy to the department.',
            ],

            // ---- Orthopaedics -----------------------------------------------------
            [
                'name' => 'Prof. Dr. Golam Sarwar', 'department' => 'orthopaedics-joint-replacement',
                'designation' => 'Senior Consultant & Head, Joint Replacement',
                'qualifications' => 'MBBS, MS (Orthopaedics), Fellowship in Arthroplasty (UK)',
                'speciality' => 'Hip & Knee Replacement', 'gender' => 'male', 'experience' => 25, 'fee' => 2000,
                'expertise' => ['Total knee replacement', 'Total hip replacement', 'Revision arthroplasty', 'Enhanced recovery'],
                'chamber' => 'Room 502, Level 5, Tower A', 'pattern' => 'evening_sat_wed', 'featured' => true,
                'about' => 'Professor Sarwar has completed more than 6,000 joint replacements and introduced the enhanced-recovery protocol that now gets most arthroplasty patients walking on the day of surgery.',
            ],
            [
                'name' => 'Dr. Mehedi Hasan Zim', 'department' => 'orthopaedics-joint-replacement',
                'designation' => 'Consultant, Sports Medicine & Arthroscopy',
                'qualifications' => 'MBBS, MS (Orthopaedics), Fellowship in Sports Injury',
                'speciality' => 'Arthroscopy & Sports Injury', 'gender' => 'male', 'experience' => 11, 'fee' => 1500,
                'expertise' => ['ACL reconstruction', 'Shoulder arthroscopy', 'Meniscus repair', 'Sports rehabilitation'],
                'chamber' => 'Room 506, Level 5, Tower A', 'pattern' => 'split_shift',
                'about' => 'Dr. Zim treats sporting injuries in athletes and active adults, with a practice built around arthroscopic reconstruction and structured return-to-play rehabilitation.',
            ],
            [
                'name' => 'Dr. Anika Tabassum', 'department' => 'orthopaedics-joint-replacement',
                'designation' => 'Consultant, Spine Surgery',
                'qualifications' => 'MBBS, MS (Orthopaedics), Fellowship in Spine Surgery',
                'speciality' => 'Spine Surgery', 'gender' => 'female', 'experience' => 12, 'fee' => 1800,
                'expertise' => ['Minimally invasive spine surgery', 'Disc replacement', 'Scoliosis correction', 'Spinal trauma'],
                'chamber' => 'Room 508, Level 5, Tower A', 'pattern' => 'alt_days_morning',
                'about' => 'Dr. Tabassum performs minimally invasive spinal decompression and fusion, and is careful to exhaust conservative management before recommending surgery.',
            ],

            // ---- Gastroenterology -------------------------------------------------
            [
                'name' => 'Prof. Dr. Aminul Islam Bhuiyan', 'department' => 'gastroenterology-hepatology',
                'designation' => 'Senior Consultant & Head, Gastroenterology',
                'qualifications' => 'MBBS, FCPS (Medicine), MD (Gastroenterology)',
                'speciality' => 'Therapeutic Endoscopy & Hepatology', 'gender' => 'male', 'experience' => 24, 'fee' => 1800,
                'expertise' => ['ERCP', 'Endoscopic ultrasound', 'Liver disease', 'GI bleeding'],
                'chamber' => 'Room 312, Level 3, Tower B', 'pattern' => 'evening_sat_wed', 'featured' => true,
                'about' => 'Professor Bhuiyan established the therapeutic endoscopy unit and runs the hepatology clinic, where much of the caseload is hepatitis B and increasingly fatty liver disease.',
            ],
            [
                'name' => 'Dr. Shafiqul Alam', 'department' => 'gastroenterology-hepatology',
                'designation' => 'Consultant, Gastroenterology',
                'qualifications' => 'MBBS, MD (Gastroenterology)',
                'speciality' => 'Luminal Gastroenterology', 'gender' => 'male', 'experience' => 10, 'fee' => 1500,
                'expertise' => ['Inflammatory bowel disease', 'Colonoscopy', 'Irritable bowel syndrome', 'Coeliac disease'],
                'chamber' => 'Room 316, Level 3, Tower B', 'pattern' => 'alt_days_morning',
                'about' => 'Dr. Alam runs the inflammatory bowel disease clinic and the colorectal cancer screening programme.',
            ],

            // ---- Nephrology & Urology --------------------------------------------
            [
                'name' => 'Dr. Rokeya Sultana', 'department' => 'nephrology-urology',
                'designation' => 'Senior Consultant, Nephrology',
                'qualifications' => 'MBBS, FCPS (Medicine), MD (Nephrology)',
                'speciality' => 'Nephrology & Dialysis', 'gender' => 'female', 'experience' => 19, 'fee' => 1800,
                'expertise' => ['Chronic kidney disease', 'Haemodialysis', 'Transplant medicine', 'Glomerular disease'],
                'chamber' => 'Room 604, Level 6, Tower B', 'pattern' => 'morning_sat_wed', 'featured' => true,
                'about' => 'Dr. Sultana oversees the dialysis unit and the transplant work-up clinic, with a research interest in slowing progression of diabetic kidney disease.',
            ],
            [
                'name' => 'Dr. Nazmul Karim Rana', 'department' => 'nephrology-urology',
                'designation' => 'Consultant, Urology',
                'qualifications' => 'MBBS, MS (Urology), Fellowship in Endourology',
                'speciality' => 'Endourology & Stone Disease', 'gender' => 'male', 'experience' => 13, 'fee' => 1500,
                'expertise' => ['Laser stone surgery', 'RIRS and PCNL', 'Prostate surgery', 'Uro-oncology'],
                'chamber' => 'Room 608, Level 6, Tower B', 'pattern' => 'three_day_long',
                'about' => 'Dr. Rana performs minimally invasive stone surgery using holmium laser, and manages benign prostatic enlargement and urological cancers.',
            ],

            // ---- Women's Health ---------------------------------------------------
            [
                'name' => 'Prof. Dr. Nasreen Akhter', 'department' => 'womens-health-obstetrics',
                'designation' => 'Senior Consultant & Head, Obstetrics & Gynaecology',
                'qualifications' => 'MBBS, FCPS (Obs & Gynae), MRCOG (UK)',
                'speciality' => 'High-Risk Obstetrics', 'gender' => 'female', 'experience' => 27, 'fee' => 2000,
                'expertise' => ['High-risk pregnancy', 'Caesarean and normal delivery', 'Recurrent miscarriage', 'Foetal medicine'],
                'chamber' => 'Room 702, Level 7, Tower A', 'pattern' => 'split_shift', 'featured' => true,
                'about' => 'Professor Akhter has delivered more than 9,000 babies and leads the high-risk pregnancy service. She holds membership of the Royal College of Obstetricians and Gynaecologists.',
            ],
            [
                'name' => 'Dr. Sumaiya Islam Neha', 'department' => 'womens-health-obstetrics',
                'designation' => 'Consultant, Gynaecology & Infertility',
                'qualifications' => 'MBBS, FCPS (Obs & Gynae), Fellowship in Reproductive Medicine',
                'speciality' => 'Fertility & Laparoscopic Gynaecology', 'gender' => 'female', 'experience' => 12, 'fee' => 1500,
                'expertise' => ['Infertility evaluation', 'Laparoscopic surgery', 'PCOS', 'Endometriosis'],
                'chamber' => 'Room 706, Level 7, Tower A', 'pattern' => 'evening_sat_wed', 'featured' => true,
                'about' => 'Dr. Neha runs the fertility clinic and performs laparoscopic gynaecological surgery, with a particular focus on endometriosis and polycystic ovary syndrome.',
            ],
            [
                'name' => 'Dr. Marufa Haque Priya', 'department' => 'womens-health-obstetrics',
                'designation' => 'Consultant, Obstetrics & Gynaecology',
                'qualifications' => 'MBBS, FCPS (Obs & Gynae)',
                'speciality' => 'General Obstetrics & Gynaecology', 'gender' => 'female', 'experience' => 9, 'fee' => 1200,
                'expertise' => ['Antenatal care', 'Menstrual disorders', 'Contraception counselling', 'Menopause'],
                'chamber' => 'Room 708, Level 7, Tower A', 'pattern' => 'alt_days_morning',
                'about' => 'Dr. Priya provides routine antenatal and gynaecological care, and holds the adolescent gynaecology clinic on alternate weeks.',
            ],

            // ---- Paediatrics ------------------------------------------------------
            [
                'name' => 'Prof. Dr. Zahid Hossain', 'department' => 'paediatrics-neonatology',
                'designation' => 'Senior Consultant & Head, Paediatrics',
                'qualifications' => 'MBBS, FCPS (Paediatrics), MD (Neonatology)',
                'speciality' => 'Neonatology', 'gender' => 'male', 'experience' => 23, 'fee' => 1500,
                'expertise' => ['Premature newborn care', 'Neonatal ventilation', 'Growth monitoring', 'Neonatal jaundice'],
                'chamber' => 'Room 710, Level 7, Tower B', 'pattern' => 'morning_sat_wed', 'featured' => true,
                'about' => 'Professor Hossain built the Level III neonatal intensive care unit and introduced therapeutic hypothermia for birth asphyxia, a service previously unavailable in the area.',
            ],
            [
                'name' => 'Dr. Ishrat Jahan Mim', 'department' => 'paediatrics-neonatology',
                'designation' => 'Consultant, Paediatrics',
                'qualifications' => 'MBBS, FCPS (Paediatrics)',
                'speciality' => 'General Paediatrics', 'gender' => 'female', 'experience' => 10, 'fee' => 1000,
                'expertise' => ['Childhood infections', 'Immunisation', 'Asthma and allergy', 'Nutrition'],
                'chamber' => 'Room 714, Level 7, Tower B', 'pattern' => 'split_shift',
                'about' => 'Dr. Mim runs general paediatric clinics and the immunisation programme, and has a special interest in childhood asthma.',
            ],
            [
                'name' => 'Dr. Sabbir Ahmed Joy', 'department' => 'paediatrics-neonatology',
                'designation' => 'Consultant, Paediatric Cardiology',
                'qualifications' => 'MBBS, FCPS (Paediatrics), Fellowship in Paediatric Cardiology',
                'speciality' => 'Paediatric Cardiology', 'gender' => 'male', 'experience' => 11, 'fee' => 1500,
                'expertise' => ['Congenital heart disease', 'Paediatric echocardiography', 'Rheumatic heart disease', 'Foetal echo'],
                'chamber' => 'Room 716, Level 7, Tower B', 'pattern' => 'alt_days_evening',
                'about' => 'Dr. Joy assesses congenital and acquired heart disease in children, including foetal echocardiography for pregnancies at risk.',
            ],

            // ---- Pulmonology ------------------------------------------------------
            [
                'name' => 'Dr. Faisal Ahmed Nabil', 'department' => 'pulmonology-respiratory-medicine',
                'designation' => 'Senior Consultant, Respiratory Medicine',
                'qualifications' => 'MBBS, FCPS (Medicine), MD (Pulmonology)',
                'speciality' => 'Asthma, COPD & Sleep Medicine', 'gender' => 'male', 'experience' => 18, 'fee' => 1500,
                'expertise' => ['Asthma', 'COPD', 'Sleep apnoea', 'Bronchoscopy'],
                'chamber' => 'Room 404, Level 4, Tower B', 'pattern' => 'evening_sat_wed', 'featured' => true,
                'about' => 'Dr. Nabil leads the sleep laboratory and the inhaler technique clinic, an intervention that has cut avoidable asthma admissions in his caseload.',
            ],
            [
                'name' => 'Dr. Tahmina Akter Shova', 'department' => 'pulmonology-respiratory-medicine',
                'designation' => 'Consultant, Respiratory Medicine',
                'qualifications' => 'MBBS, MD (Pulmonology)',
                'speciality' => 'Interstitial Lung Disease & TB', 'gender' => 'female', 'experience' => 9, 'fee' => 1200,
                'expertise' => ['Tuberculosis', 'Interstitial lung disease', 'Pleural procedures', 'Pulmonary rehabilitation'],
                'chamber' => 'Room 408, Level 4, Tower B', 'pattern' => 'alt_days_morning',
                'about' => 'Dr. Shova manages tuberculosis and interstitial lung disease, and coordinates the pulmonary rehabilitation programme.',
            ],

            // ---- Endocrinology ----------------------------------------------------
            [
                'name' => 'Prof. Dr. Selina Parvin', 'department' => 'endocrinology-diabetes',
                'designation' => 'Senior Consultant & Head, Endocrinology',
                'qualifications' => 'MBBS, FCPS (Medicine), MD (Endocrinology)',
                'speciality' => 'Diabetes & Thyroid Disorders', 'gender' => 'female', 'experience' => 21, 'fee' => 1800,
                'expertise' => ['Type 1 and type 2 diabetes', 'Thyroid disease', 'Diabetic foot', 'Osteoporosis'],
                'chamber' => 'Room 206, Level 2, Tower A', 'pattern' => 'split_shift', 'featured' => true,
                'about' => 'Professor Parvin built the diabetes education programme and the diabetic foot clinic, which has substantially reduced amputation referrals from her patient group.',
            ],
            [
                'name' => 'Dr. Arifur Rahman Sajid', 'department' => 'endocrinology-diabetes',
                'designation' => 'Consultant, Endocrinology',
                'qualifications' => 'MBBS, MD (Endocrinology)',
                'speciality' => 'Metabolic & Obesity Medicine', 'gender' => 'male', 'experience' => 8, 'fee' => 1200,
                'expertise' => ['Obesity management', 'PCOS', 'Metabolic syndrome', 'Gestational diabetes'],
                'chamber' => 'Room 208, Level 2, Tower A', 'pattern' => 'alt_days_evening',
                'about' => 'Dr. Sajid runs the metabolic and obesity clinic, working alongside dietitians on structured weight management.',
            ],

            // ---- ENT --------------------------------------------------------------
            [
                'name' => 'Dr. Mizanur Rahman Khan', 'department' => 'ent-head-neck-surgery',
                'designation' => 'Senior Consultant & Head, ENT',
                'qualifications' => 'MBBS, MS (Otolaryngology), Fellowship in Otology',
                'speciality' => 'Otology & Cochlear Implant', 'gender' => 'male', 'experience' => 20, 'fee' => 1500,
                'expertise' => ['Cochlear implantation', 'Tympanoplasty', 'Endoscopic sinus surgery', 'Hearing loss'],
                'chamber' => 'Room 512, Level 5, Tower B', 'pattern' => 'evening_sat_wed', 'featured' => true,
                'about' => 'Dr. Khan leads the cochlear implant programme and the newborn hearing screening service, which now covers every baby delivered at the hospital.',
            ],
            [
                'name' => 'Dr. Sanjida Haque', 'department' => 'ent-head-neck-surgery',
                'designation' => 'Consultant, ENT',
                'qualifications' => 'MBBS, MS (Otolaryngology)',
                'speciality' => 'Rhinology & Paediatric ENT', 'gender' => 'female', 'experience' => 9, 'fee' => 1200,
                'expertise' => ['Sinus disease', 'Tonsil and adenoid surgery', 'Allergic rhinitis', 'Voice disorders'],
                'chamber' => 'Room 516, Level 5, Tower B', 'pattern' => 'alt_days_morning',
                'about' => 'Dr. Haque treats sinus and airway conditions in children and adults, and runs the voice and swallowing clinic.',
            ],

            // ---- Ophthalmology ----------------------------------------------------
            [
                'name' => 'Dr. Habibur Rahman Sohel', 'department' => 'ophthalmology',
                'designation' => 'Senior Consultant, Ophthalmology',
                'qualifications' => 'MBBS, DO, FCPS (Ophthalmology), Fellowship in Vitreoretinal Surgery',
                'speciality' => 'Retina & Cataract Surgery', 'gender' => 'male', 'experience' => 17, 'fee' => 1500,
                'expertise' => ['Phacoemulsification', 'Diabetic retinopathy', 'Retinal detachment', 'Intravitreal injection'],
                'chamber' => 'Room 220, Level 2, Tower B', 'pattern' => 'morning_sat_wed', 'featured' => true,
                'about' => 'Dr. Sohel performs day-case cataract surgery and vitreoretinal procedures, and set up the diabetic retinopathy screening pathway with the endocrinology department.',
            ],
            [
                'name' => 'Dr. Nadia Rahman', 'department' => 'ophthalmology',
                'designation' => 'Consultant, Paediatric Ophthalmology',
                'qualifications' => 'MBBS, FCPS (Ophthalmology), Fellowship in Paediatric Ophthalmology',
                'speciality' => 'Paediatric Eye Care & Squint', 'gender' => 'female', 'experience' => 10, 'fee' => 1200,
                'expertise' => ['Squint surgery', 'Amblyopia', 'Paediatric refraction', 'Retinopathy of prematurity'],
                'chamber' => 'Room 224, Level 2, Tower B', 'pattern' => 'alt_days_evening',
                'about' => 'Dr. Rahman manages childhood eye conditions including squint and lazy eye, and screens premature babies for retinopathy of prematurity.',
            ],

            // ---- Dermatology ------------------------------------------------------
            [
                'name' => 'Dr. Tasnim Rahman Oishi', 'department' => 'dermatology-aesthetics',
                'designation' => 'Consultant, Dermatology',
                'qualifications' => 'MBBS, DDV, MD (Dermatology)',
                'speciality' => 'Medical & Cosmetic Dermatology', 'gender' => 'female', 'experience' => 11, 'fee' => 1200,
                'expertise' => ['Acne and scarring', 'Psoriasis', 'Hair loss', 'Laser treatments'],
                'chamber' => 'Room 118, Level 1, Tower B', 'pattern' => 'split_shift', 'featured' => true,
                'about' => 'Dr. Oishi treats acne, psoriasis and hair loss, and runs the laser and aesthetics suite. She takes a conservative approach to cosmetic intervention.',
            ],
            [
                'name' => 'Dr. Rakibul Hasan', 'department' => 'dermatology-aesthetics',
                'designation' => 'Consultant, Dermatology',
                'qualifications' => 'MBBS, MD (Dermatology)',
                'speciality' => 'Dermato-Surgery & Allergy', 'gender' => 'male', 'experience' => 8, 'fee' => 1000,
                'expertise' => ['Skin allergy and patch testing', 'Vitiligo', 'Skin biopsy', 'Fungal infections'],
                'chamber' => 'Room 120, Level 1, Tower B', 'pattern' => 'alt_days_morning',
                'about' => 'Dr. Hasan runs the patch testing clinic for contact allergy and performs dermatological surgery and biopsies.',
            ],

            // ---- Internal Medicine ------------------------------------------------
            [
                'name' => 'Prof. Dr. Abdul Matin Sarker', 'department' => 'internal-medicine',
                'designation' => 'Senior Consultant & Head, Internal Medicine',
                'qualifications' => 'MBBS, FCPS (Medicine), FRCP (Glasgow)',
                'speciality' => 'General Internal Medicine', 'gender' => 'male', 'experience' => 30, 'fee' => 1500,
                'expertise' => ['Complex diagnosis', 'Hypertension', 'Geriatric medicine', 'Multi-morbidity'],
                'chamber' => 'Room 104, Level 1, Tower A', 'pattern' => 'evening_sat_wed', 'featured' => true,
                'about' => 'Professor Sarker has three decades of experience in general medicine and is often the physician colleagues refer to when a diagnosis does not fit a single specialty.',
            ],
            [
                'name' => 'Dr. Lamia Chowdhury', 'department' => 'internal-medicine',
                'designation' => 'Consultant, Rheumatology',
                'qualifications' => 'MBBS, MD (Rheumatology)',
                'speciality' => 'Rheumatology', 'gender' => 'female', 'experience' => 10, 'fee' => 1500,
                'expertise' => ['Rheumatoid arthritis', 'Lupus', 'Gout', 'Ankylosing spondylitis'],
                'chamber' => 'Room 108, Level 1, Tower A', 'pattern' => 'alt_days_morning',
                'about' => 'Dr. Chowdhury manages inflammatory arthritis and connective tissue disease, with an emphasis on early treatment to prevent joint damage.',
            ],
            [
                'name' => 'Dr. Naeem Uddin Bhuiyan', 'department' => 'internal-medicine',
                'designation' => 'Consultant, Infectious Disease',
                'qualifications' => 'MBBS, MD (Internal Medicine), Fellowship in Infectious Disease',
                'speciality' => 'Infectious Disease', 'gender' => 'male', 'experience' => 12, 'fee' => 1200,
                'expertise' => ['Dengue', 'Enteric fever', 'Antimicrobial stewardship', 'Fever of unknown origin'],
                'chamber' => 'Room 110, Level 1, Tower A', 'pattern' => 'weekend_heavy',
                'about' => 'Dr. Bhuiyan leads the antimicrobial stewardship programme and manages the seasonal dengue caseload, which peaks sharply during the monsoon.',
            ],

            // ---- Critical Care / Emergency ---------------------------------------
            [
                'name' => 'Dr. Shahriar Kabir', 'department' => 'critical-care-medicine',
                'designation' => 'Senior Consultant & Head, Critical Care',
                'qualifications' => 'MBBS, FCPS (Anaesthesiology), Fellowship in Critical Care Medicine',
                'speciality' => 'Intensive Care Medicine', 'gender' => 'male', 'experience' => 19, 'fee' => 2000,
                'expertise' => ['Mechanical ventilation', 'Sepsis', 'Multi-organ support', 'Rapid response'],
                'chamber' => 'Room 802, Level 8, Tower A', 'pattern' => 'alt_days_morning', 'online' => false,
                'about' => 'Dr. Kabir leads the intensivist-led ICU model and the in-hospital rapid response team. His outpatient availability is limited; ICU consultations are arranged through the ward team.',
            ],
            [
                'name' => 'Dr. Ruponti Das', 'department' => 'emergency-medicine',
                'designation' => 'Consultant, Emergency Medicine',
                'qualifications' => 'MBBS, MRCEM (UK)',
                'speciality' => 'Emergency Medicine', 'gender' => 'female', 'experience' => 9, 'fee' => 1000,
                'expertise' => ['Trauma resuscitation', 'Triage systems', 'Toxicology', 'Point-of-care ultrasound'],
                'chamber' => 'Emergency Department, Ground Floor', 'pattern' => 'weekend_heavy', 'online' => false,
                'about' => 'Dr. Das holds membership of the Royal College of Emergency Medicine and redesigned the department\'s triage protocol. The Emergency Department does not require an appointment — walk in at any hour.',
            ],
        ];
    }
}

<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->services() as $i => $data) {
            Service::updateOrCreate(
                ['slug' => Str::slug($data['name'])],
                [...$data, 'sort_order' => $i + 1, 'is_active' => true]
            );
        }
    }

    private function services(): array
    {
        return [
            [
                'name' => 'Emergency & Trauma Care', 'category' => 'clinical', 'icon' => 'ambulance',
                'is_247' => true, 'is_featured' => true,
                'summary' => 'Consultant-led emergency care, open every hour of every day, with triage within five minutes of arrival.',
                'description' => 'Our Emergency Department never closes. Every patient is triaged by a trained nurse within five minutes of walking through the door, and emergency physicians are physically present at all hours rather than on call from home. Resuscitation bays sit directly beside the CT scanner, and heart attack patients can be moved straight to the catheterisation laboratory without waiting for an inter-departmental referral.',
                'highlights' => ['Triage within 5 minutes', 'Emergency physicians on site 24/7', 'Resuscitation bays beside CT', 'Direct cath-lab activation'],
            ],
            [
                'name' => 'Ambulance Service', 'category' => 'support', 'icon' => 'ambulance',
                'is_247' => true, 'is_featured' => true,
                'summary' => 'Basic and advanced life-support ambulances with trained paramedics, dispatched across Dhaka around the clock.',
                'description' => 'Our fleet includes both basic and advanced life-support vehicles staffed by trained paramedics who begin treatment at the scene rather than simply transporting the patient. Advanced vehicles carry a defibrillator, ventilator and infusion pumps, and crews can transmit an ECG ahead of arrival so the receiving team is ready.',
                'highlights' => ['Advanced life-support fleet', 'Trained paramedic crews', 'Pre-arrival ECG transmission', 'Inter-hospital transfer service'],
            ],
            [
                'name' => 'Intensive Care Units', 'category' => 'clinical', 'icon' => 'activity',
                'is_247' => true, 'is_featured' => true,
                'summary' => 'Sixty-four critical care beds across general, cardiac, neuro and neonatal units, led by resident intensivists.',
                'description' => 'Critical care at RBR Hospital is organised as a single intensivist-led service spanning the general ICU, high-dependency unit, cardiac ICU, neuro ICU and neonatal ICU. Ventilated patients are nursed one-to-one. Families receive a structured daily update at a fixed time, so they are not left waiting in corridors for news.',
                'highlights' => ['64 critical care beds', 'Resident intensivists 24/7', '1:1 nursing for ventilated patients', 'Structured daily family briefing'],
            ],
            [
                'name' => 'Diagnostic Imaging', 'category' => 'diagnostic', 'icon' => 'scan',
                'is_247' => true, 'is_featured' => true,
                'summary' => '3T MRI, 128-slice CT, digital X-ray, ultrasound and mammography, with emergency reporting around the clock.',
                'description' => 'The radiology department runs 3 Tesla MRI, 128-slice CT, digital radiography, colour Doppler ultrasound and digital mammography. Emergency studies are reported by an on-site radiologist at any hour; routine reports are typically available within 24 hours and can be downloaded from the patient portal rather than collected in person.',
                'highlights' => ['3T MRI and 128-slice CT', '24/7 emergency reporting', 'Digital mammography', 'Online report download'],
            ],
            [
                'name' => 'Laboratory & Pathology', 'category' => 'diagnostic', 'icon' => 'microscope',
                'is_247' => true, 'is_featured' => true,
                'summary' => 'Fully automated laboratory covering biochemistry, haematology, microbiology, histopathology and molecular testing.',
                'description' => 'Our accredited laboratory processes samples on automated analysers with barcode tracking from collection to report, which removes most opportunities for mislabelling. Services include clinical biochemistry, haematology, microbiology and culture sensitivity, histopathology with immunohistochemistry, and molecular diagnostics including PCR.',
                'highlights' => ['Barcode sample tracking', 'Histopathology & immunohistochemistry', 'Molecular / PCR testing', 'Home sample collection'],
            ],
            [
                'name' => 'Operation Theatre Complex', 'category' => 'clinical', 'icon' => 'activity',
                'is_featured' => true,
                'summary' => 'Twelve modular theatres including laminar-flow suites for joint replacement and a hybrid cardiac theatre.',
                'description' => 'The theatre complex houses twelve modular operating rooms, of which three are laminar-flow suites reserved for joint replacement and implant surgery where infection risk carries the highest consequence. A hybrid cardiac theatre combines surgical facilities with catheterisation imaging, allowing combined procedures without moving the patient.',
                'highlights' => ['12 modular operating theatres', '3 laminar-flow suites', 'Hybrid cardiac theatre', 'Dedicated day-surgery unit'],
            ],
            [
                'name' => 'Pharmacy', 'category' => 'support', 'icon' => 'pill',
                'is_247' => true,
                'summary' => 'On-site pharmacy open 24 hours, stocking both branded and generic medicines with pharmacist counselling.',
                'description' => 'The hospital pharmacy operates around the clock and is staffed by registered pharmacists who check every discharge prescription for interactions and counsel patients on how to take their medication. Both branded and generic options are stocked, and the pharmacist will tell you the generic price so the choice is yours.',
                'highlights' => ['Open 24 hours', 'Pharmacist-led counselling', 'Generic options offered', 'Discharge medicine reconciliation'],
            ],
            [
                'name' => 'Physiotherapy & Rehabilitation', 'category' => 'clinical', 'icon' => 'activity',
                'summary' => 'Post-surgical, neurological and cardiac rehabilitation in a dedicated therapy gym.',
                'description' => 'Rehabilitation begins on the ward and continues in our therapy gym. Programmes cover post-operative orthopaedic recovery, stroke and neurological rehabilitation, cardiac rehabilitation after a heart attack or bypass surgery, and pulmonary rehabilitation for chronic lung disease. Home exercise programmes are written out so patients are not reliant on memory.',
                'highlights' => ['Dedicated therapy gym', 'Stroke rehabilitation', 'Cardiac rehabilitation programme', 'Written home exercise plans'],
            ],
            [
                'name' => 'Blood Bank', 'category' => 'support', 'icon' => 'droplet',
                'is_247' => true,
                'summary' => 'Licensed blood bank with component separation and full screening of every unit.',
                'description' => 'Our blood bank provides whole blood and separated components including packed red cells, platelets and fresh frozen plasma. Every unit is screened for hepatitis B, hepatitis C, HIV, syphilis and malaria before release. Voluntary donors are welcome during working hours and receive a free basic health check.',
                'highlights' => ['Component separation facility', 'Full screening on every unit', 'Voluntary donor programme', 'Emergency cross-match service'],
            ],
            [
                'name' => 'Health Screening & Check-up', 'category' => 'patient-care', 'icon' => 'check-circle',
                'is_featured' => true,
                'summary' => 'Structured executive and preventive health packages completed in a single half-day visit.',
                'description' => 'Our health check lounge is separate from the main outpatient area, so a preventive visit does not mean sitting among unwell patients. Packages are completed in one half-day visit with a consultant review at the end, and the report explains what each result means rather than simply listing numbers against reference ranges.',
                'highlights' => ['Separate health check lounge', 'Completed in one half-day', 'Consultant review included', 'Plain-language report'],
            ],
            [
                'name' => 'International Patient Services', 'category' => 'patient-care', 'icon' => 'globe',
                'summary' => 'Visa letters, airport transfer, interpreter support and a dedicated coordinator for overseas patients.',
                'description' => 'A dedicated coordinator handles the practical side of travelling for treatment: medical visa invitation letters, airport pickup, accommodation near the hospital, interpreter arrangement, treatment cost estimates in advance, and follow-up teleconsultation after the patient returns home.',
                'highlights' => ['Medical visa invitation letters', 'Airport pickup and accommodation', 'Interpreter support', 'Post-return teleconsultation'],
            ],
            [
                'name' => 'Patient Portal & Reports', 'category' => 'patient-care', 'icon' => 'file-text',
                'summary' => 'View appointments, download lab and imaging reports, and access prescriptions online.',
                'description' => 'Patients can view upcoming appointments, download laboratory and imaging reports as soon as they are verified, and retrieve past prescriptions and billing summaries without visiting the hospital. Access is tied to the mobile number used at registration.',
                'highlights' => ['Download reports online', 'Appointment history', 'Prescription archive', 'Billing summaries'],
            ],
        ];
    }
}

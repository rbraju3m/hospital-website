<?php

namespace Tests\Feature\Admin;

use App\Models\Appointment;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDoctorTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
        $this->department = Department::create(['name' => 'Cardiology', 'slug' => 'cardiology', 'icon' => 'heart-pulse']);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'department_id' => $this->department->id,
            'name' => 'Dr. Farhana Islam',
            'qualifications' => 'MBBS, FCPS (Cardiology)',
            'gender' => 'female',
            'consultation_fee' => 1500,
            'is_active' => '1',
            'accepts_online_appointment' => '1',
        ], $overrides);
    }

    private function doctor(array $overrides = []): Doctor
    {
        return Doctor::create(array_merge([
            'department_id' => $this->department->id,
            'name' => 'Dr. Farhana Islam',
            'slug' => 'dr-farhana-islam',
        ], $overrides));
    }

    public function test_a_doctor_is_created_with_translations(): void
    {
        $this->post(route('admin.doctors.store'), $this->payload([
            'translations' => ['bn' => ['name' => 'ডা. ফারহানা ইসলাম', 'speciality' => 'হৃদরোগ']],
        ]))->assertSessionHasNoErrors();

        $doctor = Doctor::firstWhere('slug', 'dr-farhana-islam');

        $this->assertSame('ডা. ফারহানা ইসলাম', $doctor->translation('name', 'bn'));
        // Post-nominals are deliberately not translatable, so there is nowhere
        // for a Bangla variant of them to be stored.
        $this->assertNotContains('qualifications', array_keys($doctor->translations['bn']));
    }

    public function test_chamber_hours_are_added_to_a_doctor(): void
    {
        $doctor = $this->doctor();

        $this->post(route('admin.doctors.schedules.store', $doctor), [
            'day_of_week' => 1,
            'start_time' => '17:00',
            'end_time' => '20:00',
            'slot_minutes' => 20,
            'capacity_per_slot' => 1,
            'location' => 'Level 4',
            'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('doctor_schedules', [
            'doctor_id' => $doctor->id,
            'day_of_week' => 1,
            'location' => 'Level 4',
        ]);
    }

    public function test_overlapping_chamber_hours_are_rejected(): void
    {
        $doctor = $this->doctor();
        $doctor->schedules()->create([
            'day_of_week' => 1, 'start_time' => '17:00', 'end_time' => '20:00',
            'slot_minutes' => 20, 'capacity_per_slot' => 1,
        ]);

        // Two windows over the same minutes would generate a slot twice, and the
        // unique index would then reject the second booking with no explanation.
        $this->post(route('admin.doctors.schedules.store', $doctor), [
            'day_of_week' => 1,
            'start_time' => '19:00',
            'end_time' => '21:00',
            'slot_minutes' => 20,
            'capacity_per_slot' => 1,
        ])->assertSessionHasErrors('start_time');

        $this->assertSame(1, $doctor->schedules()->count());
    }

    public function test_adjacent_chamber_hours_are_allowed(): void
    {
        $doctor = $this->doctor();
        $doctor->schedules()->create([
            'day_of_week' => 1, 'start_time' => '09:00', 'end_time' => '12:00',
            'slot_minutes' => 20, 'capacity_per_slot' => 1,
        ]);

        // Touching, not overlapping: a morning and an evening chamber is normal.
        $this->post(route('admin.doctors.schedules.store', $doctor), [
            'day_of_week' => 1,
            'start_time' => '12:00',
            'end_time' => '14:00',
            'slot_minutes' => 20,
            'capacity_per_slot' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertSame(2, $doctor->schedules()->count());
    }

    public function test_an_end_time_must_follow_its_start(): void
    {
        $doctor = $this->doctor();

        $this->post(route('admin.doctors.schedules.store', $doctor), [
            'day_of_week' => 2,
            'start_time' => '20:00',
            'end_time' => '17:00',
            'slot_minutes' => 20,
            'capacity_per_slot' => 1,
        ])->assertSessionHasErrors('end_time');
    }

    public function test_chamber_hours_belonging_to_another_doctor_are_not_reachable(): void
    {
        $doctor = $this->doctor();
        $other = $this->doctor(['name' => 'Dr. Other', 'slug' => 'dr-other']);

        $schedule = $other->schedules()->create([
            'day_of_week' => 3, 'start_time' => '10:00', 'end_time' => '12:00',
            'slot_minutes' => 20, 'capacity_per_slot' => 1,
        ]);

        $this->delete(route('admin.doctors.schedules.destroy', [$doctor, $schedule]))->assertNotFound();

        $this->assertModelExists($schedule);
    }

    public function test_chamber_hours_are_removed(): void
    {
        $doctor = $this->doctor();
        $schedule = $doctor->schedules()->create([
            'day_of_week' => 4, 'start_time' => '10:00', 'end_time' => '12:00',
            'slot_minutes' => 20, 'capacity_per_slot' => 1,
        ]);

        $this->delete(route('admin.doctors.schedules.destroy', [$doctor, $schedule]))
            ->assertSessionHas('status');

        $this->assertSame(0, DoctorSchedule::count());
    }

    public function test_a_doctor_with_appointments_cannot_be_deleted(): void
    {
        $doctor = $this->doctor();

        Appointment::create([
            'reference' => 'RBR000001',
            'doctor_id' => $doctor->id,
            'department_id' => $this->department->id,
            'patient_name' => 'Rahim Uddin',
            'phone' => '01712345678',
            'appointment_date' => now()->addDay()->toDateString(),
            'appointment_time' => '17:00:00',
        ]);

        // Deleting would cascade the appointment away with it, silently losing
        // the booking record.
        $this->delete(route('admin.doctors.destroy', $doctor))->assertSessionHas('warning');

        $this->assertModelExists($doctor);
        $this->assertSame(1, Appointment::count());
    }

    public function test_a_doctor_without_appointments_is_deleted(): void
    {
        $doctor = $this->doctor();

        $this->delete(route('admin.doctors.destroy', $doctor))
            ->assertRedirect(route('admin.doctors.index'));

        $this->assertModelMissing($doctor);
    }
}

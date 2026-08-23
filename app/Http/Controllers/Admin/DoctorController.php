<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HandlesTranslatableContent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DoctorRequest;
use App\Models\Department;
use App\Models\Doctor;
use App\Services\MediaLibrary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class DoctorController extends Controller
{
    use HandlesTranslatableContent;

    public function __construct(private readonly MediaLibrary $media) {}

    public function index(Request $request): View
    {
        $query = Doctor::with('department')
            ->withCount('schedules')
            ->search($request->string('q')->trim()->value())
            ->when($request->filled('department'), fn ($q) => $q->where('department_id', $request->integer('department')))
            ->when($request->filled('active'), fn ($q) => $q->where('is_active', $request->boolean('active')))
            ->ordered();

        return view('admin.doctors.index', [
            'doctors' => $this->paginateContent($query, $request),
            'departments' => Department::ordered()->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.doctors.form', [
            'doctor' => new Doctor([
                'is_active' => true,
                'accepts_online_appointment' => true,
                'gender' => 'male',
                'consultation_fee' => 0,
            ]),
            'departments' => Department::ordered()->get(),
        ]);
    }

    public function store(DoctorRequest $request): RedirectResponse
    {
        $doctor = $this->persist(new Doctor, $request);

        return redirect()->route('admin.doctors.edit', $doctor)
            ->with('status', __('admin.doctors.created', ['name' => $doctor->untranslated('name')]));
    }

    public function show(Doctor $doctor): RedirectResponse
    {
        return redirect()->route('admin.doctors.edit', $doctor);
    }

    public function edit(Doctor $doctor): View
    {
        return view('admin.doctors.form', [
            'doctor' => $doctor->load(['schedules' => fn ($q) => $q->orderBy('day_of_week')->orderBy('start_time')]),
            'departments' => Department::ordered()->get(),
        ]);
    }

    public function update(DoctorRequest $request, Doctor $doctor): RedirectResponse
    {
        $this->persist($doctor, $request);

        return back()->with('status', __('admin.doctors.updated', ['name' => $doctor->untranslated('name')]));
    }

    public function destroy(Doctor $doctor): RedirectResponse
    {
        // Appointments cascade with the doctor. Deleting a consultant who has
        // ever been booked would erase the booking history with them, so the
        // panel steers the operator to deactivation instead.
        if ($doctor->appointments()->exists()) {
            return back()->with('warning', __('admin.doctors.has_appointments'));
        }

        $this->media->delete($doctor->untranslated('photo'));
        $doctor->delete();

        return redirect()->route('admin.doctors.index')->with('status', __('admin.doctors.deleted'));
    }

    private function persist(Doctor $doctor, DoctorRequest $request): Doctor
    {
        $data = $request->validated();

        $data['slug'] = $this->uniqueSlug('doctors', $data['slug'] ?? null, $data['name'], $doctor->id);
        $data['sort_order'] ??= 0;
        $data['experience_years'] ??= 0;

        $photo = $this->media->replace(
            $request->file('photo'),
            'doctors',
            $doctor->untranslated('photo'),
            $request->boolean('photo_remove'),
        );

        $this->fillTranslatable($doctor, Arr::except($data, ['photo', 'photo_remove']));
        $doctor->photo = $photo;
        $doctor->save();

        return $doctor;
    }
}

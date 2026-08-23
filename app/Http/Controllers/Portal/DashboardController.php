<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\PatientDocument;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $patient = auth('patient')->user();
        $today = Carbon::today();

        return view('portal.dashboard', [
            'patient' => $patient,
            'upcoming' => $patient->appointments()
                ->whereDate('appointment_date', '>=', $today)
                ->whereNot('status', 'cancelled')
                ->orderBy('appointment_date')
                ->orderBy('appointment_time')
                ->take(5)
                ->get(),
            'recent' => $patient->documents()
                ->latestFirst()
                ->take(5)
                ->get(),
            'counts' => [
                'appointments' => $patient->appointments()->count(),
                'documents' => PatientDocument::forPhone($patient->phone)->count(),
            ],
        ]);
    }
}

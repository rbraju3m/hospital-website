<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    public function index(): View
    {
        $patient = auth('patient')->user();
        $today = Carbon::today();

        return view('portal.appointments', [
            'upcoming' => $patient->appointments()
                ->whereDate('appointment_date', '>=', $today)
                ->whereNot('status', 'cancelled')
                ->orderBy('appointment_date')
                ->orderBy('appointment_time')
                ->get(),
            'past' => $patient->appointments()
                ->where(fn ($query) => $query
                    ->whereDate('appointment_date', '<', $today)
                    ->orWhere('status', 'cancelled'))
                ->orderByDesc('appointment_date')
                ->orderByDesc('appointment_time')
                ->paginate(20),
        ]);
    }
}

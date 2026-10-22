<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ClinicStats;
use Illuminate\Http\Request;

/**
 * Panel general del consultorio (/clinica). Solo la cuenta principal
 * (middleware 'clinic'). Indicadores reales: App\Services\ClinicStats.
 */
class ClinicDashboardController extends Controller
{
    public function index(Request $request)
    {
        $clinic = $request->user();
        $stats = new ClinicStats($clinic);

        return view('clinic.dashboard', [
            'clinic' => $clinic,
            'totals' => $stats->totals(),
            'nutritionists' => $stats->nutritionists(),
            'statuses' => $stats->appointmentStatusesThisMonth(),
            'todayAppointments' => $stats->todayAppointments(),
            'upcomingAppointments' => $stats->upcomingAppointments(),
            'activity' => $stats->recentActivity(),
            'licenseActive' => $clinic->clinicLicenseActive(),
            'memberLimit' => User::clinicMemberLimit(),
        ]);
    }
}

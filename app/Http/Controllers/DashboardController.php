<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Subscription;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $totalPatients = Patient::where('user_id', Auth::id())
            ->count();

        $activePatients = Patient::where('user_id', Auth::id())
            ->where('active', true)
            ->count();

        // ==========================================
        // PRUEBA GRATUITA DE 7 DÍAS
        // ==========================================
        // Antes se leía de users.subscription_status / users.trial_ends_at.
        // En la nueva BD la prueba es una fila de `subscriptions` con
        // status = "en_prueba".

        // Mismo cálculo que el indicador de la barra superior
        // (Subscription::panelStatusFor). null = ya contrató un plan.
        $trial = Subscription::panelStatusFor(Auth::user());

        $isOnTrial = $trial !== null;
        $isTrialActive = ($trial['state'] ?? null) === 'trial';
        $trialDaysRemaining = $trial['days'] ?? null;
        $trialEndsAt = $trial['ends_at'] ?? null;

        return view('dashboard.index', compact(
            'totalPatients',
            'activePatients',
            'isOnTrial',
            'trialDaysRemaining',
            'isTrialActive',
            'trialEndsAt'
        ));
    }
}

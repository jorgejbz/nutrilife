<?php

namespace App\Http\Controllers;

use App\Models\Patient;
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

        $subscription = Auth::user()?->latestSubscription;

        $isOnTrial = $subscription?->isTrial() ?? false;
        $trialDaysRemaining = null;
        $isTrialActive = false;

        if ($isOnTrial && $subscription->current_period_ends_at) {
            $trialDaysRemaining = (int) now()
                ->startOfDay()
                ->diffInDays(
                    $subscription->current_period_ends_at->copy()->startOfDay(),
                    false
                );

            $isTrialActive = $trialDaysRemaining >= 0;
        }

        return view('dashboard.index', compact(
            'totalPatients',
            'activePatients',
            'isOnTrial',
            'trialDaysRemaining',
            'isTrialActive'
        ));
    }
}

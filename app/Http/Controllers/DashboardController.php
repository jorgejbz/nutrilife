<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Services\DashboardStats;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // Indicadores reales del nutriólogo (ver App\Services\DashboardStats).
        // ?meses=6|12 cambia el periodo de las gráficas mensuales.
        $stats = new DashboardStats($user, $request->integer('meses', 6));
        $data = $stats->all(withDietPlans: Gate::allows('diet-plans'));

        // Prueba gratuita: mismo cálculo que el indicador de la barra superior
        // (Subscription::panelStatusFor). null = ya contrató un plan.
        $trial = Subscription::panelStatusFor($user);

        return view('dashboard.index', $data + [
            'months' => $stats->months(),
            'periods' => DashboardStats::PERIODS,
            'isOnTrial' => $trial !== null,
            'isTrialActive' => ($trial['state'] ?? null) === 'trial',
            'trialDaysRemaining' => $trial['days'] ?? null,
            'trialEndsAt' => $trial['ends_at'] ?? null,
        ]);
    }
}

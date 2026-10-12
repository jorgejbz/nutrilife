<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user()->load('role');

        // ---------- Suscripción ----------
        // La más reciente es la vigente (en_prueba / activa / vencida / cancelada).
        $subscription = $user->latestSubscription?->load('plan');

        $subscriptionIds = $user->subscriptions()->pluck('id');

        // Cobros de la plataforma (subscription_payments) de todo su historial.
        $subscriptionPayments = SubscriptionPayment::whereIn('subscription_id', $subscriptionIds)
            ->with('subscription.plan')
            ->orderByDesc('paid_at')
            ->get();

        // Cliente desde: inicio de la primera suscripción que tuvo un pago.
        $customerSince = $user->subscriptions()
            ->whereHas('payments')
            ->orderBy('starts_at')
            ->value('starts_at');

        // ---------- Uso del plan ----------
        $patientsCount = $user->patients()->count();
        $maxPatients = $subscription?->plan?->max_patients;

        // ---------- Resumen de su consulta ----------
        $stats = [
            'patients' => $patientsCount,
            'active_patients' => $user->patients()->where('active', true)->count(),
            'upcoming_appointments' => $user->appointments()
                ->where('appointment_at', '>=', now())
                ->whereIn('status', ['Pendiente', 'Confirmada'])
                ->count(),
            'consultations_month' => $user->consultations()
                ->whereYear('consultation_at', now()->year)
                ->whereMonth('consultation_at', now()->month)
                ->count(),
        ];

        return view('profile.edit', [
            'user' => $user,
            'subscription' => $subscription,
            'accountType' => Subscription::accountTypeFor($user),
            'trialStatus' => Subscription::panelStatusFor($user),
            'customerSince' => $customerSince ? Carbon::parse($customerSince) : null,
            'subscriptionPayments' => $subscriptionPayments,
            'totalPaid' => $subscriptionPayments->where('status', 'pagado')->sum('amount'),
            'patientsCount' => $patientsCount,
            'maxPatients' => $maxPatients,
            'planFeatures' => config('nutrilife.plan_features.'.($subscription?->plan?->name ?? ''), []),
            'stats' => $stats,
            // Para avisar antes de intentar eliminar la cuenta
            'canDeleteAccount' => ! $user->hasClinicalData(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ], [
            'password.required' => 'Escribe tu contraseña para confirmar.',
            'password.current_password' => 'La contraseña no es correcta.',
        ]);

        $user = $request->user();

        // Nueva BD: los datos clínicos referencian a users sin borrado en
        // cascada; ver User::hasClinicalData().
        $hasClinicalData = $user->hasClinicalData();

        if ($hasClinicalData) {
            return Redirect::route('profile.edit')->withErrors([
                'password' => 'No es posible eliminar la cuenta porque tiene pacientes, citas, consultas o pagos registrados.',
            ], 'userDeletion');
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}

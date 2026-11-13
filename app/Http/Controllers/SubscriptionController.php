<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\PlanCheckout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Pantalla de contratación de paquetes (subscription_plans) y compra
 * simulada (App\Services\PlanCheckout).
 *
 * Es la pantalla a la que EnsureTrialSubscription manda a quien ya no tiene
 * prueba ni paquete vigente; también se puede abrir para cambiar de paquete.
 */
class SubscriptionController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        // Los subperfiles de clínica no contratan: usan la licencia de su consultorio.
        abort_unless($user->isNutritionist() && ! $user->isClinicMember(), 403, 'Tu clínica administra la suscripción de tu cuenta.');

        $subscription = $user->latestSubscription?->loadMissing('plan');

        $plans = SubscriptionPlan::where('active', true)->orderBy('price')->get();

        return view('subscription.plans', [
            'plans' => $plans,
            'subscription' => $subscription,
            'hasAccess' => (bool) $subscription?->grantsPanelAccess(),
            'trialEnded' => $subscription && ! $subscription->grantsPanelAccess() && ! $subscription->payments()->exists(),
            'currentPlanId' => $subscription && $subscription->hasPaidAccess() && $subscription->status === Subscription::STATUS_ACTIVE
                ? (int) $subscription->subscription_plan_id
                : null,
            'planFeatures' => config('nutrilife.plan_features', []),
            'featurePlans' => collect(config('nutrilife.features', []))
                ->map(fn ($feature) => $feature['plans'] ?? []),
            'checkoutEnabled' => PlanCheckout::isEnabled(),
            'patientCount' => $user->patients()->count(),
            // Prueba en curso: qué paquete se está probando y a cuáles se puede cambiar
            'trialActive' => (bool) ($subscription?->isTrial() && ! $subscription->trialHasExpired()),
            'trialPlanId' => $subscription?->isTrial() ? (int) $subscription->subscription_plan_id : null,
            'trialPlanNames' => Subscription::trialPlanNames(),
        ]);
    }

    /**
     * Cambia el paquete que se está probando (Esencial ↔ Pro) durante la
     * prueba gratuita. Las fechas no cambian: la prueba vence el mismo día.
     */
    public function switchTrial(SubscriptionPlan $subscriptionPlan)
    {
        $user = Auth::user();
        abort_unless($user->isNutritionist() && ! $user->isClinicMember(), 403, 'Tu clínica administra la suscripción de tu cuenta.');

        $subscription = $user->latestSubscription;

        if (! $subscription || ! $subscription->switchTrialPlan($subscriptionPlan)) {
            return redirect()
                ->route('subscription.plans')
                ->withErrors(['trial' => 'Solo puedes cambiar el paquete de prueba mientras tu prueba gratuita siga vigente, entre '.implode(' y ', Subscription::trialPlanNames()).'.']);
        }

        return redirect()
            ->route('dashboard.index')
            ->with('success', 'Listo: ahora estás probando el paquete '.$subscriptionPlan->name.'. Tu prueba sigue terminando el '
                .$subscription->trialEndsAt()->format('d/m/Y').'.');
    }

    public function checkout(Request $request, SubscriptionPlan $subscriptionPlan)
    {
        $user = Auth::user();
        // Los subperfiles de clínica no contratan: usan la licencia de su consultorio.
        abort_unless($user->isNutritionist() && ! $user->isClinicMember(), 403, 'Tu clínica administra la suscripción de tu cuenta.');

        // El precio y el periodo salen de la BD, nunca de la petición.
        $subscription = PlanCheckout::purchase($user, $subscriptionPlan);

        return redirect()
            ->route('dashboard.index')
            ->with('success', 'Listo: tu paquete '.$subscriptionPlan->name.' está activo hasta el '
                .$subscription->current_period_ends_at->format('d/m/Y').'. (Pago simulado: no se realizó ningún cargo.)');
    }
}

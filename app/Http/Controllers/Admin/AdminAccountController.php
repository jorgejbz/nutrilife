<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\AccountActivation;
use App\Services\AdminAccounts;
use App\Services\PlanCheckout;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Cuentas de la plataforma (/admin/cuentas). Middleware 'admin'.
 *
 * Acciones permitidas (todas con confirmación en la interfaz, validadas
 * aquí y registradas en audit_logs):
 *  - activar ya una cuenta pendiente, desactivar y reactivar;
 *  - asignar plan a una cuenta principal (PlanCheckout::assignByAdmin).
 * Nunca se eliminan cuentas ni pacientes desde aquí. Las cuentas de
 * administrador no se modifican desde el panel (se gestionan con
 * `php artisan nutrilife:create-admin`) y nadie se cambia a sí mismo.
 */
class AdminAccountController extends Controller
{
    public function index(Request $request)
    {
        $type = array_key_exists((string) $request->query('tipo'), AdminAccounts::TYPE_FILTERS) ? $request->query('tipo') : null;
        $plan = array_key_exists((string) $request->query('plan'), AdminAccounts::PLAN_FILTERS) ? $request->query('plan') : null;
        $status = array_key_exists((string) $request->query('estado'), User::ACCOUNT_STATUS_LABELS) ? $request->query('estado') : null;
        $search = trim((string) $request->query('q'));

        $accounts = User::query()
            ->with(['latestSubscription.plan', 'latestSubscription.payments', 'clinic:id,name'])
            ->withCount(['patients', 'clinicMembers'])
            ->when($type, fn ($query) => AdminAccounts::applyTypeFilter($query, $type))
            ->when($plan, fn ($query) => AdminAccounts::applyPlanFilter($query, $plan))
            ->when($status, fn ($query) => $query->where('account_status', $status))
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.$search.'%';

                $query->where(fn ($q) => $q
                    ->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('professional_license', 'like', $like)
                    ->orWhereHas('clinic', fn ($clinic) => $clinic->where('name', 'like', $like)));
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.accounts.index', [
            'accounts' => $accounts,
            'filters' => ['tipo' => $type, 'plan' => $plan, 'estado' => $status, 'q' => $search],
            'typeFilters' => AdminAccounts::TYPE_FILTERS,
            'planFilters' => AdminAccounts::PLAN_FILTERS,
            'statusFilters' => User::ACCOUNT_STATUS_LABELS,
        ]);
    }

    public function show(User $account)
    {
        $account->load(['role', 'clinic', 'latestSubscription.plan'])
            ->loadCount(['patients', 'clinicMembers']);

        $members = $account->clinicMembers()
            ->withCount([
                'patients',
                'appointments as upcoming_appointments_count' => fn ($query) => $query
                    ->where('appointment_at', '>=', now())
                    ->whereIn('status', ['Pendiente', 'Confirmada']),
            ])
            ->orderBy('name')
            ->get();

        // Organización completa (cuenta + subperfiles) para los totales de clínica.
        $orgIds = $account->isClinic() ? $account->clinicNutritionistIds() : [$account->id];

        $appointmentStatuses = Appointment::whereIn('user_id', $orgIds)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $subscriptions = $account->subscriptions()->with(['plan', 'payments'])->latest('id')->get();

        return view('admin.accounts.show', [
            'account' => $account,
            'type' => AdminAccounts::typeOf($account),
            'accountType' => \App\Models\Subscription::accountTypeFor($account),
            'members' => $members,
            'orgPatients' => Patient::whereIn('user_id', $orgIds)->count(),
            'appointmentStatuses' => collect(AppointmentController::STATUSES)->mapWithKeys(fn ($s) => [$s => (int) ($appointmentStatuses[$s] ?? 0)]),
            'upcomingAppointments' => Appointment::whereIn('user_id', $orgIds)->where('appointment_at', '>=', now())->whereIn('status', ['Pendiente', 'Confirmada'])->count(),
            'subscriptions' => $subscriptions,
            'subscriptionPayments' => SubscriptionPayment::whereIn('subscription_id', $subscriptions->pluck('id'))->with(['subscription.plan', 'statusChangedBy'])->latest('paid_at')->get(),
            'plans' => SubscriptionPlan::where('active', true)->orderBy('price')->get(),
            'history' => AuditLog::where('subject_type', 'User')->where('subject_id', $account->id)->with('user')->latest('id')->limit(15)->get(),
            'canManage' => $this->canManage($account),
        ]);
    }

    /** activar (pendiente → activa ya) | desactivar | reactivar */
    public function updateStatus(Request $request, User $account)
    {
        abort_unless($this->canManage($account), 403, 'Esta cuenta no se puede modificar desde el panel.');

        $validated = $request->validate([
            'action' => ['required', Rule::in(['activar', 'desactivar', 'reactivar'])],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $previous = $account->account_status;

        switch ($validated['action']) {
            case 'activar':
                if (! $account->isPendingActivation()) {
                    return back()->with('error', 'La cuenta no está pendiente de activación.');
                }
                AccountActivation::activate($account);
                $action = 'account.activated';
                $message = 'Cuenta activada.';
                break;

            case 'desactivar':
                if ($account->account_status === User::ACCOUNT_DISABLED) {
                    return back()->with('error', 'La cuenta ya está desactivada.');
                }
                AccountActivation::deactivate($account);
                $action = 'account.deactivated';
                $message = 'Cuenta desactivada. Ya no puede iniciar sesión'.($account->clinicMembers()->exists() ? ' (tampoco sus subperfiles)' : '').'; su información se conserva.';
                break;

            default:
                if ($account->account_status !== User::ACCOUNT_DISABLED) {
                    return back()->with('error', 'La cuenta no está desactivada.');
                }
                AccountActivation::reactivate($account);
                $action = 'account.reactivated';
                $message = 'Cuenta reactivada.';
        }

        AuditLog::record($request->user(), $action, $account, array_filter([
            'from' => $previous,
            'to' => $account->fresh()->account_status,
            'reason' => $validated['reason'] ?? null,
        ]));

        return back()->with('success', $message);
    }

    public function assignPlan(Request $request, User $account)
    {
        abort_unless($this->canManage($account) && ! $account->isClinicMember(), 403, 'Solo las cuentas principales tienen plan propio.');

        $validated = $request->validate([
            'subscription_plan_id' => ['required', 'integer', Rule::exists('subscription_plans', 'id')->where('active', true)],
            'months' => ['required', 'integer', 'min:1', 'max:24'],
            'payment' => ['required', Rule::in([PlanCheckout::ADMIN_PAYMENT_NONE, PlanCheckout::ADMIN_PAYMENT_PENDING, PlanCheckout::ADMIN_PAYMENT_PAID])],
            'reason' => ['nullable', 'string', 'max:500'],
        ], [
            'subscription_plan_id.exists' => 'Selecciona un plan activo.',
        ]);

        $plan = SubscriptionPlan::findOrFail($validated['subscription_plan_id']);
        $previous = $account->latestSubscription?->plan?->name;

        $subscription = PlanCheckout::assignByAdmin($account, $plan, $request->user(), (int) $validated['months'], $validated['payment']);

        AuditLog::record($request->user(), 'account.plan_assigned', $account, array_filter([
            'from' => $previous,
            'to' => $plan->name,
            'until' => $subscription->current_period_ends_at->toDateString(),
            'payment' => $validated['payment'],
            'reason' => $validated['reason'] ?? null,
        ]));

        return back()->with('success', 'Plan '.$plan->name.' asignado hasta el '.$subscription->current_period_ends_at->format('d/m/Y').'.');
    }

    /** Solo cuentas de nutriólogo y nunca la propia. */
    private function canManage(User $account): bool
    {
        return $account->isNutritionist() && $account->id !== auth()->id();
    }
}

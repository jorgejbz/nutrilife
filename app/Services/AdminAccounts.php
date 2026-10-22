<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Clasificación de cuentas e indicadores del administrador global.
 *
 * "Cuenta principal" = nutriólogo sin clinic_id (individual o clínica). Los
 * subperfiles de clínica se cuentan APARTE para no inflar las cuentas
 * principales, y el administrador no cuenta como cliente.
 *
 * El plan de una cuenta es el de su suscripción más reciente
 * (User::latestSubscription), con la misma regla de vigencia que usa el
 * panel (Subscription::hasPaidAccess / periodo de prueba).
 */
class AdminAccounts
{
    /** Filtro "plan" del listado → texto. */
    public const PLAN_FILTERS = [
        'prueba' => 'Prueba gratuita',
        'Esencial' => 'Esencial',
        'Pro' => 'Pro',
        'Consultorio' => 'Consultorio',
        'vencida' => 'Vencida / sin plan vigente',
    ];

    /** Filtro "tipo" del listado → texto. */
    public const TYPE_FILTERS = [
        'principal' => 'Cuentas principales',
        'clinica' => 'Clínicas (Consultorio)',
        'subperfil' => 'Subperfiles de clínica',
        'admin' => 'Administradores',
    ];

    /** Cuentas principales de nutriólogo (individuales y clínicas). */
    public static function mainAccounts(): Builder
    {
        return User::query()->where('role_id', Role::NUTRITIONIST)->whereNull('clinic_id');
    }

    /** Suscripción con periodo pagado vigente (activa o cancelada sin terminar). */
    private static function paidScope(Builder $subscription): Builder
    {
        return $subscription
            ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_CANCELLED])
            ->whereDate('current_period_ends_at', '>=', today());
    }

    /** Aplica el filtro de plan sobre una consulta de `users`. */
    public static function applyPlanFilter(Builder $users, string $plan): Builder
    {
        return match ($plan) {
            'prueba' => $users->whereHas('latestSubscription', fn ($s) => $s->where('status', Subscription::STATUS_TRIAL)),
            'vencida' => $users->whereNull('clinic_id')->where('role_id', Role::NUTRITIONIST)
                ->where(fn ($q) => $q
                    ->whereDoesntHave('latestSubscription')
                    ->orWhereHas('latestSubscription', fn ($s) => $s->where(fn ($s) => $s
                        ->where('status', Subscription::STATUS_EXPIRED)
                        ->orWhere(fn ($s) => $s
                            ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_CANCELLED])
                            ->whereDate('current_period_ends_at', '<', today()))))),
            default => $users->whereHas('latestSubscription', fn ($s) => self::paidScope($s)
                ->whereHas('plan', fn ($p) => $p->where('name', $plan))),
        };
    }

    /** Aplica el filtro de tipo de cuenta. */
    public static function applyTypeFilter(Builder $users, string $type): Builder
    {
        return match ($type) {
            'principal' => $users->where('role_id', Role::NUTRITIONIST)->whereNull('clinic_id'),
            'clinica' => $users->where('role_id', Role::NUTRITIONIST)->whereNull('clinic_id')
                ->where(fn ($q) => $q
                    ->whereHas('clinicMembers')
                    ->orWhereHas('latestSubscription.plan', fn ($p) => $p->where('name', config('nutrilife.clinic.plan')))),
            'subperfil' => $users->whereNotNull('clinic_id'),
            'admin' => $users->where('role_id', Role::ADMIN),
            default => $users,
        };
    }

    /**
     * Indicadores del panel. Todo sale de la BD.
     *
     * @return array<string, int|float>
     */
    public static function totals(): array
    {
        $count = fn (string $plan) => self::applyPlanFilter(self::mainAccounts(), $plan)->count();

        return [
            'main_accounts' => self::mainAccounts()->count(),
            'trial' => $count('prueba'),
            'esencial' => $count('Esencial'),
            'pro' => $count('Pro'),
            'consultorio' => $count('Consultorio'),
            'expired' => $count('vencida'),
            'members' => User::where('role_id', Role::NUTRITIONIST)->whereNotNull('clinic_id')->count(),
            'admins' => User::where('role_id', Role::ADMIN)->count(),
            'patients' => Patient::count(),
            'pending_activation' => User::where('account_status', User::ACCOUNT_PENDING)->count(),
            'disabled' => User::where('account_status', User::ACCOUNT_DISABLED)->count(),
            'subscription_payments_paid' => SubscriptionPayment::where('status', SubscriptionPayment::STATUS_PAID)->count(),
            'subscription_payments_pending' => SubscriptionPayment::where('status', SubscriptionPayment::STATUS_PENDING)->count(),
            'subscription_revenue' => (float) SubscriptionPayment::where('status', SubscriptionPayment::STATUS_PAID)->sum('amount'),
            'patient_payments_paid' => Payment::where('status', Payment::STATUS_PAID)->count(),
            'patient_payments_pending' => Payment::where('status', Payment::STATUS_PENDING)->count(),
        ];
    }

    /**
     * Cómo se presenta el tipo de cuenta en las tablas del administrador.
     *
     * @return array{key: string, label: string}
     */
    public static function typeOf(User $user): array
    {
        return match (true) {
            $user->isAdmin() => ['key' => 'admin', 'label' => 'Administrador'],
            $user->isClinicMember() => ['key' => 'member', 'label' => 'Subperfil'],
            $user->isClinic() => ['key' => 'clinic', 'label' => 'Clínica'],
            default => ['key' => 'activa', 'label' => 'Nutriólogo'],
        };
    }
}

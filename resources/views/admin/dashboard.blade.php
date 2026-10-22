@extends('adminlte::page')

{{-- =========================================================
     PANEL DEL ADMINISTRADOR GLOBAL (/admin)
     Indicadores de toda la plataforma (App\Services\AdminAccounts).
     Los subperfiles se cuentan aparte de las cuentas principales.
     ========================================================= --}}

@section('title', 'Administración | NutriLife')

@section('content_header')
    <div class="patients-header">
        <div>
            <span class="header-eyebrow">
                <i class="fas fa-leaf me-1"></i>
                NUTRILIFE · ADMINISTRACIÓN GLOBAL
            </span>
            <h1>Panel general</h1>
            <p>Cuentas, planes, pacientes y cobros de toda la plataforma.</p>
        </div>
        <div class="nl-page-actions">
            <a href="{{ route('admin.accounts.index') }}" class="nl-btn nl-btn--primary">
                <i class="fas fa-users-gear" aria-hidden="true"></i> Ver cuentas
            </a>
        </div>
    </div>
@stop

@section('content')
<div class="nl-profile">

    @include('partials.flash')

    <div class="nl-kpis">
        <a href="{{ route('admin.accounts.index', ['tipo' => 'principal']) }}" class="nl-kpi">
            <i class="fas fa-user-doctor" aria-hidden="true"></i>
            <strong>{{ $totals['main_accounts'] }}</strong>
            <span>Cuentas registradas</span>
            <small>Cuentas principales (sin subperfiles ni administradores)</small>
        </a>
        <a href="{{ route('admin.accounts.index', ['tipo' => 'subperfil']) }}" class="nl-kpi is-info">
            <i class="fas fa-hospital-user" aria-hidden="true"></i>
            <strong>{{ $totals['members'] }}</strong>
            <span>Subperfiles de nutriólogo</span>
            <small>Dentro de clínicas Consultorio</small>
        </a>
        <div class="nl-kpi">
            <i class="fas fa-people-group" aria-hidden="true"></i>
            <strong>{{ $totals['patients'] }}</strong>
            <span>Pacientes registrados</span>
            <small>Expedientes vigentes en la plataforma</small>
        </div>
        <a href="{{ route('admin.accounts.index', ['estado' => 'pendiente']) }}" class="nl-kpi is-warn">
            <i class="fas fa-user-clock" aria-hidden="true"></i>
            <strong>{{ $totals['pending_activation'] }}</strong>
            <span>Pendientes de activación</span>
            <small>{{ $totals['disabled'] }} desactivadas</small>
        </a>
    </div>

    <section class="nl-profile-card">
        <header class="nl-section-head">
            <div>
                <span class="nl-eyebrow">Cuentas principales por plan vigente</span>
                <h2>Planes</h2>
            </div>
        </header>
        <div class="nl-kpis">
            @foreach ([
                ['prueba', 'Prueba gratuita', $totals['trial'], 'fa-seedling', 'is-warn'],
                ['Esencial', 'Esencial', $totals['esencial'], 'fa-leaf', ''],
                ['Pro', 'Pro', $totals['pro'], 'fa-crown', ''],
                ['Consultorio', 'Consultorio', $totals['consultorio'], 'fa-hospital', 'is-info'],
            ] as [$filter, $label, $value, $icon, $class])
                <a href="{{ route('admin.accounts.index', ['plan' => $filter]) }}" class="nl-kpi {{ $class }}">
                    <i class="fas {{ $icon }}" aria-hidden="true"></i>
                    <strong>{{ $value }}</strong>
                    <span>{{ $label }}</span>
                </a>
            @endforeach
        </div>
        <p class="nl-field-hint nl-mt-12">
            {{ $totals['expired'] }} cuentas sin plan vigente (prueba o suscripción vencida).
            <a href="{{ route('admin.accounts.index', ['plan' => 'vencida']) }}">Ver</a>
        </p>
    </section>

    <section class="nl-profile-card">
        <header class="nl-section-head">
            <div>
                <span class="nl-eyebrow">Cobros</span>
                <h2>Pagos</h2>
            </div>
            <a href="{{ route('admin.payments.index') }}" class="nl-btn-ghost">Cobros de la plataforma <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
        </header>
        <div class="nl-kpis">
            <a href="{{ route('admin.payments.index', ['estado' => 'pagado']) }}" class="nl-kpi">
                <i class="fas fa-file-invoice-dollar" aria-hidden="true"></i>
                <strong>{{ $totals['subscription_payments_paid'] }}</strong>
                <span>Cobros de suscripción pagados</span>
                <small>${{ number_format($totals['subscription_revenue'], 2) }} MXN</small>
            </a>
            <a href="{{ route('admin.payments.index', ['estado' => 'pendiente']) }}" class="nl-kpi is-warn">
                <i class="fas fa-hourglass-half" aria-hidden="true"></i>
                <strong>{{ $totals['subscription_payments_pending'] }}</strong>
                <span>Cobros de suscripción pendientes</span>
                <small>Solo el administrador los confirma</small>
            </a>
            <div class="nl-kpi">
                <i class="fas fa-wallet" aria-hidden="true"></i>
                <strong>{{ $totals['patient_payments_paid'] }}</strong>
                <span>Pagos de pacientes completados</span>
                <small>Registrados por los nutriólogos</small>
            </div>
            <div class="nl-kpi is-warn">
                <i class="far fa-clock" aria-hidden="true"></i>
                <strong>{{ $totals['patient_payments_pending'] }}</strong>
                <span>Pagos de pacientes pendientes</span>
            </div>
        </div>
    </section>

    <div class="nl-split">
        <section class="nl-profile-card">
            <header class="nl-section-head">
                <div>
                    <span class="nl-eyebrow">Altas</span>
                    <h2>Cuentas recientes</h2>
                </div>
            </header>
            <div class="table-responsive">
                <table class="nl-table">
                    <thead>
                        <tr>
                            <th scope="col">Cuenta</th>
                            <th scope="col">Tipo / plan</th>
                            <th scope="col">Registro</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentAccounts as $account)
                            @php $accountType = \App\Models\Subscription::accountTypeFor($account); @endphp
                            <tr>
                                <td data-label="Cuenta">
                                    <a href="{{ route('admin.accounts.show', $account) }}"><strong>{{ $account->name }}</strong></a>
                                    <small>{{ $account->email }}</small>
                                </td>
                                <td data-label="Tipo / plan">
                                    {{ \App\Services\AdminAccounts::typeOf($account)['label'] }}
                                    <small>{{ $accountType['label'] ?? 'Sin suscripción' }}</small>
                                </td>
                                <td data-label="Registro">{{ $account->created_at?->format('d/m/Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="nl-profile-card">
            <header class="nl-section-head">
                <div>
                    <span class="nl-eyebrow">Activación</span>
                    <h2>Cuentas pendientes</h2>
                </div>
            </header>
            @if ($pendingAccounts->isEmpty())
                <p class="nl-empty"><i class="fas fa-circle-check" aria-hidden="true"></i>No hay cuentas pendientes.</p>
            @else
                <ul class="nl-list-plain">
                    @foreach ($pendingAccounts as $account)
                        <li>
                            <div>
                                <a href="{{ route('admin.accounts.show', $account) }}"><strong>{{ $account->name }}</strong></a>
                                <small>{{ $account->email }}</small>
                            </div>
                            <small>Se activa: {{ $account->activates_at?->format('d/m/Y H:i') ?? 'sin fecha' }}</small>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($recentAudit->isNotEmpty())
                <header class="nl-section-head nl-mt-22">
                    <div>
                        <span class="nl-eyebrow">Bitácora</span>
                        <h2>Últimas acciones</h2>
                    </div>
                    <a href="{{ route('admin.audit.index') }}" class="nl-btn-ghost">Ver todo <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                </header>
                <ul class="nl-list-plain">
                    @foreach ($recentAudit as $log)
                        <li>
                            <div><strong>{{ $log->actionLabel() }}</strong><small>{{ $log->user?->name ?? 'Sistema' }}</small></div>
                            <small>{{ $log->created_at?->diffForHumans() }}</small>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

</div>
@stop

@section('css')
    @include('partials.theme-styles', ['section' => 'admin'])
@stop

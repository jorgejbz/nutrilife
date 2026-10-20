@extends('adminlte::page')

{{-- =========================================================
     MI PERFIL
     Datos del nutriólogo, tipo de cuenta (plan), suscripción,
     uso del plan, cobros de la plataforma y resumen de su consulta.
     Debajo, los formularios de Breeze para editar la cuenta.
     Datos: ProfileController@edit. Estilos: admin.css → "4. PERFIL".
     ========================================================= --}}

@section('title', 'Mi perfil | NutriLife')

@php
    $fecha = fn ($date) => $date ? $date->format('d/m/Y') : '—';
    $dinero = fn ($amount) => '$'.number_format((float) $amount, 2);

    $initials = collect(preg_split('/\s+/', trim($user->name)))
        ->reject(fn ($part) => in_array(mb_strtolower(rtrim($part, '.')), ['dr', 'dra', 'lic', 'mtra', 'mtro'], true))
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');

    $type = $accountType['type'] ?? null;

    // Uso del plan
    $usagePercent = $maxPatients ? min(100, (int) round($patientsCount * 100 / $maxPatients)) : null;
@endphp


@section('content_header')

    <div class="patients-header">
        <div>
            <span class="header-eyebrow">
                <i class="fas fa-leaf mr-1"></i>
                NUTRILIFE · CUENTA
            </span>

            <h1>Mi perfil</h1>

            <p>
                Tu información, tu plan y la configuración de tu cuenta.
            </p>
        </div>
    </div>

@stop


@section('content')

    <div class="nl-profile">

        {{-- =====================================================
             1. CUENTA DEL NUTRIÓLOGO
             ===================================================== --}}
        <section class="nl-profile-card nl-account">

            <div class="nl-account-head">
                <span class="nl-avatar" aria-hidden="true">{{ $initials ?: 'N' }}</span>

                <div class="nl-account-id">
                    <h2>{{ $user->name }}</h2>
                    <p>{{ $user->email }}</p>

                    <div class="nl-chips">
                        @if ($accountType)
                            <span class="nl-chip nl-chip--{{ $type }}">
                                <i class="fas {{ match ($type) { 'active' => 'fa-crown', 'trial' => 'fa-seedling', 'admin' => 'fa-user-shield', default => 'fa-hourglass-end' } }}" aria-hidden="true"></i>
                                {{ $accountType['label'] }}
                            </span>
                        @endif

                        <span class="nl-chip">
                            <i class="fas fa-user-doctor" aria-hidden="true"></i>
                            {{ $user->role?->label() ?? 'Nutriólogo' }}
                        </span>

                        @if ($user->email_verified_at)
                            <span class="nl-chip nl-chip--ok">
                                <i class="fas fa-circle-check" aria-hidden="true"></i>
                                Correo verificado
                            </span>
                        @else
                            <span class="nl-chip nl-chip--warn">
                                <i class="fas fa-circle-exclamation" aria-hidden="true"></i>
                                Correo sin verificar
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <dl class="nl-facts">
                <div>
                    <dt>En NutriLife desde</dt>
                    <dd>{{ $fecha($user->created_at) }}</dd>
                </div>
                <div>
                    <dt>Cliente desde</dt>
                    <dd>{{ $customerSince ? $fecha($customerSince) : 'Aún no contratas un plan' }}</dd>
                </div>
                <div class="nl-facts-code">
                    <dt>Código de acceso para tus pacientes</dt>
                    <dd>
                        <span class="nl-access-code">{{ $user->access_code }}</span>
                        <small>Tus pacientes lo usan, junto con su correo, para entrar a la app móvil.</small>
                    </dd>
                </div>
            </dl>

        </section>


        {{-- =====================================================
             2. SUSCRIPCIÓN
             ===================================================== --}}
        <section class="nl-profile-card nl-subscription">

            <header class="nl-section-head">
                <div>
                    <span class="nl-eyebrow">Suscripción</span>
                    <h2>
                        @if ($type === 'trial')
                            Prueba gratuita de 7 días
                        @elseif ($subscription?->plan)
                            Plan {{ $subscription->plan->name }}
                        @else
                            Sin suscripción
                        @endif
                    </h2>
                </div>

                @if ($subscription)
                    <span class="nl-status nl-status--{{ $subscription->status }}">
                        {{ $subscription->statusLabel() }}
                    </span>
                @endif
            </header>

            @if (! $subscription)

                <p class="nl-muted">
                    Todavía no tienes una suscripción registrada. Tu prueba gratuita se
                    activará en cuanto haya planes disponibles.
                </p>

            @else

                <dl class="nl-facts nl-facts--grid">

                    @if ($type === 'trial')
                        <div>
                            <dt>Inicio de la prueba</dt>
                            <dd>{{ $fecha($subscription->starts_at) }}</dd>
                        </div>
                        <div>
                            <dt>Termina</dt>
                            <dd>{{ $fecha($subscription->current_period_ends_at) }}</dd>
                        </div>
                        <div>
                            <dt>Días restantes</dt>
                            <dd>
                                @if (($trialStatus['days'] ?? 0) === 0)
                                    Hoy es el último día
                                @else
                                    {{ $trialStatus['days'] }} {{ $trialStatus['days'] === 1 ? 'día' : 'días' }}
                                @endif
                            </dd>
                        </div>
                    @else
                        <div>
                            <dt>Precio</dt>
                            <dd>
                                {{ $dinero($subscription->plan?->price) }} MXN
                                <small>/ {{ $subscription->plan?->billing_period === 'anual' ? 'año' : 'mes' }}</small>
                            </dd>
                        </div>
                        <div>
                            <dt>{{ $subscription->status === 'activa' ? 'Activa desde' : 'Inicio' }}</dt>
                            <dd>{{ $fecha($subscription->starts_at) }}</dd>
                        </div>
                        <div>
                            <dt>Periodo pagado hasta</dt>
                            <dd>{{ $fecha($subscription->current_period_ends_at) }}</dd>
                        </div>
                        @if ($subscription->status === 'activa')
                            <div>
                                <dt>Próxima renovación</dt>
                                <dd>
                                    @php $renewDays = $subscription->trialDaysRemaining(); @endphp
                                    {{ $renewDays > 0 ? 'En '.$renewDays.' '.($renewDays === 1 ? 'día' : 'días') : 'Hoy' }}
                                </dd>
                            </div>
                        @endif
                        @if ($subscription->cancelled_at)
                            <div>
                                <dt>Cancelada el</dt>
                                <dd>{{ $fecha($subscription->cancelled_at) }}</dd>
                            </div>
                        @endif
                    @endif

                </dl>

                {{-- Uso del plan: subscription_plans.max_patients --}}
                @if ($type !== 'trial' && $subscription->plan)
                    <div class="nl-usage">
                        <div class="nl-usage-head">
                            <span>Pacientes registrados</span>
                            <strong>
                                {{ $patientsCount }}
                                @if ($maxPatients)
                                    de {{ $maxPatients }}
                                @else
                                    · sin límite
                                @endif
                            </strong>
                        </div>
                        @if ($maxPatients)
                            <div class="nl-usage-bar {{ $usagePercent >= 90 ? 'is-high' : '' }}" role="progressbar"
                                 aria-valuenow="{{ $usagePercent }}" aria-valuemin="0" aria-valuemax="100"
                                 aria-label="Uso del límite de pacientes">
                                <span style="width: {{ $usagePercent }}%"></span>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Qué incluye el plan (config/nutrilife.php → plan_features) --}}
                @if ($type !== 'trial' && count($planFeatures))
                    <div class="nl-features">
                        <span class="nl-eyebrow">Tu plan incluye</span>
                        <ul>
                            @foreach ($planFeatures as $feature)
                                <li class="{{ $feature['available'] ? '' : 'is-soon' }}">
                                    <i class="fas {{ $feature['available'] ? 'fa-check' : 'fa-clock' }}" aria-hidden="true"></i>
                                    {{ $feature['label'] }}
                                    @unless ($feature['available'])
                                        <small>Próximamente</small>
                                    @endunless
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (in_array($type, ['trial', 'expired', 'cancelled'], true))
                    <a href="{{ route('subscription.plans') }}" class="nl-btn-primary">
                        {{ $type === 'trial' ? 'Conocer los planes' : 'Elegir un plan' }}
                        <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    </a>
                @elseif ($type === 'active')
                    <a href="{{ route('subscription.plans') }}" class="nl-btn-ghost">
                        Cambiar de paquete
                        <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    </a>
                @endif

            @endif

        </section>


        {{-- =====================================================
             3. RESUMEN DE TU CONSULTA
             ===================================================== --}}
        <section class="nl-profile-card">
            <header class="nl-section-head">
                <div>
                    <span class="nl-eyebrow">Tu consulta</span>
                    <h2>Resumen</h2>
                </div>
            </header>

            <div class="nl-stats">
                <a href="{{ route('pacientes.index') }}" class="nl-stat">
                    <strong>{{ $stats['active_patients'] }}</strong>
                    <span>Pacientes activos</span>
                    <small>{{ $stats['patients'] }} registrados</small>
                </a>
                <a href="{{ route('citas.index') }}" class="nl-stat">
                    <strong>{{ $stats['upcoming_appointments'] }}</strong>
                    <span>Citas próximas</span>
                    <small>Pendientes o confirmadas</small>
                </a>
                <a href="{{ route('consultas.index') }}" class="nl-stat">
                    <strong>{{ $stats['consultations_month'] }}</strong>
                    <span>Consultas este mes</span>
                    <small>Registradas en {{ now()->format('m/Y') }}</small>
                </a>
            </div>
        </section>


        {{-- =====================================================
             4. COBROS DE NUTRILIFE (subscription_payments)
             ===================================================== --}}
        <section class="nl-profile-card">
            <header class="nl-section-head">
                <div>
                    <span class="nl-eyebrow">Facturación</span>
                    <h2>Historial de cobros</h2>
                </div>
                @if ($subscriptionPayments->isNotEmpty())
                    <span class="nl-total">Total pagado: <strong>{{ $dinero($totalPaid) }} MXN</strong></span>
                @endif
            </header>

            @if ($subscriptionPayments->isEmpty())
                <p class="nl-muted">
                    Aún no hay cobros. {{ $type === 'trial' ? 'Estás usando la prueba gratuita.' : '' }}
                </p>
            @else
                <div class="table-responsive">
                    <table class="nl-table">
                        <thead>
                            <tr>
                                <th scope="col">Fecha</th>
                                <th scope="col">Plan</th>
                                <th scope="col">Monto</th>
                                <th scope="col">Método</th>
                                <th scope="col">Referencia</th>
                                <th scope="col">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($subscriptionPayments as $payment)
                                <tr>
                                    <td>{{ $fecha($payment->paid_at) }}</td>
                                    <td>{{ $payment->subscription?->plan?->name ?? '—' }}</td>
                                    <td>{{ $dinero($payment->amount) }}</td>
                                    <td>{{ $payment->payment_method }}</td>
                                    <td>{{ $payment->reference ?: '—' }}</td>
                                    <td>
                                        <span class="nl-status nl-status--{{ $payment->status === 'pagado' ? 'activa' : 'vencida' }}">
                                            {{ ucfirst($payment->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>


        {{-- =====================================================
             5. CONFIGURACIÓN DE LA CUENTA (formularios de Breeze)
             ===================================================== --}}
        <div class="nl-settings-title">
            <span class="nl-eyebrow">Configuración</span>
            <h2>Configuración de la cuenta</h2>
            <p>Actualiza tus datos de acceso y la seguridad de tu cuenta.</p>
        </div>

        <div class="nl-settings-grid">
            <div class="nl-profile-card">
                @include('profile.partials.update-profile-information-form')
            </div>

            <div class="nl-profile-card">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="nl-profile-card nl-profile-card--danger">
            @include('profile.partials.delete-user-form')
        </div>

    </div>

@stop


@section('css')

    @include('partials.theme-styles', ['section' => 'admin'])

@stop


@section('js')

    {{-- Mostrar/ocultar contraseñas, diálogo "Eliminar cuenta" y avisos "Guardado" --}}
    @vite(['resources/js/pages/profile-edit.js'])

@stop

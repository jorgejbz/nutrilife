@extends('adminlte::page')

{{-- =========================================================
     DETALLE DE UNA CUENTA (/admin/cuentas/{id})
     Datos, plan, cobros, subperfiles (si es clínica) y acciones
     administrativas con confirmación. AdminAccountController@show.
     ========================================================= --}}

@section('title', $account->name.' | Administración NutriLife')

@php
    $fecha = fn ($date) => $date ? $date->format('d/m/Y') : '—';
    $dinero = fn ($amount) => '$'.number_format((float) $amount, 2);
    $isClinic = $type['key'] === 'clinic';
    $isMember = $type['key'] === 'member';
@endphp

@section('content_header')
    <div class="patients-header">
        <div>
            <span class="header-eyebrow">
                <i class="fas fa-leaf me-1"></i>
                NUTRILIFE · ADMINISTRACIÓN GLOBAL · {{ mb_strtoupper($type['label']) }}
            </span>
            <h1>{{ $account->name }}</h1>
            <p>
                {{ $account->email }}
                @if ($isMember && $account->clinic)
                    · Subperfil de <a href="{{ route('admin.accounts.show', $account->clinic) }}">{{ $account->clinic->name }}</a>
                @endif
            </p>
        </div>
        <div class="nl-page-actions">
            <a href="{{ route('admin.accounts.index') }}" class="nl-btn nl-btn--ghost">
                <i class="fas fa-arrow-left" aria-hidden="true"></i> Cuentas
            </a>
        </div>
    </div>
@stop

@section('content')
<div class="nl-profile">

    @include('partials.flash')

    {{-- 1. DATOS --}}
    <section class="nl-profile-card">
        <header class="nl-section-head">
            <div>
                <span class="nl-eyebrow">{{ $isClinic ? 'Cuenta principal de Consultorio' : ($isMember ? 'Subperfil de clínica (sin suscripción propia)' : 'Cuenta') }}</span>
                <h2>Datos de la cuenta</h2>
            </div>
            <span class="nl-status nl-status--{{ $account->account_status }}">{{ $account->accountStatusLabel() }}</span>
        </header>

        <dl class="nl-facts nl-facts--grid">
            <div><dt>Tipo</dt><dd>{{ $type['label'] }}</dd></div>
            <div><dt>Plan</dt><dd>{{ $accountType['label'] ?? 'Sin suscripción' }}</dd></div>
            <div><dt>Registro</dt><dd>{{ $fecha($account->created_at) }}</dd></div>
            <div><dt>Activación</dt><dd>{{ $account->activated_at ? $fecha($account->activated_at) : ($account->activates_at ? 'Programada '.$account->activates_at->format('d/m/Y H:i') : '—') }}</dd></div>
            <div><dt>Correo</dt><dd>{{ $account->emailVerificationState()['label'] }}</dd></div>
            <div><dt>Cédula profesional</dt><dd>{{ $account->professional_license ?: 'No registrado' }}</dd></div>
            <div><dt>Título profesional</dt><dd>{{ $account->degree_number ?: 'No registrado' }}</dd></div>
            <div><dt>Verificación profesional</dt><dd>{{ $account->professional_verified_at ? 'Verificada '.$fecha($account->professional_verified_at) : 'Pendiente' }}</dd></div>
            @unless ($account->isAdmin())
                <div><dt>Pacientes propios</dt><dd>{{ $account->patients_count }}</dd></div>
                @if ($isClinic)
                    <div><dt>Nutriólogos asociados</dt><dd>{{ $account->clinic_members_count }}</dd></div>
                    <div><dt>Pacientes de la clínica</dt><dd>{{ $orgPatients }} <small>· cuenta principal + subperfiles</small></dd></div>
                @endif
                <div><dt>Citas próximas{{ $isClinic ? ' (clínica)' : '' }}</dt><dd>{{ $upcomingAppointments }}</dd></div>
            @endunless
        </dl>
    </section>

    {{-- 2. SUBPERFILES (clínicas) --}}
    @if ($isClinic)
        <section class="nl-profile-card">
            <header class="nl-section-head">
                <div>
                    <span class="nl-eyebrow">Equipo de la clínica</span>
                    <h2>Subperfiles de nutriólogo</h2>
                </div>
            </header>

            @if ($members->isEmpty())
                <p class="nl-empty"><i class="fas fa-user-doctor" aria-hidden="true"></i>Esta clínica aún no tiene subperfiles.</p>
            @else
                <div class="table-responsive">
                    <table class="nl-table">
                        <thead>
                            <tr>
                                <th scope="col">Nutriólogo</th>
                                <th scope="col">Estado</th>
                                <th scope="col">Acceso</th>
                                <th scope="col" class="is-num">Pacientes</th>
                                <th scope="col" class="is-num">Próximas citas</th>
                                <th scope="col">Alta</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($members as $member)
                                <tr>
                                    <td data-label="Nutriólogo">
                                        <a href="{{ route('admin.accounts.show', $member) }}"><strong>{{ $member->name }}</strong></a>
                                        <small>{{ $member->email }}</small>
                                    </td>
                                    <td data-label="Estado"><span class="nl-status nl-status--{{ $member->account_status }}">{{ $member->accountStatusLabel() }}</span></td>
                                    <td data-label="Acceso">Funciones {{ config('nutrilife.clinic.member_plan') }}</td>
                                    <td data-label="Pacientes" class="is-num">{{ $member->patients_count }}</td>
                                    <td data-label="Próximas citas" class="is-num">{{ $member->upcoming_appointments_count }}</td>
                                    <td data-label="Alta">{{ $fecha($member->created_at) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @endif

    {{-- 3. CITAS --}}
    @unless ($account->isAdmin())
        <section class="nl-profile-card">
            <header class="nl-section-head">
                <div>
                    <span class="nl-eyebrow">Actividad{{ $isClinic ? ' de toda la clínica' : '' }}</span>
                    <h2>Citas por estado</h2>
                </div>
            </header>
            <div class="nl-stats">
                @foreach ($appointmentStatuses as $status => $count)
                    <div class="nl-stat"><strong>{{ $count }}</strong><span>{{ $status }}</span></div>
                @endforeach
            </div>
        </section>
    @endunless

    {{-- 4. ACCIONES --}}
    @if ($canManage)
        <section class="nl-profile-card">
            <header class="nl-section-head">
                <div>
                    <span class="nl-eyebrow">Acciones administrativas</span>
                    <h2>Estado del acceso</h2>
                </div>
            </header>

            @php
                $statusAction = match ($account->account_status) {
                    \App\Models\User::ACCOUNT_PENDING => ['activar', 'Activar ahora', 'fa-user-check', 'nl-btn--ok', '¿Activar ahora la cuenta de '.$account->name.'? Se saltará el tiempo de espera y empezará su prueba gratuita.'],
                    \App\Models\User::ACCOUNT_DISABLED => ['reactivar', 'Reactivar cuenta', 'fa-user-check', 'nl-btn--ok', '¿Reactivar la cuenta de '.$account->name.'?'],
                    default => ['desactivar', 'Desactivar cuenta', 'fa-user-slash', 'nl-btn--danger-soft', '¿Desactivar la cuenta de '.$account->name.'? No podrá iniciar sesión'.($isClinic ? ' y sus subperfiles tampoco' : '').'. No se borra ningún dato.'],
                };
            @endphp

            <form method="post" action="{{ route('admin.accounts.status', $account) }}" class="nl-inline-form"
                  data-confirm="{{ $statusAction[4] }}"
                  data-confirm-title="{{ $statusAction[1] }}"
                  data-confirm-accept="{{ $statusAction[1] }}"
                  data-confirm-variant="{{ $statusAction[0] === 'desactivar' ? 'danger' : '' }}"
                  data-confirm-icon="{{ $statusAction[2] }}">
                @csrf
                @method('patch')
                <input type="hidden" name="action" value="{{ $statusAction[0] }}">
                <div class="nl-field">
                    <label for="reason">Motivo (queda en la bitácora)</label>
                    <div class="nl-input">
                        <i class="far fa-comment" aria-hidden="true"></i>
                        <input id="reason" name="reason" type="text" maxlength="500" placeholder="Opcional">
                    </div>
                </div>
                <button type="submit" class="nl-btn {{ $statusAction[3] }}">
                    <i class="fas {{ $statusAction[2] }}" aria-hidden="true"></i> {{ $statusAction[1] }}
                </button>
            </form>

            @unless ($isMember)
                <header class="nl-section-head nl-mt-26">
                    <div>
                        <span class="nl-eyebrow">Plan</span>
                        <h2>Asignar o cambiar plan</h2>
                    </div>
                </header>
                <p class="nl-muted nl-mb-12">
                    Cierra la suscripción actual y abre una nueva desde hoy. No usa pasarelas: el cobro se registra como
                    "Manual" (o ninguno, si es una cortesía) y uno pendiente se confirma en Cobros de la plataforma.
                </p>
                <form method="post" action="{{ route('admin.accounts.plan', $account) }}" class="nl-inline-form"
                      data-confirm="¿Asignar el plan seleccionado a {{ $account->name }}? La suscripción actual se cerrará."
                      data-confirm-title="Asignar plan" data-confirm-accept="Asignar plan" data-confirm-icon="fa-crown">
                    @csrf
                    <div class="nl-field">
                        <label for="subscription_plan_id">Plan</label>
                        <div class="nl-input">
                            <i class="fas fa-crown" aria-hidden="true"></i>
                            <select id="subscription_plan_id" name="subscription_plan_id" required>
                                @foreach ($plans as $plan)
                                    <option value="{{ $plan->id }}" @selected($account->latestSubscription?->subscription_plan_id === $plan->id)>
                                        {{ $plan->name }} · {{ $dinero($plan->price) }}/{{ $plan->billing_period === 'anual' ? 'año' : 'mes' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="nl-field">
                        <label for="months">Meses</label>
                        <div class="nl-input">
                            <i class="far fa-calendar" aria-hidden="true"></i>
                            <input id="months" name="months" type="number" min="1" max="24" value="1" required>
                        </div>
                    </div>
                    <div class="nl-field">
                        <label for="payment">Cobro</label>
                        <div class="nl-input">
                            <i class="fas fa-receipt" aria-hidden="true"></i>
                            <select id="payment" name="payment" required>
                                <option value="pendiente">Registrar cobro pendiente</option>
                                <option value="pagado">Registrar cobro ya pagado</option>
                                <option value="ninguno">Sin cobro (cortesía)</option>
                            </select>
                        </div>
                    </div>
                    <div class="nl-field">
                        <label for="plan_reason">Motivo</label>
                        <div class="nl-input">
                            <i class="far fa-comment" aria-hidden="true"></i>
                            <input id="plan_reason" name="reason" type="text" maxlength="500" placeholder="Opcional">
                        </div>
                    </div>
                    <button type="submit" class="nl-btn nl-btn--primary"><i class="fas fa-crown" aria-hidden="true"></i> Asignar</button>
                </form>
            @endunless
        </section>
    @endif

    {{-- 5. SUSCRIPCIONES Y COBROS --}}
    @if ($subscriptions->isNotEmpty())
        <section class="nl-profile-card">
            <header class="nl-section-head">
                <div>
                    <span class="nl-eyebrow">Facturación</span>
                    <h2>Suscripciones y cobros</h2>
                </div>
            </header>
            <div class="table-responsive">
                <table class="nl-table">
                    <thead>
                        <tr>
                            <th scope="col">Plan</th>
                            <th scope="col">Estado</th>
                            <th scope="col">Inicio</th>
                            <th scope="col">Fin del periodo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($subscriptions as $subscription)
                            <tr>
                                <td data-label="Plan">{{ $subscription->isTrial() ? 'Prueba gratuita' : $subscription->plan?->name }}</td>
                                <td data-label="Estado"><span class="nl-status nl-status--{{ $subscription->status }}">{{ $subscription->statusLabel() }}</span></td>
                                <td data-label="Inicio">{{ $fecha($subscription->starts_at) }}</td>
                                <td data-label="Fin">{{ $fecha($subscription->current_period_ends_at) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($subscriptionPayments->isNotEmpty())
                <div class="table-responsive nl-mt-20">
                    <table class="nl-table">
                        <thead>
                            <tr>
                                <th scope="col">Fecha</th>
                                <th scope="col">Plan</th>
                                <th scope="col" class="is-num">Monto</th>
                                <th scope="col">Referencia y método</th>
                                <th scope="col">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($subscriptionPayments as $payment)
                                <tr>
                                    <td data-label="Fecha">{{ $fecha($payment->paid_at) }}</td>
                                    <td data-label="Plan">{{ $payment->subscription?->plan?->name }}</td>
                                    <td data-label="Monto" class="is-num">{{ $dinero($payment->amount) }}</td>
                                    <td data-label="Referencia" class="payment-reference-cell">@include('partials.payment-reference', ['payment' => $payment, 'external' => true, 'externalLabel' => 'Código de origen'])</td>
                                    <td data-label="Estado">
                                        <span class="nl-status nl-status--{{ $payment->status }}">{{ ucfirst($payment->status) }}</span>
                                        @if ($payment->statusChangedBy)
                                            <small>Confirmó {{ $payment->statusChangedBy->name }}</small>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @endif

    {{-- 6. BITÁCORA --}}
    @if ($history->isNotEmpty())
        <section class="nl-profile-card">
            <header class="nl-section-head">
                <div>
                    <span class="nl-eyebrow">Bitácora</span>
                    <h2>Cambios en esta cuenta</h2>
                </div>
            </header>
            <ul class="nl-list-plain">
                @foreach ($history as $log)
                    <li>
                        <div>
                            <strong>{{ $log->actionLabel() }}</strong>
                            <small>
                                {{ $log->user?->name ?? 'Sistema' }}
                                @if (! empty($log->details['reason'])) · «{{ $log->details['reason'] }}» @endif
                                @if (! empty($log->details['to'])) · {{ $log->details['from'] ?? '—' }} → {{ $log->details['to'] }} @endif
                            </small>
                        </div>
                        <small>{{ $log->created_at?->format('d/m/Y H:i') }}</small>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

</div>
@stop

@section('css')
    @include('partials.theme-styles', ['section' => 'admin'])
@stop

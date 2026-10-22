@extends('adminlte::page')

{{-- =========================================================
     PANEL DE LA CLÍNICA (/clinica)
     Indicadores reales de toda la organización: la cuenta principal
     y sus subperfiles. Datos: ClinicDashboardController + ClinicStats.
     ========================================================= --}}

@section('title', 'Panel de la clínica | NutriLife')

@php
    $hora = fn ($date) => $date ? $date->format('H:i') : '—';
    $dia = fn ($date) => $date ? $date->translatedFormat('d/m/Y H:i') : '—';
    $maxPatients = max(1, (int) $nutritionists->max('patients_count'));
    $statusClass = fn (string $status) => \Illuminate\Support\Str::slug($status);
@endphp

@section('content_header')
    <div class="patients-header">
        <div>
            <span class="header-eyebrow">
                <i class="fas fa-leaf me-1"></i>
                NUTRILIFE · CLÍNICA
            </span>
            <h1>{{ $clinic->name }}</h1>
            <p>Resumen de tu equipo, pacientes y agenda de toda la clínica.</p>
        </div>

        <div class="nl-page-actions">
            <a href="{{ route('clinic.team.create') }}" class="nl-btn nl-btn--primary">
                <i class="fas fa-user-plus" aria-hidden="true"></i>
                Agregar nutriólogo
            </a>
        </div>
    </div>
@stop

@section('content')
<div class="nl-profile">

    @include('partials.flash')

    @unless ($licenseActive)
        <div class="nl-alert nl-alert--error" role="alert">
            <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
            <div>
                La licencia Consultorio no está vigente: tus subperfiles no pueden entrar y no puedes agregar nuevos.
                <a href="{{ route('subscription.plans') }}">Renovar plan</a>
            </div>
        </div>
    @endunless

    {{-- 1. INDICADORES --}}
    <div class="nl-kpis">
        <a href="{{ route('clinic.team.index') }}" class="nl-kpi">
            <i class="fas fa-user-doctor" aria-hidden="true"></i>
            <strong>{{ $totals['members'] }}</strong>
            <span>Nutriólogos asociados</span>
            <small>
                Subperfiles{{ $memberLimit !== null ? ' · límite '.$memberLimit : '' }} (más la cuenta principal)
            </small>
        </a>
        <a href="{{ route('clinic.patients.index') }}" class="nl-kpi is-info">
            <i class="fas fa-people-group" aria-hidden="true"></i>
            <strong>{{ $totals['patients'] }}</strong>
            <span>Pacientes de la clínica</span>
            <small>{{ $totals['active_patients'] }} activos · {{ $totals['new_patients_month'] }} nuevos este mes</small>
        </a>
        <a href="{{ route('clinic.agenda.index') }}" class="nl-kpi is-warn">
            <i class="fas fa-calendar-day" aria-hidden="true"></i>
            <strong>{{ $totals['today_appointments'] }}</strong>
            <span>Citas de hoy</span>
            <small>{{ $totals['upcoming_appointments'] }} próximas pendientes o confirmadas</small>
        </a>
        {{-- Mismo criterio que ClinicStats: consultas desde el día 1 del mes --}}
        <a href="{{ route('clinic.consultations.index', ['desde' => now()->startOfMonth()->toDateString()]) }}" class="nl-kpi">
            <i class="fas fa-notes-medical" aria-hidden="true"></i>
            <strong>{{ $totals['consultations_month'] }}</strong>
            <span>Consultas este mes</span>
            <small>Ingresos registrados: ${{ number_format($totals['income_month'], 2) }} · {{ $totals['pending_payments'] }} pagos pendientes</small>
        </a>
        <a href="{{ route('clinic.payments.index', ['estado' => 'Pendiente']) }}" class="nl-kpi is-danger">
            <i class="fas fa-receipt" aria-hidden="true"></i>
            <strong>{{ $totals['pending_payments'] }}</strong>
            <span>Pagos pendientes</span>
            <small>De todo el equipo · consulta cada pago por su referencia</small>
        </a>
    </div>

    {{-- 2. CITAS DEL MES POR ESTADO --}}
    <section class="nl-profile-card">
        <header class="nl-section-head">
            <div>
                <span class="nl-eyebrow">Agenda · {{ now()->translatedFormat('F Y') }}</span>
                <h2>Citas del mes por estado</h2>
            </div>
        </header>
        <div class="nl-stats">
            @foreach ($statuses as $status => $count)
                <a href="{{ route('clinic.agenda.index', ['estado' => $status, 'desde' => now()->startOfMonth()->toDateString(), 'hasta' => now()->endOfMonth()->toDateString()]) }}" class="nl-stat">
                    <strong>{{ $count }}</strong>
                    <span>{{ ['Pendiente' => 'Pendientes', 'Confirmada' => 'Confirmadas', 'Completada' => 'Atendidas (completadas)', 'Cancelada' => 'Canceladas'][$status] ?? $status }}</span>
                </a>
            @endforeach
        </div>
    </section>

    <div class="nl-split">

        {{-- 3. PROFESIONALES --}}
        <section class="nl-profile-card">
            <header class="nl-section-head">
                <div>
                    <span class="nl-eyebrow">Equipo</span>
                    <h2>Pacientes por nutriólogo</h2>
                </div>
                <a href="{{ route('clinic.team.index') }}" class="nl-btn-ghost">
                    Administrar equipo <i class="fas fa-arrow-right" aria-hidden="true"></i>
                </a>
            </header>

            <ul class="nl-bars">
                @foreach ($nutritionists as $nutritionist)
                    <li>
                        <span title="{{ $nutritionist->name }}">
                            @if ($nutritionist->id === $clinic->id)
                                {{ $nutritionist->name }} <small>(cuenta principal)</small>
                            @else
                                <a href="{{ route('clinic.team.show', $nutritionist->id) }}">{{ $nutritionist->name }}</a>
                            @endif
                        </span>
                        <span class="nl-usage-bar" role="img" aria-label="{{ $nutritionist->patients_count }} pacientes">
                            <span style="width: {{ (int) round($nutritionist->patients_count * 100 / $maxPatients) }}%"></span>
                        </span>
                        <strong>{{ $nutritionist->patients_count }}</strong>
                    </li>
                @endforeach
            </ul>

            <div class="table-responsive nl-mt-20">
                <table class="nl-table">
                    <thead>
                        <tr>
                            <th scope="col">Nutriólogo</th>
                            <th scope="col">Estado</th>
                            <th scope="col" class="is-num">Activos</th>
                            <th scope="col" class="is-num">Citas hoy</th>
                            <th scope="col" class="is-num">Próximas</th>
                            <th scope="col" class="is-num">Planes activos</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($nutritionists as $nutritionist)
                            <tr>
                                <td data-label="Nutriólogo">
                                    <strong>{{ $nutritionist->name }}</strong>
                                    <small>{{ $nutritionist->id === $clinic->id ? 'Cuenta principal' : $nutritionist->email }}</small>
                                </td>
                                <td data-label="Estado">
                                    <span class="nl-status nl-status--{{ $nutritionist->account_status }}">{{ $nutritionist->accountStatusLabel() }}</span>
                                </td>
                                <td data-label="Activos" class="is-num">{{ $nutritionist->active_patients_count }}</td>
                                <td data-label="Citas hoy" class="is-num">{{ $nutritionist->today_appointments_count }}</td>
                                <td data-label="Próximas" class="is-num">{{ $nutritionist->upcoming_appointments_count }}</td>
                                <td data-label="Planes activos" class="is-num">{{ $nutritionist->active_diet_plans_count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        {{-- 4. ACTIVIDAD --}}
        <section class="nl-profile-card">
            <header class="nl-section-head">
                <div>
                    <span class="nl-eyebrow">Actividad</span>
                    <h2>Lo más reciente</h2>
                </div>
            </header>

            @if ($activity->isEmpty())
                <p class="nl-empty"><i class="fas fa-seedling" aria-hidden="true"></i>Aún no hay actividad registrada.</p>
            @else
                <ul class="nl-list-plain">
                    @foreach ($activity as $event)
                        <li>
                            <div>
                                <strong><i class="fas {{ $event['icon'] }} me-1" aria-hidden="true"></i> {{ $event['text'] }}</strong>
                                <small>{{ $event['who'] }}</small>
                            </div>
                            <small>{{ $event['at']->diffForHumans() }}</small>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

    <div class="nl-split">
        {{-- 5. CITAS DE HOY --}}
        <section class="nl-profile-card">
            <header class="nl-section-head">
                <div>
                    <span class="nl-eyebrow">Hoy · {{ now()->format('d/m/Y') }}</span>
                    <h2>Citas de hoy</h2>
                </div>
                <a href="{{ route('clinic.agenda.index') }}" class="nl-btn-ghost">
                    Ver agenda <i class="fas fa-arrow-right" aria-hidden="true"></i>
                </a>
            </header>

            @if ($todayAppointments->isEmpty())
                <p class="nl-empty"><i class="far fa-calendar" aria-hidden="true"></i>No hay citas para hoy.</p>
            @else
                <div class="table-responsive">
                    <table class="nl-table">
                        <thead>
                            <tr>
                                <th scope="col">Hora</th>
                                <th scope="col">Paciente</th>
                                <th scope="col">Nutriólogo</th>
                                <th scope="col">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($todayAppointments as $appointment)
                                <tr>
                                    <td data-label="Hora">{{ $hora($appointment->appointment_at) }}</td>
                                    <td data-label="Paciente"><strong>{{ $appointment->patient?->first_name }} {{ $appointment->patient?->last_name }}</strong></td>
                                    <td data-label="Nutriólogo">{{ $appointment->user?->name }}</td>
                                    <td data-label="Estado"><span class="nl-status nl-status--{{ $statusClass($appointment->status) }}">{{ $appointment->status }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        {{-- 6. PRÓXIMAS CITAS --}}
        <section class="nl-profile-card">
            <header class="nl-section-head">
                <div>
                    <span class="nl-eyebrow">Próximamente</span>
                    <h2>Próximas citas</h2>
                </div>
            </header>

            @if ($upcomingAppointments->isEmpty())
                <p class="nl-empty"><i class="far fa-calendar-check" aria-hidden="true"></i>No hay citas próximas.</p>
            @else
                <ul class="nl-list-plain">
                    @foreach ($upcomingAppointments as $appointment)
                        <li>
                            <div>
                                <strong>{{ $appointment->patient?->first_name }} {{ $appointment->patient?->last_name }}</strong>
                                <small>{{ $appointment->user?->name }} · {{ $appointment->reason ?: 'Sin motivo' }}</small>
                            </div>
                            <small>{{ $dia($appointment->appointment_at) }}</small>
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

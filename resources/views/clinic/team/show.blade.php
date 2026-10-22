@extends('adminlte::page')

{{-- =========================================================
     DETALLE DE UN NUTRIÓLOGO DE LA CLÍNICA (/clinica/equipo/{id})
     Datos, estado, pacientes y citas del subperfil (solo lectura;
     cada nutriólogo edita sus expedientes desde su propia cuenta).
     Datos: ClinicTeamController@show.
     ========================================================= --}}

@section('title', $member->name.' | NutriLife')

@php
    $fecha = fn ($date) => $date ? $date->format('d/m/Y') : '—';
    $isActive = $member->account_status === \App\Models\User::ACCOUNT_ACTIVE;
@endphp

@section('content_header')
    <div class="patients-header">
        <div>
            <span class="header-eyebrow">
                <i class="fas fa-leaf me-1"></i>
                NUTRILIFE · CLÍNICA · EQUIPO
            </span>
            <h1>{{ $member->name }}</h1>
            <p>Nutriólogo de {{ $clinic->name }} · {{ $member->email }}</p>
        </div>

        <div class="nl-page-actions">
            <a href="{{ route('clinic.team.index') }}" class="nl-btn nl-btn--ghost">
                <i class="fas fa-arrow-left" aria-hidden="true"></i> Equipo
            </a>
            <a href="{{ route('clinic.team.edit', $member->id) }}" class="nl-btn nl-btn--ghost">
                <i class="fas fa-pen" aria-hidden="true"></i> Editar
            </a>
            <form method="post" action="{{ route('clinic.team.status', $member->id) }}"
                  data-confirm="{{ $isActive
                        ? '¿Desactivar el acceso de '.$member->name.'? No podrá iniciar sesión; sus pacientes, citas y planes se conservan.'
                        : '¿Reactivar el acceso de '.$member->name.'?' }}"
                  data-confirm-title="{{ $isActive ? 'Desactivar subperfil' : 'Reactivar subperfil' }}"
                  data-confirm-accept="{{ $isActive ? 'Desactivar' : 'Reactivar' }}"
                  data-confirm-variant="{{ $isActive ? 'danger' : '' }}"
                  data-confirm-icon="{{ $isActive ? 'fa-user-slash' : 'fa-user-check' }}">
                @csrf
                @method('patch')
                <input type="hidden" name="action" value="{{ $isActive ? 'desactivar' : 'activar' }}">
                <button type="submit" class="nl-btn {{ $isActive ? 'nl-btn--danger-soft' : 'nl-btn--ok' }}">
                    <i class="fas {{ $isActive ? 'fa-user-slash' : 'fa-user-check' }}" aria-hidden="true"></i>
                    {{ $isActive ? 'Desactivar acceso' : 'Reactivar acceso' }}
                </button>
            </form>
        </div>
    </div>
@stop

@section('content')
<div class="nl-profile">

    @include('partials.flash')

    <section class="nl-profile-card">
        <header class="nl-section-head">
            <div>
                <span class="nl-eyebrow">Subperfil · funciones {{ config('nutrilife.clinic.member_plan') }}</span>
                <h2>Información profesional y acceso</h2>
            </div>
            <span class="nl-status nl-status--{{ $member->account_status }}">{{ $member->accountStatusLabel() }}</span>
        </header>

        <dl class="nl-facts nl-facts--grid">
            <div><dt>Correo</dt><dd>{{ $member->email }} <small>· {{ $member->emailVerificationState()['label'] }}</small></dd></div>
            <div><dt>Cédula profesional</dt><dd>{{ $member->professional_license ?: 'No registrado' }}</dd></div>
            <div><dt>Título profesional</dt><dd>{{ $member->degree_number ?: 'No registrado' }}</dd></div>
            <div><dt>Alta en la clínica</dt><dd>{{ $fecha($member->created_at) }}</dd></div>
            <div><dt>Código de acceso (app de pacientes)</dt><dd>{{ $member->access_code }}</dd></div>
            <div><dt>Pacientes</dt><dd>{{ $member->patients_count }} <small>· {{ $member->active_patients_count }} activos</small></dd></div>
            <div><dt>Planes alimenticios activos</dt><dd>{{ $member->active_diet_plans_count }}</dd></div>
            <div><dt>Consultas este mes</dt><dd>{{ $member->consultations_month_count }}</dd></div>
        </dl>
    </section>

    <section class="nl-profile-card">
        <header class="nl-section-head">
            <div>
                <span class="nl-eyebrow">Agenda</span>
                <h2>Citas por estado</h2>
            </div>
            <a href="{{ route('clinic.agenda.index', ['nutriologo' => $member->id]) }}" class="nl-btn-ghost">
                Ver su agenda <i class="fas fa-arrow-right" aria-hidden="true"></i>
            </a>
        </header>
        <div class="nl-stats">
            @foreach ($statusCounts as $status => $count)
                <div class="nl-stat">
                    <strong>{{ $count }}</strong>
                    <span>{{ $status }}</span>
                </div>
            @endforeach
        </div>

        @if ($upcoming->isNotEmpty())
            <ul class="nl-list-plain nl-mt-18">
                @foreach ($upcoming as $appointment)
                    <li>
                        <div>
                            <strong>{{ $appointment->patient?->first_name }} {{ $appointment->patient?->last_name }}</strong>
                            <small>{{ $appointment->reason ?: 'Sin motivo' }} · {{ $appointment->status }}</small>
                        </div>
                        <small>{{ $appointment->appointment_at->format('d/m/Y H:i') }}</small>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <section class="nl-profile-card">
        <header class="nl-section-head">
            <div>
                <span class="nl-eyebrow">Expedientes</span>
                <h2>Pacientes de {{ $member->name }}</h2>
            </div>
            <a href="{{ route('clinic.patients.index', ['nutriologo' => $member->id]) }}" class="nl-btn-ghost">
                Ver en pacientes de la clínica <i class="fas fa-arrow-right" aria-hidden="true"></i>
            </a>
        </header>

        @if ($patients->isEmpty())
            <p class="nl-empty"><i class="fas fa-users" aria-hidden="true"></i>Este nutriólogo aún no registra pacientes.</p>
        @else
            <div class="table-responsive">
                <table class="nl-table">
                    <thead>
                        <tr>
                            <th scope="col">Paciente</th>
                            <th scope="col">Estado</th>
                            <th scope="col" class="is-num">Citas</th>
                            <th scope="col" class="is-num">Planes activos</th>
                            <th scope="col">Registro</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($patients as $patient)
                            <tr>
                                <td data-label="Paciente"><strong>{{ $patient->first_name }} {{ $patient->last_name }}</strong><small>{{ $patient->email }}</small></td>
                                <td data-label="Estado"><span class="nl-status nl-status--{{ $patient->active ? 'activa' : 'vencida' }}">{{ $patient->active ? 'Activo' : 'Inactivo' }}</span></td>
                                <td data-label="Citas" class="is-num">{{ $patient->appointments_count }}</td>
                                <td data-label="Planes activos" class="is-num">{{ $patient->active_diet_plans_count }}</td>
                                <td data-label="Registro">{{ $fecha($patient->created_at) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    @if ($history->isNotEmpty())
        <section class="nl-profile-card">
            <header class="nl-section-head">
                <div>
                    <span class="nl-eyebrow">Bitácora</span>
                    <h2>Cambios en este subperfil</h2>
                </div>
            </header>
            <ul class="nl-list-plain">
                @foreach ($history as $log)
                    <li>
                        <div><strong>{{ $log->actionLabel() }}</strong><small>{{ $log->user?->name ?? 'Sistema' }}</small></div>
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

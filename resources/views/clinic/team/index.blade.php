@extends('adminlte::page')

{{-- =========================================================
     EQUIPO DE LA CLÍNICA (/clinica/equipo)
     Subperfiles de nutriólogo: cada uno con su propio acceso.
     Datos: ClinicTeamController@index.
     ========================================================= --}}

@section('title', 'Equipo | NutriLife')

@section('content_header')
    <div class="patients-header">
        <div>
            <span class="header-eyebrow">
                <i class="fas fa-leaf me-1"></i>
                NUTRILIFE · CLÍNICA
            </span>
            <h1>Equipo de {{ $clinic->name }}</h1>
            <p>Nutriólogos de tu clínica. Cada uno entra con su propio correo y contraseña.</p>
        </div>

        @if ($canAdd)
            <div class="nl-page-actions">
                <a href="{{ route('clinic.team.create') }}" class="nl-btn nl-btn--primary">
                    <i class="fas fa-user-plus" aria-hidden="true"></i>
                    Agregar nutriólogo
                </a>
            </div>
        @endif
    </div>
@stop

@section('content')
<div class="nl-profile">

    @include('partials.flash')

    @unless ($licenseActive)
        <div class="nl-alert nl-alert--error" role="alert">
            <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
            <div>La licencia Consultorio no está vigente: los subperfiles no pueden entrar y no se pueden agregar nuevos.</div>
        </div>
    @endunless

    <section class="nl-profile-card">
        <header class="nl-section-head">
            <div>
                <span class="nl-eyebrow">
                    {{ $members->count() }} {{ $members->count() === 1 ? 'subperfil' : 'subperfiles' }}
                    @if ($memberLimit !== null) · límite {{ $memberLimit }} @endif
                </span>
                <h2>Nutriólogos de la clínica</h2>
            </div>
        </header>

        <p class="nl-muted nl-mb-16">
            Cada subperfil tiene las funciones del plan {{ config('nutrilife.clinic.member_plan') }} (pacientes, citas,
            consultas, planes alimenticios, catálogos y progreso) con la licencia de la clínica, sin cobros individuales.
            Cada nutriólogo ve solo sus pacientes; la clínica ve los de todos.
        </p>

        @if ($members->isEmpty())
            <div class="nl-empty">
                <i class="fas fa-user-doctor" aria-hidden="true"></i>
                Todavía no agregas nutriólogos a tu clínica.
                @if ($canAdd)
                    <div class="nl-mt-12">
                        <a href="{{ route('clinic.team.create') }}" class="nl-btn nl-btn--primary">Agregar el primero</a>
                    </div>
                @endif
            </div>
        @else
            <div class="table-responsive">
                <table class="nl-table">
                    <thead>
                        <tr>
                            <th scope="col">Nutriólogo</th>
                            <th scope="col">Cédula / título</th>
                            <th scope="col">Estado</th>
                            <th scope="col" class="is-num">Pacientes</th>
                            <th scope="col" class="is-num">Próximas citas</th>
                            <th scope="col">Alta</th>
                            <th scope="col"><span class="visually-hidden">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($members as $member)
                            <tr>
                                <td data-label="Nutriólogo">
                                    <strong>{{ $member->name }}</strong>
                                    <small>{{ $member->email }}</small>
                                </td>
                                <td data-label="Cédula / título">
                                    {{ $member->professional_license ?: 'No registrado' }}
                                    <small>{{ $member->degree_number ?: 'Título no registrado' }}</small>
                                </td>
                                <td data-label="Estado">
                                    <span class="nl-status nl-status--{{ $member->account_status }}">{{ $member->accountStatusLabel() }}</span>
                                </td>
                                <td data-label="Pacientes" class="is-num">
                                    {{ $member->patients_count }}
                                    <small>{{ $member->active_patients_count }} activos</small>
                                </td>
                                <td data-label="Próximas citas" class="is-num">{{ $member->upcoming_appointments_count }}</td>
                                <td data-label="Alta">{{ $member->created_at?->format('d/m/Y') }}</td>
                                <td data-label="Acciones">
                                    <div class="nl-row-actions">
                                        <a href="{{ route('clinic.team.show', $member->id) }}" class="nl-btn nl-btn--ghost nl-btn--sm">
                                            <i class="far fa-eye" aria-hidden="true"></i> Ver
                                        </a>
                                        <a href="{{ route('clinic.team.edit', $member->id) }}" class="nl-btn nl-btn--ghost nl-btn--sm">
                                            <i class="fas fa-pen" aria-hidden="true"></i> Editar
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

</div>
@stop

@section('css')
    @include('partials.theme-styles', ['section' => 'admin'])
@stop

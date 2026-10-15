

@extends('adminlte::page')

@section('title', 'Planes alimenticios | NutriLife')

{{-- =========================================================

     CONTENT HEADER

========================================================= --}}

@section('content_header')

<div class="consultations-header">

    <div>

        <span class="consultation-eyebrow">

            <i class="fas fa-leaf mr-1"></i>

            NUTRILIFE

        </span>

        <h1>Planes alimenticios</h1>

        <p>

            Diseña, administra y consulta los planes

            nutricionales de tus pacientes.

        </p>

    </div>

    <a

        href="{{ route('diet-plans.create') }}"

        class="btn-consultation-primary"

    >

        <i class="fas fa-plus"></i>

        Nuevo plan

    </a>

</div>

@stop

{{-- =========================================================

     CONTENT

========================================================= --}}

@section('content')

<div class="consultations-page diet-plans-page">

    {{-- BANNER DE BIENVENIDA: PLANES ALIMENTICIOS --}}
    <section class="diet-welcome-banner" aria-label="Bienvenida al módulo de planes alimenticios">
        <div class="diet-welcome-icon" aria-hidden="true">
            <i class="fas fa-utensils"></i>
        </div>

        <div class="diet-welcome-copy">
            <span class="diet-welcome-eyebrow">NUTRICIÓN PERSONALIZADA</span>
            <h2>Alimenta sus metas, <strong>transforma su bienestar.</strong></h2>
            <p>Diseña y organiza planes alimenticios personalizados para acompañar el progreso de cada paciente.</p>
        </div>

        <div class="diet-welcome-art" aria-hidden="true">
            <i class="fas fa-leaf diet-art-leaf diet-art-leaf-one"></i>
            <i class="fas fa-seedling diet-art-seedling"></i>
            <i class="fas fa-leaf diet-art-leaf diet-art-leaf-two"></i>
        </div>
    </section>


    {{-- MENSAJE DE ÉXITO --}}

    @if(session('success'))

        <div class="consultation-alert">

            <i class="fas fa-check-circle"></i>

            {{ session('success') }}

        </div>

    @endif

    {{-- MÉTRICAS --}}

    <div class="consultation-stats">

        {{-- TOTAL DE PLANES --}}

        <div class="consultation-stat">

            <div class="consultation-stat-icon">

                <i class="fas fa-clipboard-list"></i>

            </div>

            <div>

                <span>Total de planes</span>

                <h3>{{ $plans->count() }}</h3>

            </div>

        </div>

        {{-- PLANES ACTIVOS --}}

        <div class="consultation-stat">

            <div class="consultation-stat-icon today">

                <i class="fas fa-seedling"></i>

            </div>

            <div>

                <span>Planes activos</span>

                <h3>{{ $activePlans }}</h3>

            </div>

        </div>

        {{-- PLANES FINALIZADOS --}}

        <div class="consultation-stat">

            <div class="consultation-stat-icon patients">

                <i class="fas fa-check-circle"></i>

            </div>

            <div>

                <span>Planes finalizados</span>

                <h3>

                    {{ $plans->where('active', false)->count() }}

                </h3>

            </div>

        </div>

    </div>

    {{-- HISTORIAL DE PLANES --}}

    <div class="consultations-card">

        <div class="consultations-card-header">

            <div>

                <span>PLANES NUTRICIONALES</span>

                <h4>

                    Planes alimenticios registrados

                </h4>

            </div>

            <i class="fas fa-utensils"></i>

        </div>

        @if($plans->isEmpty())

            {{-- SIN PLANES --}}

            <div class="empty-consultations">

                <i class="fas fa-utensils"></i>

                <h5>

                    Sin planes alimenticios

                </h5>

                <p>

                    Cuando diseñes un plan nutricional

                    para alguno de tus pacientes,

                    aparecerá registrado aquí.

                </p>

                <a href="{{ route('diet-plans.create') }}">

                    Crear primer plan

                </a>

            </div>

        @else

            {{-- LISTA DE PLANES --}}

            <div class="consultations-list">

                @foreach($plans as $plan)

                    <div class="consultation-item diet-plan-item">

                        {{-- FECHA --}}

                        <div class="consultation-date">

                            <strong>

                                {{ $plan->starts_at->format('d') }}

                            </strong>

                            <span>

                                {{ strtoupper($plan->starts_at->locale('es')->translatedFormat('M')) }}

                            </span>

                        </div>

                        {{-- INFORMACIÓN --}}

                        <div class="consultation-info">

                            <div class="diet-patient-heading">

                                <h5>

                                    {{ $plan->patient->first_name }}

                                    {{ $plan->patient->last_name }}

                                </h5>

                                <span class="diet-plan-status {{ $plan->active ? 'is-active' : 'is-finished' }}">

                                    <i class="fas fa-circle"></i>

                                    {{ $plan->active ? 'Activo' : 'Finalizado' }}

                                </span>

                            </div>

                            <p>

                                {{ $plan->name }}

                            </p>

                            {{-- MACRONUTRIENTES --}}

                            <div class="diet-plan-macros">

                                <span>

                                    <i class="fas fa-fire"></i>

                                    {{ number_format((float) $plan->target_kcal, 0) }}

                                    kcal

                                </span>

                                <span>

                                    <i class="fas fa-dumbbell"></i>

                                    {{ number_format((float) $plan->target_protein_g, 0) }}

                                    g proteína

                                </span>

                                <span>

                                    <i class="fas fa-bread-slice"></i>

                                    {{ number_format((float) $plan->target_carbs_g, 0) }}

                                    g carbohidratos

                                </span>

                                <span>

                                    <i class="fas fa-tint"></i>

                                    {{ number_format((float) $plan->target_lipids_g, 0) }}

                                    g grasas

                                </span>

                            </div>

                            {{-- FECHAS --}}

                            <div class="consultation-meta">

                                <span>

                                    <i class="far fa-calendar"></i>

                                    Inicio:

                                    {{ $plan->starts_at->format('d/m/Y') }}

                                </span>

                                <span>

                                    <i class="far fa-calendar-check"></i>

                                    Fin:

                                    @if($plan->ends_at)

                                        {{ $plan->ends_at->format('d/m/Y') }}

                                    @else

                                        Actualidad

                                    @endif

                                </span>

                            </div>

                        </div>

                        {{-- ACCIONES --}}

                        <div class="consultation-actions">

                            <a

                                href="{{ route('diet-plans.show', $plan) }}"

                                class="diet-plan-view"

                            >

                                <i class="fas fa-eye"></i>

                                Ver / editar plan

                            </a>

                        </div>

                    </div>

                @endforeach

            </div>

        @endif

    </div>

</div>

@stop

{{-- =========================================================

     ESTILOS

========================================================= --}}

@section('css')

    {{-- Mismos estilos del módulo de Consultas --}}

    @include('partials.theme-styles', ['section' => 'admin'])

    {{-- Ajustes exclusivos de planes alimenticios --}}

    <style>
        /* Banner de bienvenida (adaptado a claro y oscuro) */
        .diet-plans-page .diet-welcome-banner {
            position: relative;
            isolation: isolate;
            display: flex;
            align-items: center;
            gap: 24px;
            min-height: 210px;
            padding: 34px 42px;
            margin-bottom: 26px;
            overflow: hidden;
            border: 1px solid rgba(62, 123, 69, .10);
            border-radius: 27px;
            background: linear-gradient(115deg, #fff 0%, #fff 64%, #f5faf2 100%);
            box-shadow: 0 12px 30px rgba(30, 77, 42, .07);
        }
        .diet-plans-page .diet-welcome-icon {
            width: 76px;
            height: 76px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            border: 1px solid rgba(56, 133, 73, .20);
            border-radius: 24px;
            background: linear-gradient(145deg, #dfefdf, #f5f8f3);
            color: #469c53;
            font-size: 29px;
        }
        .diet-plans-page .diet-welcome-copy {
            position: relative;
            z-index: 1;
            max-width: 680px;
            padding-right: 150px;
        }
        .diet-plans-page .diet-welcome-eyebrow {
            display: block;
            margin-bottom: 9px;
            color: #469c53;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 2px;
        }
        .diet-plans-page .diet-welcome-copy h2 {
            margin: 0 0 11px;
            color: #234431;
            font-size: clamp(21px, 2.4vw, 31px);
            font-weight: 500;
            line-height: 1.25;
        }
        .diet-plans-page .diet-welcome-copy h2 strong {
            color: #4aa057;
            font-weight: 700;
        }
        .diet-plans-page .diet-welcome-copy p {
            margin: 0;
            color: #819188;
            font-size: 13px;
            line-height: 1.7;
        }
        .diet-plans-page .diet-welcome-art {
            position: absolute;
            right: 33px;
            top: 50%;
            width: 195px;
            height: 160px;
            transform: translateY(-50%);
            pointer-events: none;
        }
        .diet-plans-page .diet-welcome-art i { position: absolute; }
        .diet-plans-page .diet-art-seedling {
            right: 38px;
            bottom: 4px;
            color: #718c79;
            font-size: 110px;
            opacity: .92;
        }
        .diet-plans-page .diet-art-leaf { color: #4b9f58; font-size: 48px; }
        .diet-plans-page .diet-art-leaf-one { left: 12px; top: 19px; transform: rotate(-30deg); }
        .diet-plans-page .diet-art-leaf-two { right: 0; top: 0; transform: rotate(35deg); }
        /* El esquema oscuro se vincula a las clases / atributos habituales de AdminLTE. */
        body.dark-mode .diet-plans-page .diet-welcome-banner,
        html.dark .diet-plans-page .diet-welcome-banner,
        html[data-theme="dark"] .diet-plans-page .diet-welcome-banner,
        body[data-theme="dark"] .diet-plans-page .diet-welcome-banner {
            background: linear-gradient(120deg, #16291f, #203a2a);
            border-color: rgba(164, 211, 158, .15);
            box-shadow: 0 12px 32px rgba(0, 0, 0, .15);
        }
        body.dark-mode .diet-plans-page .diet-welcome-icon,
        html.dark .diet-plans-page .diet-welcome-icon,
        html[data-theme="dark"] .diet-plans-page .diet-welcome-icon,
        body[data-theme="dark"] .diet-plans-page .diet-welcome-icon {
            background: rgba(159, 212, 154, .10);
            color: #a3d69e;
            border-color: rgba(159, 212, 154, .20);
        }
        body.dark-mode .diet-plans-page .diet-welcome-copy h2,
        html.dark .diet-plans-page .diet-welcome-copy h2,
        html[data-theme="dark"] .diet-plans-page .diet-welcome-copy h2,
        body[data-theme="dark"] .diet-plans-page .diet-welcome-copy h2 { color: #e7f3e8; }
        body.dark-mode .diet-plans-page .diet-welcome-copy p,
        html.dark .diet-plans-page .diet-welcome-copy p,
        html[data-theme="dark"] .diet-plans-page .diet-welcome-copy p,
        body[data-theme="dark"] .diet-plans-page .diet-welcome-copy p { color: #b2c4b5; }
        @media (max-width: 750px) {
            .diet-plans-page .diet-welcome-banner { padding: 25px; gap: 16px; min-height: 0; }
            .diet-plans-page .diet-welcome-icon { width: 55px; height: 55px; font-size: 23px; border-radius: 17px; }
            .diet-plans-page .diet-welcome-copy { padding-right: 0; }
            .diet-plans-page .diet-welcome-art { display: none; }
        }
        @media (max-width: 480px) {
            .diet-plans-page .diet-welcome-banner { align-items: flex-start; flex-direction: column; }
            .diet-plans-page .diet-welcome-copy p { font-size: 12px; }
        }


        .diet-plans-page .diet-plan-item {

            align-items: center;

            gap: 18px;

        }

        .diet-plans-page .consultation-info {

            flex: 1;

            min-width: 0;

        }

        .diet-patient-heading {

            display: flex;

            align-items: center;

            flex-wrap: wrap;

            gap: 12px;

            margin-bottom: 5px;

        }

        .diet-patient-heading h5 {

            margin: 0;

        }

        .diet-plan-status {

            display: inline-flex;

            align-items: center;

            gap: 6px;

            padding: 5px 10px;

            border-radius: 20px;

            font-size: 10px;

            font-weight: 600;

            white-space: nowrap;

        }

        .diet-plan-status i {

            font-size: 6px;

        }

        .diet-plan-status.is-active {

            background: rgba(102, 187, 106, .13);

            color: #8fd19e;

        }

        .diet-plan-status.is-finished {

            background: rgba(148, 163, 184, .12);

            color: #aeb9c6;

        }

        .diet-plan-macros {

            display: flex;

            flex-wrap: wrap;

            align-items: center;

            gap: 8px;

            margin: 12px 0;

        }

        .diet-plan-macros span {

            display: inline-flex;

            align-items: center;

            gap: 6px;

            padding: 7px 10px;

            border: 1px solid rgba(150, 190, 145, .10);

            border-radius: 9px;

            background: rgba(150, 190, 145, .055);

            color: #a9c8ad;

            font-size: 10px;

            font-weight: 500;

        }

        .diet-plan-macros span:nth-child(1) i {

            color: #dfba78;

        }

        .diet-plan-macros span:nth-child(2) i {

            color: #9dcca3;

        }

        .diet-plan-macros span:nth-child(3) i {

            color: #c7d879;

        }

        .diet-plan-macros span:nth-child(4) i {

            color: #8bbcc9;

        }

        .diet-plan-view {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 8px;

            padding: 11px 15px;

            border: 1px solid rgba(168, 207, 160, .18);

            border-radius: 11px;

            background: rgba(151, 195, 145, .08);

            color: #b7d6b0 !important;

            font-size: 11px;

            font-weight: 600;

            text-decoration: none !important;

            white-space: nowrap;

            transition: all .2s ease;

        }

        .diet-plan-view:hover {

            background: #a9ce9f;

            color: #102417 !important;

            transform: translateY(-2px);

        }

        @media (max-width: 850px) {

            .diet-plans-page .diet-plan-item {

                align-items: flex-start;

                flex-wrap: wrap;

            }

            .diet-plans-page .consultation-info {

                flex: 1 1 65%;

            }

            .diet-plans-page .consultation-actions {

                width: 100%;

            }

            .diet-plan-view {

                width: 100%;

            }

        }

        @media (max-width: 520px) {

            .diet-plans-page .consultation-info {

                flex-basis: 100%;

            }

            .diet-plan-macros {

                gap: 6px;

            }

            .diet-plan-macros span {

                font-size: 9px;

                padding: 7px 8px;

            }

        }

    </style>

@stop

@section('js')

@stop

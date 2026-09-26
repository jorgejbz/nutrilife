@extends('adminlte::page')

@section('title', 'Nueva consulta | NutriLife')


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

            <h1>Nueva consulta</h1>

            <p>Registra la atención y recomendaciones del paciente.</p>
        </div>

        <a href="{{ route('consultas.index') }}" class="btn-consultation-primary">
            <i class="fas fa-arrow-left"></i>
            Regresar
        </a>

    </div>

@stop


{{-- =========================================================
     CONTENT
     ========================================================= --}}
@section('content')

    <div class="consultation-form-page">

        {{-- Luces ambientales --}}
        <div class="ambient ambient-one"></div>
        <div class="ambient ambient-two"></div>
        <div class="ambient ambient-three"></div>

        <form method="POST" action="{{ route('consultas.store') }}" id="consultationForm">

            @csrf

            <div class="form-glass-card">

                <div class="glass-shine"></div>


                {{-- ENCABEZADO --}}
                <div class="form-card-header">

                    <div class="form-heading">
                        <div class="form-heading-icon">
                            <i class="fas fa-stethoscope"></i>
                        </div>

                        <div>
                            <span>REGISTRO CLÍNICO</span>
                            <h3>Datos de la consulta</h3>
                            <p>Registra la atención y recomendaciones para el paciente.</p>
                        </div>
                    </div>

                    <div class="required-badge">
                        <span>*</span>
                        Campos obligatorios
                    </div>

                </div>


                {{-- ================================================
                     PACIENTE Y FECHA
                ================================================= --}}
                <div class="form-section">

                    <div class="section-label">
                        <span class="section-number">01</span>
                        <div>
                            <h4>Paciente y horario</h4>
                            <p>¿A quién y cuándo fue la consulta?</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Paciente *</label>

                        <div class="liquid-input liquid-select">
                            <span class="input-icon"><i class="far fa-user"></i></span>

                            <select name="patient_id" required>
                                <option value="">Selecciona un paciente</option>

                                @foreach($patients as $patient)
                                    <option
                                        value="{{ $patient->id }}"
                                        {{ old('patient_id') == $patient->id ? 'selected' : '' }}
                                    >
                                        {{ $patient->first_name }} {{ $patient->last_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Fecha y hora *</label>

                        <div class="liquid-input">
                            <span class="input-icon"><i class="far fa-clock"></i></span>

                            <input
                                type="datetime-local"
                                name="consultation_at"
                                value="{{ old('consultation_at', now()->format('Y-m-d\TH:i')) }}"
                                required
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Cita relacionada</label>

                        <div class="liquid-input liquid-select">
                            <span class="input-icon"><i class="far fa-calendar-check"></i></span>

                            <select name="appointment_id">
                                <option value="">Ninguna</option>

                                @foreach($appointments as $appointment)
                                    <option
                                        value="{{ $appointment->id }}"
                                        {{ old('appointment_id') == $appointment->id ? 'selected' : '' }}
                                    >
                                        {{ $appointment->patient->first_name }}
                                        {{ $appointment->patient->last_name }}
                                        —
                                        {{ $appointment->appointment_at->format('d/m/Y H:i') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <small class="field-hint">
                            Opcional. Si seleccionas una cita, se marcará como completada.
                        </small>
                    </div>

                </div>


                {{-- ================================================
                     MOTIVO Y SEGUIMIENTO
                ================================================= --}}
                <div class="form-section">

                    <div class="section-label">
                        <span class="section-number">02</span>
                        <div>
                            <h4>Motivo y seguimiento</h4>
                            <p>Contexto general de la consulta.</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Motivo</label>

                        <div class="liquid-input">
                            <span class="input-icon"><i class="far fa-comment-dots"></i></span>

                            <input
                                type="text"
                                name="reason"
                                value="{{ old('reason') }}"
                                placeholder="Ej. Seguimiento mensual"
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Próxima consulta</label>

                        <div class="liquid-input">
                            <span class="input-icon"><i class="far fa-calendar-plus"></i></span>

                            <input type="date" name="next_visit" value="{{ old('next_visit') }}">
                        </div>
                    </div>

                </div>


                {{-- ================================================
                     OBSERVACIONES
                ================================================= --}}
                <div class="form-section">

                    <div class="section-label">
                        <span class="section-number">03</span>
                        <div>
                            <h4>Observaciones</h4>
                            <p>¿Cómo se encuentra el paciente?</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="liquid-textarea">
                            <span class="textarea-icon"><i class="far fa-file-alt"></i></span>

                            <textarea
                                name="observations"
                                rows="5"
                                placeholder="Describe cómo se encuentra el paciente, adherencia, síntomas, cambios..."
                                id="observationsField"
                            >{{ old('observations') }}</textarea>

                            <span class="character-counter" id="observationsCount">0</span>
                        </div>
                    </div>

                </div>


                {{-- ================================================
                     RECOMENDACIONES
                ================================================= --}}
                <div class="form-section last-section">

                    <div class="section-label">
                        <span class="section-number">04</span>
                        <div>
                            <h4>Recomendaciones</h4>
                            <p>Indicaciones para el paciente.</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="liquid-textarea">
                            <span class="textarea-icon"><i class="far fa-lightbulb"></i></span>

                            <textarea
                                name="recommendations"
                                rows="5"
                                placeholder="Indicaciones para el paciente..."
                                id="recommendationsField"
                            >{{ old('recommendations') }}</textarea>

                            <span class="character-counter" id="recommendationsCount">0</span>
                        </div>
                    </div>

                </div>


                {{-- ACCIONES --}}
                <div class="form-actions">

                    <a href="{{ route('consultas.index') }}" class="btn-cancel-liquid">
                        Cancelar
                    </a>

                    <button type="submit" class="btn-save-liquid">
                        <span class="save-text">
                            <i class="far fa-save"></i>
                            Guardar consulta
                        </span>

                        <span class="save-arrow">
                            <i class="fas fa-arrow-right"></i>
                        </span>
                    </button>

                </div>

            </div>

        </form>

    </div>

@stop


{{-- =========================================================
     ESTILOS
     ========================================================= --}}
@section('css')

    {{-- Estilos del panel: tokens + admin.css + admin-light/dark.css,
         anti-parpadeo del tema, tipografía e iconos --}}
    @include('partials.theme-styles', ['section' => 'admin'])

@stop


{{-- =========================================================
     SCRIPTS
     ========================================================= --}}
@section('js')



    @vite(['resources/js/pages/consultations-create.js'])

@stop

{{-- =========================================================
     PLANES ALIMENTICIOS — FORMULARIO POR PASOS (crear y editar)
     Diseño de la versión nutrilife_planesAlim (pasos 01 · 02 · 03)
     más los campos de la versión final: objetivo, indicaciones y,
     al editar, el estado Activo / Finalizado.

     Columnas de diet_plans: patient_id (solo al crear), name, goal,
     instructions, starts_at, ends_at, target_kcal DECIMAL(7,2),
     target_protein_g / target_carbs_g / target_lipids_g DECIMAL(6,2)
     y active (solo al editar).

     Variables: $plan (DietPlan|null), $patients y
     $selectedPatientId (solo crear).
     ========================================================= --}}
@php
    $isEdit = isset($plan) && $plan?->exists;

    $macroFields = [
        'target_kcal' => ['class' => 'kcal', 'kicker' => 'ENERGÍA', 'label' => 'Calorías', 'unit' => 'kcal', 'icon' => 'fas fa-fire', 'max' => \App\Models\DietPlan::MAX_KCAL, 'placeholder' => '2000'],
        'target_protein_g' => ['class' => 'protein', 'kicker' => 'MACRONUTRIENTE', 'label' => 'Proteínas', 'unit' => 'g', 'icon' => 'fas fa-dumbbell', 'max' => \App\Models\DietPlan::MAX_MACRO_G, 'placeholder' => '160'],
        'target_carbs_g' => ['class' => 'carbs', 'kicker' => 'MACRONUTRIENTE', 'label' => 'Carbohidratos', 'unit' => 'g', 'icon' => 'fas fa-bread-slice', 'max' => \App\Models\DietPlan::MAX_MACRO_G, 'placeholder' => '220'],
        'target_lipids_g' => ['class' => 'fats', 'kicker' => 'MACRONUTRIENTE', 'label' => 'Grasas', 'unit' => 'g', 'icon' => 'fas fa-tint', 'max' => \App\Models\DietPlan::MAX_MACRO_G, 'placeholder' => '65'],
    ];

    $value = fn (string $field, $default = null) => old($field, $isEdit ? $plan->{$field} : $default);
    $dateValue = fn (string $field, $default = null) => old($field, $isEdit ? $plan->{$field}?->format('Y-m-d') : $default);
    $number = fn ($raw) => $raw === null || $raw === '' ? '' : rtrim(rtrim((string) $raw, '0'), '.');
@endphp

<div class="dp dp-create-page">

    {{-- INTRODUCCIÓN --}}
    <div class="dp-intro">
        <div class="dp-intro-icon">
            <i class="fas fa-clipboard-list"></i>
        </div>

        <div>
            <span>CONFIGURACIÓN NUTRICIONAL</span>
            <h2>{{ $isEdit ? 'Editar plan alimenticio' : 'Crear plan alimenticio' }}</h2>
            <p>
                {{ $isEdit
                    ? 'Ajusta los datos, los objetivos o finaliza el plan.'
                    : 'Completa la información para registrar un nuevo plan. Después agregarás sus comidas.' }}
            </p>
        </div>
    </div>

    {{-- ERRORES DE VALIDACIÓN --}}
    @if($errors->any())
        <div class="dp-validation-alert" role="alert">
            <i class="fas fa-exclamation-circle"></i>
            <div>
                <strong>Revisa la información</strong>
                <p>
                    {{ $errors->count() === 1 ? 'Un campo necesita' : $errors->count().' campos necesitan' }}
                    corregirse antes de {{ $isEdit ? 'guardar' : 'crear' }} el plan.
                </p>
            </div>
        </div>
    @endif

    <form action="{{ $isEdit ? route('diet-plans.update', $plan) : route('diet-plans.store') }}"
          method="POST"
          class="dp-form"
          id="dietPlanForm">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        {{-- =====================================
             01 - PACIENTE
             ===================================== --}}
        <section class="dp-section">
            <div class="dp-section-heading">
                <div class="dp-step-icon"><i class="fas fa-user"></i></div>

                <div class="dp-section-copy">
                    <span>PASO 01 · PACIENTE</span>
                    <h3>{{ $isEdit ? 'Paciente del plan' : 'Seleccionar paciente' }}</h3>
                    <p>
                        {{ $isEdit
                            ? 'El paciente no se cambia: las comidas se diseñaron para él. Para otro paciente usa «Duplicar / asignar».'
                            : 'Elige a quién se asignará este plan alimenticio.' }}
                    </p>
                </div>

                <span class="dp-step-number">01</span>
            </div>

            <div class="dp-section-body">
                @if($isEdit)
                    <div class="dp-fixed-patient">
                        <span class="dp-avatar" aria-hidden="true">
                            {{ mb_strtoupper(mb_substr($plan->patient?->first_name ?? 'P', 0, 1)) }}
                        </span>
                        <div>
                            <strong>{{ $plan->patient?->first_name }} {{ $plan->patient?->last_name }}</strong>
                            @if($plan->patient?->email)
                                <small>{{ $plan->patient->email }}</small>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="dp-field">
                        <label for="patient_id">
                            <i class="far fa-user"></i>
                            Paciente
                            <span class="dp-required">*</span>
                        </label>

                        <div class="dp-select-wrapper">
                            <select name="patient_id"
                                    id="patient_id"
                                    class="dp-input @error('patient_id') is-invalid @enderror"
                                    required
                                    @error('patient_id') aria-invalid="true" aria-describedby="patient_id_error" @enderror>
                                <option value="">Seleccionar paciente...</option>
                                @foreach($patients as $patient)
                                    <option value="{{ $patient->id }}" @selected((string) old('patient_id', $selectedPatientId) === (string) $patient->id)>{{ $patient->first_name }} {{ $patient->last_name }}</option>
                                @endforeach
                            </select>
                            <i class="fas fa-chevron-down"></i>
                        </div>

                        @error('patient_id')
                            <span class="dp-error" id="patient_id_error">{{ $message }}</span>
                        @enderror

                        @if($patients->isEmpty())
                            <small>
                                No tienes pacientes activos.
                                <a href="{{ route('pacientes.create') }}">Registra uno</a> para crearle un plan.
                            </small>
                        @else
                            <small>Selecciona un paciente previamente registrado (solo aparecen tus pacientes activos).</small>
                        @endif
                    </div>
                @endif
            </div>
        </section>

        {{-- =====================================
             02 - INFORMACIÓN DEL PLAN
             ===================================== --}}
        <section class="dp-section">
            <div class="dp-section-heading">
                <div class="dp-step-icon"><i class="fas fa-file-medical"></i></div>

                <div class="dp-section-copy">
                    <span>PASO 02 · INFORMACIÓN</span>
                    <h3>Detalles del plan</h3>
                    <p>Define el nombre, el objetivo y el periodo de seguimiento nutricional.</p>
                </div>

                <span class="dp-step-number">02</span>
            </div>

            <div class="dp-section-body">
                <div class="dp-field">
                    <label for="name">
                        <i class="fas fa-pen"></i>
                        Nombre del plan
                        <span class="dp-required">*</span>
                    </label>

                    <input type="text"
                           name="name"
                           id="name"
                           class="dp-input @error('name') is-invalid @enderror"
                           value="{{ $value('name') }}"
                           maxlength="255"
                           placeholder="Ej. Plan de déficit calórico - 1800 kcal"
                           required>

                    @error('name')
                        <span class="dp-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="dp-field" id="goal">
                    <label for="goal_input">
                        <i class="fas fa-bullseye"></i>
                        Objetivo del plan
                    </label>

                    <input type="text"
                           name="goal"
                           id="goal_input"
                           class="dp-input @error('goal') is-invalid @enderror"
                           value="{{ $value('goal') }}"
                           maxlength="255"
                           placeholder="Ej. Pérdida de grasa gradual con control de glucosa">

                    @error('goal')
                        <span class="dp-error">{{ $message }}</span>
                    @enderror

                    <small>Opcional.</small>
                </div>

                <div class="dp-field-grid">
                    <div class="dp-field">
                        <label for="starts_at">
                            <i class="far fa-calendar-alt"></i>
                            Fecha de inicio
                            <span class="dp-required">*</span>
                        </label>

                        <input type="date"
                               name="starts_at"
                               id="starts_at"
                               class="dp-input @error('starts_at') is-invalid @enderror"
                               value="{{ $dateValue('starts_at', now()->format('Y-m-d')) }}"
                               min="2000-01-01"
                               max="2100-12-31"
                               required>

                        @error('starts_at')
                            <span class="dp-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="dp-field">
                        <label for="ends_at">
                            <i class="far fa-calendar-check"></i>
                            Fecha de finalización
                        </label>

                        <input type="date"
                               name="ends_at"
                               id="ends_at"
                               class="dp-input @error('ends_at') is-invalid @enderror"
                               value="{{ $dateValue('ends_at') }}"
                               min="{{ $dateValue('starts_at', now()->format('Y-m-d')) }}"
                               max="2100-12-31">

                        @error('ends_at')
                            <span class="dp-error">{{ $message }}</span>
                        @enderror

                        <small>Opcional</small>
                    </div>
                </div>

                @if($isEdit)
                    <div class="dp-field nl-mt-19">
                        <input type="hidden" name="active" value="0">
                        <label class="dp-switch" for="planActive">
                            <input type="checkbox" name="active" value="1" id="planActive" @checked(old('active', $plan->active))>
                            <span class="dp-switch-track" aria-hidden="true"></span>
                            <span>
                                <strong id="planActiveText" data-on="Plan activo" data-off="Plan finalizado">
                                    {{ old('active', $plan->active) ? 'Plan activo' : 'Plan finalizado' }}
                                </strong>
                                <small>Desactívalo cuando el paciente termine este plan.</small>
                            </span>
                        </label>
                    </div>
                @endif
            </div>
        </section>

        {{-- =====================================
             03 - OBJETIVOS NUTRICIONALES
             ===================================== --}}
        <section class="dp-section">
            <div class="dp-section-heading">
                <div class="dp-step-icon"><i class="fas fa-chart-pie"></i></div>

                <div class="dp-section-copy">
                    <span>PASO 03 · NUTRICIÓN</span>
                    <h3>Objetivos nutricionales</h3>
                    <p>Establece las calorías y los macronutrientes diarios.</p>
                </div>

                <span class="dp-step-number">03</span>
            </div>

            <div class="dp-section-body">
                <div class="dp-macro-grid">
                    @foreach($macroFields as $field => $meta)
                        <div class="dp-macro-card {{ $meta['class'] }}">
                            <div class="dp-macro-top">
                                <div class="dp-macro-icon"><i class="{{ $meta['icon'] }}"></i></div>
                                <span>{{ $meta['kicker'] }}</span>
                            </div>

                            <label for="{{ $field }}">
                                {{ $meta['label'] }}
                                <span class="dp-required">*</span>
                            </label>

                            <div class="dp-macro-input @error($field) is-invalid @enderror">
                                <input type="number"
                                       name="{{ $field }}"
                                       id="{{ $field }}"
                                       value="{{ $number($value($field)) }}"
                                       placeholder="{{ $meta['placeholder'] }}"
                                       step="0.01"
                                       min="0"
                                       max="{{ $meta['max'] }}"
                                       inputmode="decimal"
                                       data-macro="{{ $field }}"
                                       required>
                                <span>{{ $meta['unit'] }}</span>
                            </div>

                            @error($field)
                                <span class="dp-error">{{ $message }}</span>
                            @enderror
                        </div>
                    @endforeach
                </div>

                <div class="dp-info-note">
                    <i class="fas fa-info-circle"></i>
                    <p>Los valores registrados serán utilizados como objetivos nutricionales del plan.</p>
                </div>

                {{-- Lo llena diet-plans.js: kcal que aportan los macros
                     (4 kcal/g proteína y carbohidratos, 9 kcal/g grasa). --}}
                <div class="dp-info-note" id="macroCheck" aria-live="polite" hidden>
                    <i class="fas fa-calculator"></i>
                    <p id="macroCheckText"></p>
                </div>
            </div>
        </section>

        {{-- =====================================
             04 - INDICACIONES
             ===================================== --}}
        <section class="dp-section" id="instructions">
            <div class="dp-section-heading">
                <div class="dp-step-icon"><i class="far fa-clipboard"></i></div>

                <div class="dp-section-copy">
                    <span>PASO 04 · INDICACIONES</span>
                    <h3>Indicaciones para el paciente</h3>
                    <p>Recomendaciones generales del plan (hidratación, horarios, preparación…).</p>
                </div>

                <span class="dp-step-number">04</span>
            </div>

            <div class="dp-section-body">
                <div class="dp-field">
                    <label for="instructions_input">
                        <i class="far fa-file-alt"></i>
                        Indicaciones
                    </label>

                    <textarea name="instructions"
                              id="instructions_input"
                              class="dp-input @error('instructions') is-invalid @enderror"
                              rows="4"
                              maxlength="5000"
                              placeholder="Ej. Tomar 2 L de agua al día. Cenar 2 horas antes de dormir.">{{ $value('instructions') }}</textarea>

                    @error('instructions')
                        <span class="dp-error">{{ $message }}</span>
                    @enderror

                    <small>Opcional. Una indicación por línea.</small>
                </div>
            </div>
        </section>

        {{-- =====================================
             ACCIONES
             ===================================== --}}
        <div class="dp-form-footer">
            <div class="dp-footer-info">
                <i class="fas fa-shield-alt"></i>
                <span>Completa los campos obligatorios para {{ $isEdit ? 'guardar' : 'registrar' }} el plan.</span>
            </div>

            <div class="dp-footer-buttons">
                <a href="{{ $isEdit ? route('diet-plans.show', $plan) : route('diet-plans.index') }}" class="dp-cancel-button">
                    Cancelar
                </a>

                <button type="submit"
                        class="dp-save-button"
                        data-loading-text="{{ $isEdit ? 'Guardando…' : 'Creando…' }}"
                        @disabled(! $isEdit && $patients->isEmpty())>
                    <i class="fas fa-check"></i>
                    {{ $isEdit ? 'Guardar cambios' : 'Crear plan' }}
                    <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>
    </form>
</div>

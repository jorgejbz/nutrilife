{{-- =========================================================
     PLANES ALIMENTICIOS — FORMULARIO (crear y editar)
     Columnas de diet_plans: patient_id (solo al crear), name,
     goal e instructions (opcionales, actualización 3.1),
     starts_at, ends_at, target_kcal DECIMAL(7,2),
     target_protein_g / target_carbs_g / target_lipids_g DECIMAL(6,2)
     y active (solo al editar).

     Variables: $plan (DietPlan|null), $patients (solo crear),
     $selectedPatientId (solo crear).
     ========================================================= --}}
@php
    $isEdit = isset($plan) && $plan->exists;

    $macroFields = [
        'target_kcal' => ['label' => 'Calorías', 'unit' => 'kcal', 'icon' => 'fas fa-fire', 'max' => \App\Models\DietPlan::MAX_KCAL, 'placeholder' => '1800'],
        'target_protein_g' => ['label' => 'Proteínas', 'unit' => 'g', 'icon' => 'fas fa-drumstick-bite', 'max' => \App\Models\DietPlan::MAX_MACRO_G, 'placeholder' => '100'],
        'target_carbs_g' => ['label' => 'Carbohidratos', 'unit' => 'g', 'icon' => 'fas fa-bread-slice', 'max' => \App\Models\DietPlan::MAX_MACRO_G, 'placeholder' => '200'],
        'target_lipids_g' => ['label' => 'Grasas', 'unit' => 'g', 'icon' => 'fas fa-tint', 'max' => \App\Models\DietPlan::MAX_MACRO_G, 'placeholder' => '60'],
    ];

    $value = fn (string $field, $default = null) => old($field, $isEdit ? $plan->{$field} : $default);
    $dateValue = fn (string $field, $default = null) => old($field, $isEdit ? $plan->{$field}?->format('Y-m-d') : $default);
@endphp

<div class="consultation-form-page">

    <div class="ambient ambient-one"></div>
    <div class="ambient ambient-two"></div>
    <div class="ambient ambient-three"></div>

    @if($errors->any())
        <div class="diet-form-alert" role="alert">
            <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
            <div>
                <strong>Revisa la información</strong>
                <span>Hay {{ $errors->count() === 1 ? 'un campo' : $errors->count().' campos' }} por corregir.</span>
            </div>
        </div>
    @endif

    <form method="POST"
          action="{{ $isEdit ? route('diet-plans.update', $plan) : route('diet-plans.store') }}"
          id="dietPlanForm">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <div class="form-glass-card">
            <div class="glass-shine"></div>

            {{-- ENCABEZADO --}}
            <div class="form-card-header">
                <div class="form-heading">
                    <div class="form-heading-icon">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <div>
                        <span>PLAN ALIMENTICIO</span>
                        <h3>{{ $isEdit ? 'Datos del plan' : 'Nuevo plan alimenticio' }}</h3>
                        <p>
                            {{ $isEdit
                                ? 'Ajusta el nombre, las fechas, los objetivos o finaliza el plan.'
                                : 'Después de guardarlo podrás agregar las comidas y sus alimentos.' }}
                        </p>
                    </div>
                </div>

                <div class="required-badge">
                    <span>*</span>
                    Campos obligatorios
                </div>
            </div>

            {{-- 01 · PACIENTE --}}
            <div class="form-section">
                <div class="section-label">
                    <span class="section-number">01</span>
                    <div>
                        <h4>Paciente</h4>
                        <p>{{ $isEdit ? 'El paciente no se cambia: las comidas se diseñaron para él.' : '¿Para quién es el plan?' }}</p>
                    </div>
                </div>

                @if($isEdit)
                    <div class="diet-fixed-patient">
                        <span class="diet-avatar" aria-hidden="true">
                            {{ mb_strtoupper(mb_substr($plan->patient?->first_name ?? 'P', 0, 1)) }}
                        </span>
                        <div>
                            <strong>{{ $plan->patient?->first_name }} {{ $plan->patient?->last_name }}</strong>
                            <small>{{ $plan->patient?->email }}</small>
                        </div>
                    </div>
                @else
                    <div class="form-group">
                        <label for="patient_id">Paciente <span class="required">*</span></label>
                        <div class="liquid-input liquid-select @error('patient_id') input-error @enderror">
                            <span class="input-icon"><i class="far fa-user"></i></span>
                            <select name="patient_id" id="patient_id" required @error('patient_id') aria-invalid="true" aria-describedby="patient_id_error" @enderror>
                                <option value="">Selecciona un paciente</option>
                                @foreach($patients as $patient)
                                    <option value="{{ $patient->id }}" @selected((string) old('patient_id', $selectedPatientId) === (string) $patient->id)>
                                        {{ $patient->first_name }} {{ $patient->last_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        @if($patients->isEmpty())
                            <small class="field-hint">
                                No tienes pacientes activos.
                                <a href="{{ route('pacientes.create') }}">Registra uno</a> para crearle un plan.
                            </small>
                        @else
                            <small class="field-hint">Solo aparecen tus pacientes activos.</small>
                        @endif

                        @error('patient_id')
                            <div class="field-error" id="patient_id_error">
                                <i class="fas fa-exclamation-circle"></i>
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                @endif
            </div>

            {{-- 02 · DATOS DEL PLAN --}}
            <div class="form-section">
                <div class="section-label">
                    <span class="section-number">02</span>
                    <div>
                        <h4>Datos del plan</h4>
                        <p>Nombre y periodo de seguimiento.</p>
                    </div>
                </div>

                <div class="form-group">
                    <label for="name">Nombre del plan <span class="required">*</span></label>
                    <div class="liquid-input @error('name') input-error @enderror">
                        <span class="input-icon"><i class="fas fa-pen"></i></span>
                        <input type="text"
                               name="name"
                               id="name"
                               value="{{ $value('name') }}"
                               maxlength="255"
                               placeholder="Ej. Déficit moderado 1,800 kcal"
                               required>
                    </div>
                    @error('name')
                        <div class="field-error">
                            <i class="fas fa-exclamation-circle"></i>
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="goal">Objetivo del plan</label>
                    <div class="liquid-input @error('goal') input-error @enderror">
                        <span class="input-icon"><i class="fas fa-bullseye"></i></span>
                        <input type="text"
                               name="goal"
                               id="goal"
                               value="{{ $value('goal') }}"
                               maxlength="255"
                               placeholder="Ej. Pérdida de grasa gradual con control de glucosa">
                    </div>
                    @if(! $isEdit && $patients->isNotEmpty())
                        <small class="field-hint">Opcional. Tip: el objetivo registrado en la ficha del paciente aparece en el constructor del plan.</small>
                    @endif
                    @error('goal')
                        <div class="field-error">
                            <i class="fas fa-exclamation-circle"></i>
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="diet-form-grid">
                    <div class="form-group">
                        <label for="starts_at">Inicio <span class="required">*</span></label>
                        <div class="liquid-input @error('starts_at') input-error @enderror">
                            <span class="input-icon"><i class="far fa-calendar-alt"></i></span>
                            <input type="date"
                                   name="starts_at"
                                   id="starts_at"
                                   value="{{ $dateValue('starts_at', now()->format('Y-m-d')) }}"
                                   min="2000-01-01"
                                   max="2100-12-31"
                                   required>
                        </div>
                        @error('starts_at')
                            <div class="field-error">
                                <i class="fas fa-exclamation-circle"></i>
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="ends_at">Fin</label>
                        <div class="liquid-input @error('ends_at') input-error @enderror">
                            <span class="input-icon"><i class="far fa-calendar-check"></i></span>
                            <input type="date"
                                   name="ends_at"
                                   id="ends_at"
                                   value="{{ $dateValue('ends_at') }}"
                                   min="{{ $dateValue('starts_at', now()->format('Y-m-d')) }}"
                       max="2100-12-31">
                        </div>
                        <small class="field-hint">Opcional. Déjalo vacío si el plan no tiene fecha de cierre.</small>
                        @error('ends_at')
                            <div class="field-error">
                                <i class="fas fa-exclamation-circle"></i>
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                </div>

                @if($isEdit)
                    <div class="form-group mb-0">
                        <label>Estado del plan</label>
                        <div class="status-control">
                            <input type="hidden" name="active" value="0">
                            <label class="liquid-switch">
                                <input type="checkbox" name="active" value="1" id="planActive" @checked(old('active', $plan->active))>
                                <span class="switch-slider"></span>
                                <span class="switch-text">
                                    <strong id="planActiveText"
                                            data-on="Plan activo"
                                            data-off="Plan finalizado">
                                        {{ old('active', $plan->active) ? 'Plan activo' : 'Plan finalizado' }}
                                    </strong>
                                    <small>Desactívalo cuando el paciente termine este plan.</small>
                                </span>
                            </label>
                        </div>
                    </div>
                @endif
            </div>

            {{-- INDICACIONES --}}
            <div class="form-section">
                <div class="section-label">
                    <span class="section-number">03</span>
                    <div>
                        <h4>Indicaciones para el paciente</h4>
                        <p>Recomendaciones generales del plan (hidratación, horarios, preparación…).</p>
                    </div>
                </div>

                <div class="form-group mb-0">
                    <label for="instructions" class="visually-hidden">Indicaciones</label>
                    <div class="liquid-textarea @error('instructions') input-error @enderror">
                        <span class="textarea-icon"><i class="far fa-file-alt"></i></span>
                        <textarea name="instructions"
                                  id="instructions"
                                  rows="4"
                                  maxlength="5000"
                                  placeholder="Ej. Tomar 2 L de agua al día. Cenar 2 horas antes de dormir.">{{ $value('instructions') }}</textarea>
                    </div>
                    <small class="field-hint">Opcional. Una indicación por línea.</small>
                    @error('instructions')
                        <div class="field-error">
                            <i class="fas fa-exclamation-circle"></i>
                            {{ $message }}
                        </div>
                    @enderror
                </div>
            </div>

            {{-- 03 · OBJETIVOS DIARIOS --}}
            <div class="form-section last-section">
                <div class="section-label">
                    <span class="section-number">04</span>
                    <div>
                        <h4>Objetivos diarios</h4>
                        <p>Calorías y macronutrientes que debe cubrir un día del plan.</p>
                    </div>
                </div>

                <div class="diet-form-grid diet-form-grid--macros">
                    @foreach($macroFields as $field => $meta)
                        <div class="form-group">
                            <label for="{{ $field }}">{{ $meta['label'] }} <span class="required">*</span></label>
                            <div class="liquid-input diet-unit-input @error($field) input-error @enderror">
                                <span class="input-icon"><i class="{{ $meta['icon'] }}"></i></span>
                                <input type="number"
                                       name="{{ $field }}"
                                       id="{{ $field }}"
                                       value="{{ $value($field) !== null ? rtrim(rtrim((string) $value($field), '0'), '.') : '' }}"
                                       min="0"
                                       max="{{ $meta['max'] }}"
                                       step="0.01"
                                       inputmode="decimal"
                                       placeholder="{{ $meta['placeholder'] }}"
                                       data-macro="{{ $field }}"
                                       required>
                                <span class="diet-unit" aria-hidden="true">{{ $meta['unit'] }}</span>
                            </div>
                            @error($field)
                                <div class="field-error">
                                    <i class="fas fa-exclamation-circle"></i>
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    @endforeach
                </div>

                {{-- Lo llena diet-plans-form.js: kcal que aportan los macros
                     (4 kcal/g proteína y carbohidratos, 9 kcal/g grasa). --}}
                <div class="diet-macro-check" id="macroCheck" aria-live="polite" hidden>
                    <div class="diet-macro-split" aria-hidden="true">
                        <span class="is-protein" data-split="protein"></span>
                        <span class="is-carbs" data-split="carbs"></span>
                        <span class="is-lipids" data-split="lipids"></span>
                    </div>
                    <p id="macroCheckText"></p>
                </div>
            </div>

            {{-- ACCIONES --}}
            <div class="form-actions">
                <a href="{{ $isEdit ? route('diet-plans.show', $plan) : route('diet-plans.index') }}" class="btn-cancel-liquid">
                    Cancelar
                </a>
                <button type="submit" class="btn-save-liquid">
                    <span class="save-text">
                        <i class="far fa-save"></i>
                        {{ $isEdit ? 'Guardar cambios' : 'Crear y armar comidas' }}
                    </span>
                    <span class="save-arrow">
                        <i class="fas fa-arrow-right"></i>
                    </span>
                </button>
            </div>
        </div>
    </form>
</div>

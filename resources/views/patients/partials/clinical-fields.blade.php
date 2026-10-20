{{-- =========================================================
     HISTORIAL CLÍNICO (crear y editar paciente)
     Tablas pivote de la BD:
       patient_pathologies (pathology_id, diagnosed_at, notes)
       patient_allergens   (allergen_id, severity: leve|moderada|severa)
     Catálogos: pathologies / allergens (ClinicalCatalogSeeder).
     Campos: pathologies[ID][selected|diagnosed_at|notes]
             allergens[ID][selected|severity]
     Validación y guardado: PatientController (clinicalRules / sync).
     Interacción: resources/js/pages/patients-clinical.js

     Variables: $pathologies, $allergens (catálogos),
                $paciente (solo en editar), $sectionNumber
     ========================================================= --}}
@php
    // Lo que debe aparecer marcado: lo enviado (si hubo error de validación)
    // o, al editar, lo guardado en la BD.
    $fromOld = old('_clinical') !== null;

    $currentPathologies = $fromOld
        ? collect(old('pathologies', []))->filter(fn ($item) => ! empty($item['selected']))
        : collect(isset($paciente) ? $paciente->pathologies->mapWithKeys(fn ($pathology) => [
            $pathology->id => [
                'selected' => 1,
                'diagnosed_at' => $pathology->pivot->diagnosed_at ? substr($pathology->pivot->diagnosed_at, 0, 10) : null,
                'notes' => $pathology->pivot->notes,
            ],
        ]) : []);

    $currentAllergens = $fromOld
        ? collect(old('allergens', []))->filter(fn ($item) => ! empty($item['selected']))
        : collect(isset($paciente) ? $paciente->allergens->mapWithKeys(fn ($allergen) => [
            $allergen->id => ['selected' => 1, 'severity' => $allergen->pivot->severity],
        ]) : []);

    $severities = ['leve' => 'Leve', 'moderada' => 'Moderada', 'severa' => 'Severa'];
@endphp

<div class="form-section last-section clinical-section" data-clinical>

    {{-- Marca que este bloque se envió (para repoblar aunque no se marque nada) --}}
    <input type="hidden" name="_clinical" value="1">

    <div class="section-label">
        <span class="section-number">{{ $sectionNumber ?? '04' }}</span>
        <div>
            <h4>Historial clínico</h4>
            <p>Patologías diagnosticadas y alergias o intolerancias alimentarias.</p>
        </div>
    </div>


    {{-- ------------------------------ PATOLOGÍAS ------------------------------ --}}
    <div class="clinical-block">

        <div class="clinical-block-head">
            <div>
                <h5><i class="fas fa-notes-medical" aria-hidden="true"></i> Patologías</h5>
                <p>Marca las que tenga el paciente. La fecha de diagnóstico y las notas son opcionales.</p>
            </div>
            <span class="clinical-count" data-clinical-count="pathologies">
                {{ $currentPathologies->count() }} seleccionadas
            </span>
        </div>

        @error('pathologies')
            <div class="field-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
        @enderror

        @if ($pathologies->isEmpty())
            <div class="clinical-empty">
                <i class="fas fa-circle-info" aria-hidden="true"></i>
                El catálogo de patologías está vacío. Ejecuta
                <code>database/sql/catalogos_clinicos.sql</code> (o
                <code>php artisan db:seed --class=ClinicalCatalogSeeder</code>).
            </div>
        @else
            <div class="clinical-grid">
                @foreach ($pathologies as $pathology)
                    @php
                        $item = $currentPathologies->get($pathology->id);
                        $checked = $item !== null;
                        $base = 'pathologies.'.$pathology->id;
                        $hasError = $errors->has($base.'.diagnosed_at') || $errors->has($base.'.notes');
                    @endphp

                    <div class="clinical-option {{ $checked ? 'is-selected' : '' }} {{ $hasError ? 'has-error' : '' }}" data-clinical-option>

                        <label class="clinical-check">
                            <input
                                type="checkbox"
                                name="pathologies[{{ $pathology->id }}][selected]"
                                value="1"
                                @checked($checked)
                                data-clinical-toggle="pathologies"
                            >
                            <span class="clinical-check-box" aria-hidden="true"><i class="fas fa-check"></i></span>
                            <span class="clinical-check-text">
                                <strong>{{ $pathology->name }}</strong>
                                @if ($pathology->description)
                                    <small>{{ $pathology->description }}</small>
                                @endif
                            </span>
                        </label>

                        <div class="clinical-details" @unless ($checked) hidden @endunless>
                            <div class="clinical-detail-row">
                                <div class="clinical-detail">
                                    <label for="pathology_{{ $pathology->id }}_date">Fecha de diagnóstico</label>
                                    <input
                                        type="date"
                                        id="pathology_{{ $pathology->id }}_date"
                                        name="pathologies[{{ $pathology->id }}][diagnosed_at]"
                                        value="{{ $item['diagnosed_at'] ?? '' }}"
                                        min="1900-01-01"
                                        max="{{ now()->format('Y-m-d') }}"
                                        @disabled(! $checked)
                                    >
                                </div>
                                <div class="clinical-detail clinical-detail--grow">
                                    <label for="pathology_{{ $pathology->id }}_notes">Notas</label>
                                    <input
                                        type="text"
                                        id="pathology_{{ $pathology->id }}_notes"
                                        name="pathologies[{{ $pathology->id }}][notes]"
                                        value="{{ $item['notes'] ?? '' }}"
                                        maxlength="1000"
                                        placeholder="Ej. Controlada con metformina"
                                        @disabled(! $checked)
                                    >
                                </div>
                            </div>
                            @foreach (['diagnosed_at', 'notes'] as $sub)
                                @error($base.'.'.$sub)
                                    <div class="field-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
                                @enderror
                            @endforeach
                        </div>

                    </div>
                @endforeach
            </div>
        @endif
    </div>


    {{-- ------------------------------ ALERGIAS ------------------------------ --}}
    <div class="clinical-block">

        <div class="clinical-block-head">
            <div>
                <h5><i class="fas fa-triangle-exclamation" aria-hidden="true"></i> Alergias e intolerancias</h5>
                <p>Marca los alimentos que el paciente debe evitar e indica qué tan severa es la reacción.</p>
            </div>
            <span class="clinical-count" data-clinical-count="allergens">
                {{ $currentAllergens->count() }} seleccionadas
            </span>
        </div>

        @error('allergens')
            <div class="field-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
        @enderror

        @if ($allergens->isEmpty())
            <div class="clinical-empty">
                <i class="fas fa-circle-info" aria-hidden="true"></i>
                El catálogo de alérgenos está vacío. Ejecuta
                <code>database/sql/catalogos_clinicos.sql</code>.
            </div>
        @else
            <div class="clinical-grid clinical-grid--compact">
                @foreach ($allergens as $allergen)
                    @php
                        $item = $currentAllergens->get($allergen->id);
                        $checked = $item !== null;
                        $severityError = $errors->first('allergens.'.$allergen->id.'.severity');
                    @endphp

                    <div class="clinical-option {{ $checked ? 'is-selected' : '' }} {{ $severityError ? 'has-error' : '' }}" data-clinical-option>

                        <label class="clinical-check">
                            <input
                                type="checkbox"
                                name="allergens[{{ $allergen->id }}][selected]"
                                value="1"
                                @checked($checked)
                                data-clinical-toggle="allergens"
                            >
                            <span class="clinical-check-box" aria-hidden="true"><i class="fas fa-check"></i></span>
                            <span class="clinical-check-text">
                                <strong>{{ $allergen->name }}</strong>
                            </span>
                        </label>

                        <div class="clinical-details" @unless ($checked) hidden @endunless>
                            <fieldset class="severity-group">
                                <legend>Severidad</legend>
                                @foreach ($severities as $value => $label)
                                    <label class="severity-option severity-option--{{ $value }}">
                                        <input
                                            type="radio"
                                            name="allergens[{{ $allergen->id }}][severity]"
                                            value="{{ $value }}"
                                            @checked(($item['severity'] ?? null) === $value)
                                            @disabled(! $checked)
                                        >
                                        <span>{{ $label }}</span>
                                    </label>
                                @endforeach
                            </fieldset>
                            @if ($severityError)
                                <div class="field-error"><i class="fas fa-exclamation-circle"></i> {{ $severityError }}</div>
                            @endif
                        </div>

                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <p class="clinical-note">
        <i class="fas fa-shield-halved" aria-hidden="true"></i>
        Esta información es parte del expediente clínico y solo la ves tú.
    </p>

</div>

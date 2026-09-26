{{-- =========================================================
     EVALUATIONS CREATE — FORMULARIO
     Composición corporal, medidas y observaciones
     ========================================================= --}}

            {{-- =====================================================
                 FORMULARIO
            ====================================================== --}}

            <div class="evaluation-form-glass">


                <div class="glass-shine"></div>



                {{-- HEADER --}}
                <div class="form-main-header">


                    <div class="heading-main">


                        <div class="heading-icon">

                            <i class="fas fa-clipboard-check"></i>

                        </div>


                        <div>

                            <span>
                                EVALUACIÓN NUTRICIONAL
                            </span>

                            <h3>
                                Datos antropométricos
                            </h3>

                            <p>
                                Registra las mediciones actuales del paciente.
                            </p>

                        </div>


                    </div>



                    <div class="patient-mini-badge">

                        <div class="mini-avatar">

                            {{ strtoupper(substr($paciente->first_name, 0, 1)) }}
                            {{ strtoupper(substr($paciente->last_name, 0, 1)) }}

                        </div>

                        <div>

                            <span>
                                PACIENTE
                            </span>

                            <strong>

                                {{ $paciente->first_name }}
                                {{ $paciente->last_name }}

                            </strong>

                        </div>

                    </div>


                </div>



                {{-- =====================================================
                     01 COMPOSICIÓN CORPORAL
                ====================================================== --}}

                <div class="form-section">


                    <div class="section-heading">


                        <span class="section-number">
                            01
                        </span>


                        <div>

                            <h4>
                                Composición corporal
                            </h4>

                            <p>
                                Datos principales de la evaluación.
                            </p>

                        </div>


                    </div>



                    <div class="row">


                        {{-- FECHA --}}
                        <div class="col-lg-4 col-md-6">

                            <div class="form-group">


                                <label for="evaluation_date">

                                    Fecha

                                    <span class="required">
                                        *
                                    </span>

                                </label>


                                <div class="liquid-input @error('evaluation_date') input-error @enderror">

                                    <div class="input-icon">

                                        <i class="far fa-calendar-alt"></i>

                                    </div>


                                    <input
                                        type="date"
                                        id="evaluation_date"
                                        name="evaluation_date"
                                        value="{{ old('evaluation_date', now()->format('Y-m-d')) }}"
                                        required
                                    >

                                </div>


                                @error('evaluation_date')

                                    <div class="field-error">

                                        <i class="fas fa-exclamation-circle"></i>

                                        {{ $message }}

                                    </div>

                                @enderror


                            </div>

                        </div>



                        {{-- PESO --}}
                        <div class="col-lg-4 col-md-6">

                            <div class="form-group">


                                <label for="weight">

                                    Peso

                                    <span class="input-unit-label">
                                        kg
                                    </span>

                                </label>


                                <div class="liquid-input @error('weight') input-error @enderror">

                                    <div class="input-icon">

                                        <i class="fas fa-weight"></i>

                                    </div>


                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        id="weight"
                                        name="weight"
                                        value="{{ old('weight') }}"
                                        placeholder="Ej. 82.50"
                                    >

                                    <span class="input-suffix">
                                        kg
                                    </span>

                                </div>


                                @error('weight')

                                    <div class="field-error">

                                        {{ $message }}

                                    </div>

                                @enderror


                            </div>

                        </div>



                        {{-- ESTATURA --}}
                        <div class="col-lg-4 col-md-6">

                            <div class="form-group">


                                <label for="height">

                                    Estatura

                                    <span class="input-unit-label">
                                        cm
                                    </span>

                                </label>


                                <div class="liquid-input @error('height') input-error @enderror">

                                    <div class="input-icon">

                                        <i class="fas fa-ruler-vertical"></i>

                                    </div>


                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        id="height"
                                        name="height"
                                        value="{{ old('height') }}"
                                        placeholder="Ej. 178"
                                    >

                                    <span class="input-suffix">
                                        cm
                                    </span>

                                </div>


                                @error('height')

                                    <div class="field-error">

                                        {{ $message }}

                                    </div>

                                @enderror


                            </div>

                        </div>



                        {{-- GRASA --}}
                        <div class="col-lg-6 col-md-6">

                            <div class="form-group">


                                <label for="body_fat">

                                    Grasa corporal

                                    <span class="input-unit-label">
                                        %
                                    </span>

                                </label>


                                <div class="liquid-input @error('body_fat') input-error @enderror">

                                    <div class="input-icon">

                                        <i class="fas fa-percentage"></i>

                                    </div>


                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        id="body_fat"
                                        name="body_fat"
                                        value="{{ old('body_fat') }}"
                                        placeholder="Ej. 18.5"
                                    >

                                    <span class="input-suffix">
                                        %
                                    </span>

                                </div>


                                @error('body_fat')

                                    <div class="field-error">

                                        {{ $message }}

                                    </div>

                                @enderror


                            </div>

                        </div>



                        {{-- MÚSCULO --}}
                        <div class="col-lg-6 col-md-6">

                            <div class="form-group">


                                <label for="muscle_mass">

                                    Masa muscular

                                    <span class="input-unit-label">
                                        kg
                                    </span>

                                </label>


                                <div class="liquid-input @error('muscle_mass') input-error @enderror">

                                    <div class="input-icon">

                                        <i class="fas fa-dumbbell"></i>

                                    </div>


                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        id="muscle_mass"
                                        name="muscle_mass"
                                        value="{{ old('muscle_mass') }}"
                                        placeholder="Ej. 35.20"
                                    >

                                    <span class="input-suffix">
                                        kg
                                    </span>

                                </div>


                                @error('muscle_mass')

                                    <div class="field-error">

                                        {{ $message }}

                                    </div>

                                @enderror


                            </div>

                        </div>


                    </div>

                </div>



                {{-- =====================================================
                     02 MEDIDAS
                ====================================================== --}}

                <div class="form-section">


                    <div class="section-heading">


                        <span class="section-number">
                            02
                        </span>


                        <div>

                            <h4>
                                Medidas corporales
                            </h4>

                            <p>
                                Registra perímetros para comparar el progreso.
                            </p>

                        </div>


                    </div>



                    <div class="measurements-grid">


                        @foreach($measurements as $name => $data)

                            <div class="measurement-field">


                                <label for="{{ $name }}">

                                    {{ $data['label'] }}

                                </label>


                                <div class="liquid-input measurement-input @error($name) input-error @enderror">


                                    <div class="input-icon">

                                        <i class="fas {{ $data['icon'] }}"></i>

                                    </div>


                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        id="{{ $name }}"
                                        name="{{ $name }}"
                                        value="{{ old($name) }}"
                                        placeholder="0.00"
                                    >


                                    <span class="input-suffix">
                                        cm
                                    </span>


                                </div>


                                @error($name)

                                    <div class="field-error">

                                        {{ $message }}

                                    </div>

                                @enderror


                            </div>

                        @endforeach


                    </div>

                </div>



                {{-- =====================================================
                     03 OBSERVACIONES
                ====================================================== --}}

                <div class="form-section last-section">


                    <div class="section-heading">


                        <span class="section-number">
                            03
                        </span>


                        <div>

                            <h4>
                                Observaciones
                            </h4>

                            <p>
                                Añade notas relevantes de la consulta.
                            </p>

                        </div>


                    </div>



                    <div class="form-group mb-0">


                        <div class="liquid-textarea @error('notes') input-error @enderror">


                            <div class="textarea-icon">

                                <i class="far fa-clipboard"></i>

                            </div>


                            <textarea
                                name="notes"
                                id="notes"
                                rows="5"
                                placeholder="Ej. El paciente reporta buena adherencia al plan, mejora en energía y entrenamiento..."
                            >{{ old('notes') }}</textarea>


                            <div class="notes-counter">

                                <span id="notesCount">
                                    {{ strlen(old('notes', '')) }}
                                </span>

                                caracteres

                            </div>


                        </div>


                        @error('notes')

                            <div class="field-error">

                                {{ $message }}

                            </div>

                        @enderror


                    </div>


                </div>



                {{-- =====================================================
                     FOOTER
                ====================================================== --}}

                <div class="form-actions">


                    <a
                        href="{{ route('pacientes.show', $paciente) }}"
                        class="btn-cancel-liquid"
                    >

                        Cancelar

                    </a>


                    <button
                        type="submit"
                        class="btn-save-liquid"
                    >

                        <span>

                            <i class="far fa-save"></i>

                            Guardar evaluación

                        </span>


                        <div>

                            <i class="fas fa-arrow-right"></i>

                        </div>


                    </button>


                </div>


            </div>

{{-- =========================================================
     PATIENTS CREATE — FORMULARIO PRINCIPAL
     Secciones: identificación, contacto, objetivo y acciones
     ========================================================= --}}

            {{-- ==================================================
                 FORMULARIO PRINCIPAL
            =================================================== --}}
            <div class="form-glass-card">


                <div class="glass-shine"></div>


                {{-- ENCABEZADO --}}
                <div class="form-card-header">


                    <div class="form-heading">


                        <div class="form-heading-icon">

                            <i class="far fa-user"></i>

                        </div>


                        <div>

                            <span>
                                INFORMACIÓN PERSONAL
                            </span>

                            <h3>
                                Datos del paciente
                            </h3>

                            <p>
                                Completa la información básica para crear
                                su expediente dentro de NutriLife.
                            </p>

                        </div>

                    </div>


                    <div class="required-badge">

                        <span>*</span>
                        Campos obligatorios

                    </div>

                </div>



                {{-- ==================================================
                     NOMBRE
                =================================================== --}}
                <div class="form-section">

                    <div class="section-label">

                        <span class="section-number">
                            01
                        </span>

                        <div>

                            <h4>
                                Identificación
                            </h4>

                            <p>
                                Información principal del paciente.
                            </p>

                        </div>

                    </div>


                    <div class="row">


                        {{-- NOMBRE --}}
                        <div class="col-md-6">

                            <div class="form-group">

                                <label for="first_name">

                                    Nombre
                                    <span class="required">*</span>

                                </label>


                                <div class="liquid-input @error('first_name') input-error @enderror">

                                    <div class="input-icon">
                                        <i class="far fa-user"></i>
                                    </div>

                                    <input
                                        type="text"
                                        id="first_name"
                                        name="first_name"
                                        maxlength="100"
                                        value="{{ old('first_name') }}"
                                        placeholder="Ej. Juan"
                                        required
                                        autocomplete="given-name"
                                    >

                                </div>


                                @error('first_name')

                                    <div class="field-error">

                                        <i class="fas fa-exclamation-circle"></i>

                                        {{ $message }}

                                    </div>

                                @enderror

                            </div>

                        </div>



                        {{-- APELLIDOS --}}
                        <div class="col-md-6">

                            <div class="form-group">

                                <label for="last_name">

                                    Apellidos
                                    <span class="required">*</span>

                                </label>


                                <div class="liquid-input @error('last_name') input-error @enderror">

                                    <div class="input-icon">
                                        <i class="fas fa-signature"></i>
                                    </div>

                                    <input
                                        type="text"
                                        id="last_name"
                                        name="last_name"
                                        maxlength="100"
                                        value="{{ old('last_name') }}"
                                        placeholder="Ej. García Lizaola"
                                        required
                                        autocomplete="family-name"
                                    >

                                </div>


                                @error('last_name')

                                    <div class="field-error">

                                        <i class="fas fa-exclamation-circle"></i>

                                        {{ $message }}

                                    </div>

                                @enderror

                            </div>

                        </div>



                        {{-- FECHA NACIMIENTO --}}
                        <div class="col-md-4">

                            <div class="form-group">

                                <label for="birth_date">
                                    Fecha de nacimiento
                                </label>


                                <div class="liquid-input @error('birth_date') input-error @enderror">

                                    <div class="input-icon">
                                        <i class="far fa-calendar"></i>
                                    </div>

                                    <input
                                        type="date"
                                        id="birth_date"
                                        name="birth_date"
                                        min="1900-01-01"
                                        max="{{ now()->format('Y-m-d') }}"
                                        value="{{ old('birth_date') }}"
                                    >

                                </div>


                                @error('birth_date')

                                    <div class="field-error">

                                        <i class="fas fa-exclamation-circle"></i>

                                        {{ $message }}

                                    </div>

                                @enderror

                            </div>

                        </div>



                        {{-- SEXO --}}
                        <div class="col-md-4">

                            <div class="form-group">

                                <label for="sex">
                                    Sexo
                                </label>


                                <div class="liquid-input liquid-select @error('sex') input-error @enderror">

                                    <div class="input-icon">
                                        <i class="fas fa-venus-mars"></i>
                                    </div>


                                    <select
                                        id="sex"
                                        name="sex"
                                    >

                                        <option value="">
                                            Selecciona
                                        </option>

                                        <option
                                            value="Masculino"
                                            {{ old('sex') === 'Masculino' ? 'selected' : '' }}
                                        >
                                            Masculino
                                        </option>

                                        <option
                                            value="Femenino"
                                            {{ old('sex') === 'Femenino' ? 'selected' : '' }}
                                        >
                                            Femenino
                                        </option>

                                        <option
                                            value="Otro"
                                            {{ old('sex') === 'Otro' ? 'selected' : '' }}
                                        >
                                            Otro
                                        </option>

                                    </select>

                                </div>


                                @error('sex')

                                    <div class="field-error">

                                        <i class="fas fa-exclamation-circle"></i>

                                        {{ $message }}

                                    </div>

                                @enderror

                            </div>

                        </div>



                        {{-- TELÉFONO --}}
                        <div class="col-md-4">

                            <div class="form-group">

                                <label for="phone">
                                    Teléfono
                                </label>


                                <div class="liquid-input @error('phone') input-error @enderror">

                                    <div class="input-icon">
                                        <i class="fas fa-phone-alt"></i>
                                    </div>

                                    <input
                                        type="tel"
                                        id="phone"
                                        name="phone"
                                        maxlength="20"
                                        value="{{ old('phone') }}"
                                        placeholder="33 1234 5678"
                                        autocomplete="tel"
                                    >

                                </div>


                                @error('phone')

                                    <div class="field-error">

                                        <i class="fas fa-exclamation-circle"></i>

                                        {{ $message }}

                                    </div>

                                @enderror

                            </div>

                        </div>

                    </div>

                </div>



                {{-- ==================================================
                     CONTACTO
                =================================================== --}}
                <div class="form-section">

                    <div class="section-label">

                        <span class="section-number">
                            02
                        </span>

                        <div>

                            <h4>
                                Contacto y actividad
                            </h4>

                            <p>
                                Datos útiles para mantener comunicación.
                            </p>

                        </div>

                    </div>


                    <div class="row">


                        {{-- CORREO --}}
                        <div class="col-md-6">

                            <div class="form-group">

                                <label for="email">
                                    Correo electrónico
                                    <span class="required">*</span>
                                </label>


                                <div class="liquid-input @error('email') input-error @enderror">

                                    <div class="input-icon">
                                        <i class="far fa-envelope"></i>
                                    </div>

                                    <input
                                        type="email"
                                        id="email"
                                        name="email"
                                        maxlength="255"
                                        value="{{ old('email') }}"
                                        placeholder="paciente@correo.com"
                                        autocomplete="email"
                                        required
                                    >

                                </div>


                                @error('email')

                                    <div class="field-error">

                                        <i class="fas fa-exclamation-circle"></i>

                                        {{ $message }}

                                    </div>

                                @enderror

                            </div>

                        </div>



                        {{-- OCUPACIÓN --}}
                        <div class="col-md-6">

                            <div class="form-group">

                                <label for="occupation">
                                    Ocupación
                                </label>


                                <div class="liquid-input @error('occupation') input-error @enderror">

                                    <div class="input-icon">
                                        <i class="fas fa-briefcase"></i>
                                    </div>

                                    <input
                                        type="text"
                                        id="occupation"
                                        name="occupation"
                                        maxlength="150"
                                        value="{{ old('occupation') }}"
                                        placeholder="Ej. Estudiante"
                                    >

                                </div>


                                @error('occupation')

                                    <div class="field-error">

                                        <i class="fas fa-exclamation-circle"></i>

                                        {{ $message }}

                                    </div>

                                @enderror

                            </div>

                        </div>

                    </div>

                </div>



                {{-- ==================================================
                     OBJETIVO
                =================================================== --}}
                <div class="form-section">

                    <div class="section-label">

                        <span class="section-number">
                            03
                        </span>

                        <div>

                            <h4>
                                Objetivo nutricional
                            </h4>

                            <p>
                                Describe brevemente qué desea conseguir.
                            </p>

                        </div>

                    </div>


                    <div class="form-group mb-0">

                        <label for="goal">
                            Objetivo del paciente
                        </label>


                        <div class="liquid-textarea @error('goal') input-error @enderror">

                            <div class="textarea-icon">

                                <i class="fas fa-bullseye"></i>

                            </div>


                            <textarea
                                id="goal"
                                name="goal"
                                rows="5"
                                maxlength="1000"
                                placeholder="Ej. Pérdida de grasa manteniendo masa muscular..."
                            >{{ old('goal') }}</textarea>


                            <div class="character-counter">

                                <span id="goalCount">
                                    {{ strlen(old('goal', '')) }}
                                </span>

                                / 1000

                            </div>

                        </div>


                        @error('goal')

                            <div class="field-error">

                                <i class="fas fa-exclamation-circle"></i>

                                {{ $message }}

                            </div>

                        @enderror

                    </div>

                </div>



                {{-- ==================================================
                     HISTORIAL CLÍNICO (patologías y alergias)
                =================================================== --}}
                @include('patients.partials.clinical-fields', ['sectionNumber' => '04'])



                {{-- ==================================================
                     BOTONES
                =================================================== --}}
                <div class="form-actions">


                    <a
                        href="{{ route('pacientes.index') }}"
                        class="btn-cancel-liquid"
                    >

                        Cancelar

                    </a>


                    <button
                        type="submit"
                        class="btn-save-liquid"
                    >

                        <span class="save-text">

                            <i class="far fa-save"></i>

                            Guardar paciente

                        </span>


                        <span class="save-arrow">

                            <i class="fas fa-arrow-right"></i>

                        </span>

                    </button>

                </div>


            </div>

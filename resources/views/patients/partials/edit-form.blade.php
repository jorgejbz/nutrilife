{{-- =========================================================
     PATIENTS EDIT — FORMULARIO
     Secciones: datos personales, contacto, objetivo y acciones
     ========================================================= --}}

            {{-- ==================================================
                 FORMULARIO
            =================================================== --}}

            <div class="edit-glass-card">


                <div class="glass-shine"></div>


                <div class="form-heading">

                    <div class="form-heading-icon">

                        <i class="far fa-edit"></i>

                    </div>


                    <div>

                        <span>
                            EDITAR EXPEDIENTE
                        </span>

                        <h3>
                            Información del paciente
                        </h3>

                        <p>
                            Modifica los datos que necesites actualizar.
                        </p>

                    </div>

                </div>



                {{-- ==========================================
                     DATOS PERSONALES
                =========================================== --}}

                <div class="form-section">


                    <div class="section-heading">

                        <div class="section-number">
                            01
                        </div>

                        <div>

                            <h4>
                                Datos personales
                            </h4>

                            <p>
                                Información básica del paciente.
                            </p>

                        </div>

                    </div>



                    <div class="row">


                        {{-- NOMBRE --}}
                        <div class="col-md-6">

                            <div class="form-group">

                                <label for="first_name">

                                    Nombre
                                    <span>*</span>

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
                                        value="{{ old('first_name', $paciente->first_name) }}"
                                        required
                                    >

                                </div>


                                @error('first_name')

                                    <div class="field-error">
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
                                    <span>*</span>

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
                                        value="{{ old('last_name', $paciente->last_name) }}"
                                        required
                                    >

                                </div>


                                @error('last_name')

                                    <div class="field-error">
                                        {{ $message }}
                                    </div>

                                @enderror

                            </div>

                        </div>



                        {{-- NACIMIENTO --}}
                        <div class="col-md-4">

                            <div class="form-group">

                                <label for="birth_date">
                                    Fecha de nacimiento
                                </label>


                                <div class="liquid-input">

                                    <div class="input-icon">
                                        <i class="far fa-calendar"></i>
                                    </div>


                                    <input
                                        type="date"
                                        id="birth_date"
                                        name="birth_date"
                                        min="1900-01-01"
                                        max="{{ now()->format('Y-m-d') }}"
                                        value="{{ old(
                                            'birth_date',
                                            $paciente->birth_date
                                                ? $paciente->birth_date->format('Y-m-d')
                                                : ''
                                        ) }}"
                                    >

                                </div>


                                @error('birth_date')

                                    <div class="field-error">
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


                                <div class="liquid-input">

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
                                            {{ old('sex', $paciente->sex) === 'Masculino'
                                                ? 'selected'
                                                : ''
                                            }}
                                        >
                                            Masculino
                                        </option>


                                        <option
                                            value="Femenino"
                                            {{ old('sex', $paciente->sex) === 'Femenino'
                                                ? 'selected'
                                                : ''
                                            }}
                                        >
                                            Femenino
                                        </option>


                                        <option
                                            value="Otro"
                                            {{ old('sex', $paciente->sex) === 'Otro'
                                                ? 'selected'
                                                : ''
                                            }}
                                        >
                                            Otro
                                        </option>

                                    </select>

                                </div>


                                @error('sex')

                                    <div class="field-error">
                                        {{ $message }}
                                    </div>

                                @enderror

                            </div>

                        </div>



                        {{-- ESTADO --}}
                        <div class="col-md-4">

                            <div class="form-group">

                                <label>
                                    Estado del paciente
                                </label>


                                <div class="status-control">

                                    {{-- IMPORTANTE:
                                         si está desmarcado manda 0 --}}
                                    <input
                                        type="hidden"
                                        name="active"
                                        value="0"
                                    >


                                    <label class="liquid-switch">

                                        <input
                                            type="checkbox"
                                            name="active"
                                            value="1"
                                            {{ old('active', $paciente->active)
                                                ? 'checked'
                                                : ''
                                            }}
                                        >


                                        <span class="switch-slider"></span>


                                        <span class="switch-text">

                                            <strong id="statusText">

                                                {{ old('active', $paciente->active)
                                                    ? 'Paciente activo'
                                                    : 'Paciente inactivo'
                                                }}

                                            </strong>

                                            <small>
                                                Cambiar estado
                                            </small>

                                        </span>

                                    </label>

                                </div>


                                @error('active')

                                    <div class="field-error">
                                        {{ $message }}
                                    </div>

                                @enderror

                            </div>

                        </div>


                    </div>

                </div>



                {{-- ==========================================
                     CONTACTO
                =========================================== --}}

                <div class="form-section">


                    <div class="section-heading">

                        <div class="section-number">
                            02
                        </div>

                        <div>

                            <h4>
                                Contacto
                            </h4>

                            <p>
                                Información para comunicación.
                            </p>

                        </div>

                    </div>



                    <div class="row">


                        {{-- TELÉFONO --}}
                        <div class="col-md-6">

                            <div class="form-group">

                                <label for="phone">
                                    Teléfono
                                </label>


                                <div class="liquid-input">

                                    <div class="input-icon">
                                        <i class="fas fa-phone-alt"></i>
                                    </div>


                                    <input
                                        type="tel"
                                        id="phone"
                                        name="phone"
                                        maxlength="20"
                                        value="{{ old('phone', $paciente->phone) }}"
                                        placeholder="33 1234 5678"
                                    >

                                </div>


                                @error('phone')

                                    <div class="field-error">
                                        {{ $message }}
                                    </div>

                                @enderror

                            </div>

                        </div>



                        {{-- CORREO --}}
                        <div class="col-md-6">

                            <div class="form-group">

                                <label for="email">
                                    Correo electrónico
                                    <span class="required">*</span>
                                </label>


                                <div class="liquid-input">

                                    <div class="input-icon">
                                        <i class="far fa-envelope"></i>
                                    </div>


                                    <input
                                        type="email"
                                        id="email"
                                        name="email"
                                        maxlength="255"
                                        value="{{ old('email', $paciente->email) }}"
                                        placeholder="paciente@correo.com"
                                        required
                                    >

                                </div>


                                @error('email')

                                    <div class="field-error">
                                        {{ $message }}
                                    </div>

                                @enderror

                            </div>

                        </div>



                        {{-- OCUPACIÓN --}}
                        <div class="col-md-12">

                            <div class="form-group">

                                <label for="occupation">
                                    Ocupación
                                </label>


                                <div class="liquid-input">

                                    <div class="input-icon">
                                        <i class="fas fa-briefcase"></i>
                                    </div>


                                    <input
                                        type="text"
                                        id="occupation"
                                        name="occupation"
                                        maxlength="150"
                                        value="{{ old('occupation', $paciente->occupation) }}"
                                        placeholder="Ej. Estudiante"
                                    >

                                </div>


                                @error('occupation')

                                    <div class="field-error">
                                        {{ $message }}
                                    </div>

                                @enderror

                            </div>

                        </div>


                    </div>

                </div>



                {{-- ==========================================
                     OBJETIVO
                =========================================== --}}

                <div class="form-section">


                    <div class="section-heading">

                        <div class="section-number">
                            03
                        </div>

                        <div>

                            <h4>
                                Objetivo nutricional
                            </h4>

                            <p>
                                Meta principal del paciente.
                            </p>

                        </div>

                    </div>



                    <div class="form-group mb-0">


                        <div class="liquid-textarea">

                            <div class="textarea-icon">

                                <i class="fas fa-bullseye"></i>

                            </div>


                            <textarea
                                id="goal"
                                name="goal"
                                rows="5"
                                maxlength="1000"
                                placeholder="Describe el objetivo nutricional..."
                            >{{ old('goal', $paciente->goal) }}</textarea>


                            <div class="character-counter">

                                <span id="goalCount">
                                    {{ strlen(old('goal', $paciente->goal ?? '')) }}
                                </span>

                                / 1000

                            </div>

                        </div>


                        @error('goal')

                            <div class="field-error">
                                {{ $message }}
                            </div>

                        @enderror

                    </div>


                </div>



                {{-- ==================================================
                     HISTORIAL CLÍNICO (patologías y alergias)
                =================================================== --}}
                @include('patients.partials.clinical-fields', ['sectionNumber' => '04'])



                {{-- ==========================================
                     BOTONES
                =========================================== --}}

                <div class="form-actions">


                    <a
                        href="{{ route('pacientes.show', $paciente) }}"
                        class="btn-cancel"
                    >
                        Cancelar
                    </a>


                    <button
                        type="submit"
                        class="btn-save"
                    >

                        <span>

                            <i class="far fa-save"></i>

                            Guardar cambios

                        </span>


                        <div>

                            <i class="fas fa-arrow-right"></i>

                        </div>

                    </button>


                </div>


            </div>

{{-- =========================================================
     APPOINTMENTS CREATE — FORMULARIO PRINCIPAL
     Paciente/horario, detalles, notas y acciones
     ========================================================= --}}

            <div class="appointment-form-glass">


                <div class="glass-shine"></div>


                {{-- HEADER --}}
                <div class="form-main-header">


                    <div class="heading-main">


                        <div class="heading-icon">

                            <i class="far fa-calendar-plus"></i>

                        </div>


                        <div>

                            <span>
                                PROGRAMAR CONSULTA
                            </span>

                            <h3>
                                Información de la cita
                            </h3>

                            <p>
                                Selecciona paciente, fecha y detalles de la consulta.
                            </p>

                        </div>

                    </div>


                    <div class="required-badge">

                        <span>*</span>

                        Campos obligatorios

                    </div>


                </div>



                {{-- =====================================================
                     01 PACIENTE Y HORARIO
                ====================================================== --}}

                <div class="form-section">


                    <div class="section-heading">

                        <span class="section-number">
                            01
                        </span>

                        <div>

                            <h4>
                                Paciente y horario
                            </h4>

                            <p>
                                Define quién será atendido y cuándo.
                            </p>

                        </div>

                    </div>



                    <div class="row">


                        {{-- PACIENTE --}}
                        <div class="col-md-6">

                            <div class="form-group">

                                <label for="patient_id">

                                    Paciente

                                    <span class="required">*</span>

                                </label>


                                <div class="liquid-input liquid-select @error('patient_id') input-error @enderror">


                                    <div class="input-icon">

                                        <i class="far fa-user"></i>

                                    </div>


                                    <select
                                        id="patient_id"
                                        name="patient_id"
                                        required
                                    >

                                        <option value="">
                                            Selecciona un paciente
                                        </option>


                                        @foreach($patients as $patient)

                                            <option
                                                value="{{ $patient->id }}"
                                                data-name="{{ $patient->first_name }} {{ $patient->last_name }}"
                                                data-email="{{ $patient->email ?? '' }}"
                                                data-phone="{{ $patient->phone ?? '' }}"
                                                {{ old('patient_id') == $patient->id ? 'selected' : '' }}
                                            >

                                                {{ $patient->first_name }}
                                                {{ $patient->last_name }}

                                            </option>

                                        @endforeach

                                    </select>


                                </div>


                                @error('patient_id')

                                    <div class="field-error">

                                        <i class="fas fa-exclamation-circle"></i>

                                        {{ $message }}

                                    </div>

                                @enderror


                            </div>

                        </div>



                        {{-- FECHA Y HORA --}}
                        <div class="col-md-6">

                            <div class="form-group">

                                <label for="appointment_at">

                                    Fecha y hora

                                    <span class="required">*</span>

                                </label>


                                <div class="liquid-input @error('appointment_at') input-error @enderror">


                                    <div class="input-icon">

                                        <i class="far fa-clock"></i>

                                    </div>


                                    <input
                                        type="datetime-local"
                                        id="appointment_at"
                                        name="appointment_at"
                                        value="{{ old('appointment_at') }}"
                                        required
                                    >


                                </div>


                                @error('appointment_at')

                                    <div class="field-error">

                                        <i class="fas fa-exclamation-circle"></i>

                                        {{ $message }}

                                    </div>

                                @enderror


                            </div>

                        </div>


                    </div>

                </div>



                {{-- =====================================================
                     02 DETALLES
                ====================================================== --}}

                <div class="form-section">


                    <div class="section-heading">

                        <span class="section-number">
                            02
                        </span>

                        <div>

                            <h4>
                                Detalles de la consulta
                            </h4>

                            <p>
                                Indica el motivo y estado inicial.
                            </p>

                        </div>

                    </div>



                    <div class="row">


                        {{-- MOTIVO --}}
                        <div class="col-md-6">

                            <div class="form-group">

                                <label for="reason">
                                    Motivo
                                </label>


                                <div class="liquid-input @error('reason') input-error @enderror">


                                    <div class="input-icon">

                                        <i class="fas fa-stethoscope"></i>

                                    </div>


                                    <input
                                        type="text"
                                        id="reason"
                                        name="reason"
                                        value="{{ old('reason') }}"
                                        placeholder="Ej. Consulta de seguimiento"
                                    >


                                </div>


                                @error('reason')

                                    <div class="field-error">
                                        {{ $message }}
                                    </div>

                                @enderror


                            </div>

                        </div>



                        {{-- ESTADO --}}
                        <div class="col-md-6">

                            <div class="form-group">

                                <label for="status">
                                    Estado
                                </label>


                                <div class="liquid-input liquid-select">


                                    <div class="input-icon">

                                        <i class="fas fa-circle-notch"></i>

                                    </div>


                                    <select
                                        id="status"
                                        name="status"
                                        required
                                    >

                                        <option
                                            value="Pendiente"
                                            {{ old('status', 'Pendiente') === 'Pendiente' ? 'selected' : '' }}
                                        >
                                            Pendiente
                                        </option>


                                        <option
                                            value="Confirmada"
                                            {{ old('status') === 'Confirmada' ? 'selected' : '' }}
                                        >
                                            Confirmada
                                        </option>


                                        <option
                                            value="Completada"
                                            {{ old('status') === 'Completada' ? 'selected' : '' }}
                                        >
                                            Completada
                                        </option>


                                        <option
                                            value="Cancelada"
                                            {{ old('status') === 'Cancelada' ? 'selected' : '' }}
                                        >
                                            Cancelada
                                        </option>

                                    </select>


                                </div>

                            </div>

                        </div>


                    </div>

                </div>



                {{-- =====================================================
                     03 NOTAS
                ====================================================== --}}

                <div class="form-section last-section">


                    <div class="section-heading">

                        <span class="section-number">
                            03
                        </span>

                        <div>

                            <h4>
                                Notas adicionales
                            </h4>

                            <p>
                                Información que pueda ser útil para la consulta.
                            </p>

                        </div>

                    </div>



                    <div class="form-group mb-0">


                        <div class="liquid-textarea @error('notes') input-error @enderror">


                            <div class="textarea-icon">

                                <i class="far fa-clipboard"></i>

                            </div>


                            <textarea
                                id="notes"
                                name="notes"
                                rows="5"
                                maxlength="1000"
                                placeholder="Información adicional de la cita..."
                            >{{ old('notes') }}</textarea>


                            <div class="character-counter">

                                <span id="notesCount">
                                    {{ strlen(old('notes', '')) }}
                                </span>

                                / 1000

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
                     ACCIONES
                ====================================================== --}}

                <div class="form-actions">


                    <a
                        href="{{ route('citas.index') }}"
                        class="btn-cancel-liquid"
                    >

                        Cancelar

                    </a>


                    <button
                        type="submit"
                        class="btn-save-liquid"
                    >

                        <span>

                            <i class="far fa-calendar-check"></i>

                            Guardar cita

                        </span>


                        <div>

                            <i class="fas fa-arrow-right"></i>

                        </div>


                    </button>


                </div>


            </div>

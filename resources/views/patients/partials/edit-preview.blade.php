{{-- =========================================================
     PATIENTS EDIT — PREVIEW
     Vista previa en vivo del expediente mientras se edita
     (ver resources/js/pages/patients-edit.js)
     ========================================================= --}}

            {{-- ==================================================
                 PREVIEW
            =================================================== --}}

            <aside class="edit-side">


                <div class="preview-glass">


                    <span class="preview-eyebrow">
                        VISTA PREVIA
                    </span>


                    <div class="preview-avatar">

                        <span id="previewInitial">

                            {{ strtoupper(substr($paciente->first_name, 0, 1)) }}

                        </span>

                    </div>


                    <h3 id="previewName">

                        {{ $paciente->first_name }}
                        {{ $paciente->last_name }}

                    </h3>


                    <p id="previewOccupation">

                        {{ $paciente->occupation
                            ?? 'Sin ocupación'
                        }}

                    </p>


                    <div
                        class="preview-state {{ $paciente->active ? 'active' : 'inactive' }}"
                        id="previewState"
                    >

                        <span></span>

                        <strong id="previewStateText">

                            {{ $paciente->active
                                ? 'Paciente activo'
                                : 'Paciente inactivo'
                            }}

                        </strong>

                    </div>



                    <div class="preview-divider"></div>



                    <div class="preview-data">


                        <div>

                            <div>

                                <i class="fas fa-phone-alt"></i>

                            </div>

                            <span id="previewPhone">

                                {{ $paciente->phone
                                    ?? 'Sin teléfono'
                                }}

                            </span>

                        </div>



                        <div>

                            <div>

                                <i class="far fa-envelope"></i>

                            </div>

                            <span id="previewEmail">

                                {{ $paciente->email
                                    ?? 'Sin correo'
                                }}

                            </span>

                        </div>



                        <div>

                            <div>

                                <i class="fas fa-bullseye"></i>

                            </div>

                            <span id="previewGoal">

                                {{ $paciente->goal
                                    ?? 'Sin objetivo'
                                }}

                            </span>

                        </div>


                    </div>


                </div>



                <div class="info-glass">

                    <div>

                        <i class="fas fa-shield-alt"></i>

                    </div>


                    <p>

                        <strong>
                            Editando expediente
                        </strong>

                        Los cambios se guardarán cuando
                        presiones “Guardar cambios”.

                    </p>

                </div>


            </aside>

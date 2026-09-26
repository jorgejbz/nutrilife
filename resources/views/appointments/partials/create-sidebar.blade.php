{{-- =========================================================
     APPOINTMENTS CREATE — PANEL DERECHO
     Resumen / vista previa de la cita
     ========================================================= --}}


            {{-- =====================================================
                 PANEL DERECHO
            ====================================================== --}}

            <aside class="appointment-sidebar">


                {{-- PREVIEW --}}
                <div class="appointment-preview-glass">


                    <div class="preview-shine"></div>


                    <div class="preview-header">


                        <div>

                            <span>
                                VISTA PREVIA
                            </span>

                            <h4>
                                Nueva cita
                            </h4>

                        </div>


                        <div
                            class="preview-status pending"
                            id="previewStatus"
                        >

                            <span></span>

                            <strong id="previewStatusText">
                                Pendiente
                            </strong>

                        </div>


                    </div>



                    {{-- CALENDARIO --}}
                    <div class="preview-date">


                        <div class="date-box">

                            <span id="previewDay">
                                —
                            </span>

                            <small id="previewMonth">
                                MES
                            </small>

                        </div>


                        <div class="date-info">

                            <span>
                                FECHA Y HORA
                            </span>

                            <h3 id="previewDateText">
                                Sin fecha seleccionada
                            </h3>

                            <p id="previewTime">
                                Selecciona el horario
                            </p>

                        </div>


                    </div>



                    <div class="preview-divider"></div>



                    {{-- PACIENTE --}}
                    <div class="preview-patient">


                        <div
                            class="preview-avatar"
                            id="previewAvatar"
                        >

                            ?

                        </div>


                        <div>

                            <span>
                                PACIENTE
                            </span>

                            <h4 id="previewPatient">
                                Sin seleccionar
                            </h4>

                            <p id="previewContact">
                                Selecciona un paciente
                            </p>

                        </div>


                    </div>



                    {{-- MOTIVO --}}
                    <div class="preview-reason">


                        <div>

                            <i class="fas fa-stethoscope"></i>

                        </div>


                        <div>

                            <span>
                                MOTIVO
                            </span>

                            <strong id="previewReason">
                                Sin motivo registrado
                            </strong>

                        </div>


                    </div>



                    {{-- NOTA --}}
                    <div class="preview-note">


                        <span>
                            NOTAS
                        </span>

                        <p id="previewNotes">
                            Sin notas adicionales.
                        </p>


                    </div>


                </div>



                {{-- CONSEJO --}}
                <div class="agenda-tip-glass">


                    <div class="tip-icon">

                        <i class="fas fa-seedling"></i>

                    </div>


                    <div>

                        <span>
                            NUTRILIFE TIP
                        </span>

                        <h4>
                            Agenda organizada
                        </h4>

                        <p>
                            Mantener las citas actualizadas facilita
                            el seguimiento de tus pacientes.
                        </p>

                    </div>


                </div>



                {{-- INDICADORES --}}
                <div class="appointment-info-glass">


                    <div>

                        <i class="far fa-calendar-check"></i>

                        <span>
                            La cita aparecerá automáticamente
                            en tu calendario.
                        </span>

                    </div>


                    <div>

                        <i class="fas fa-user-check"></i>

                        <span>
                            Quedará vinculada al expediente
                            del paciente seleccionado.
                        </span>

                    </div>


                </div>


            </aside>

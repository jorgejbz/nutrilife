{{-- =========================================================
     EVALUATIONS CREATE — PANEL DERECHO
     Resumen en vivo de la evaluación (ver resources/js/pages/evaluations-create.js)
     ========================================================= --}}


            {{-- =====================================================
                 PANEL DERECHO
            ====================================================== --}}

            <aside class="evaluation-sidebar">


                {{-- RESUMEN EN VIVO --}}
                <div class="live-summary-glass">


                    <div class="side-shine"></div>


                    <div class="live-header">


                        <div>

                            <span>
                                VISTA PREVIA
                            </span>

                            <h4>
                                Evaluación actual
                            </h4>

                        </div>


                        <div class="live-indicator">

                            <span></span>

                            En vivo

                        </div>


                    </div>



                    <div class="live-patient">


                        <div class="live-avatar">

                            {{ strtoupper(substr($paciente->first_name, 0, 1)) }}
                            {{ strtoupper(substr($paciente->last_name, 0, 1)) }}

                        </div>


                        <div>

                            <strong>

                                {{ $paciente->first_name }}
                                {{ $paciente->last_name }}

                            </strong>


                            <span>

                                Nueva medición

                            </span>

                        </div>


                    </div>



                    {{-- IMC --}}
                    <div class="bmi-preview">


                        <div class="bmi-circle">

                            <strong id="previewBmi">
                                —
                            </strong>

                            <span>
                                IMC
                            </span>

                        </div>


                        <div class="bmi-info">

                            <span>
                                ÍNDICE CORPORAL
                            </span>

                            <h4 id="bmiStatus">
                                Esperando datos
                            </h4>

                            <p id="bmiDescription">

                                Ingresa peso y estatura
                                para obtener el cálculo.

                            </p>

                        </div>


                    </div>



                    {{-- VALORES --}}
                    <div class="live-values">


                        <div>

                            <div class="live-value-icon">

                                <i class="fas fa-weight"></i>

                            </div>

                            <span>
                                Peso
                            </span>

                            <strong id="previewWeight">
                                —
                            </strong>

                            <small>
                                kg
                            </small>

                        </div>



                        <div>

                            <div class="live-value-icon">

                                <i class="fas fa-percentage"></i>

                            </div>

                            <span>
                                Grasa
                            </span>

                            <strong id="previewFat">
                                —
                            </strong>

                            <small>
                                %
                            </small>

                        </div>



                        <div>

                            <div class="live-value-icon">

                                <i class="fas fa-dumbbell"></i>

                            </div>

                            <span>
                                Músculo
                            </span>

                            <strong id="previewMuscle">
                                —
                            </strong>

                            <small>
                                kg
                            </small>

                        </div>


                    </div>


                </div>



                {{-- MEDIDAS PREVIEW --}}
                <div class="measure-preview-glass">


                    <div class="side-card-heading">


                        <div>

                            <span>
                                PERÍMETROS
                            </span>

                            <h4>
                                Medidas corporales
                            </h4>

                        </div>


                        <div>

                            <i class="fas fa-ruler-combined"></i>

                        </div>


                    </div>



                    <div class="measure-preview-list">


                        <div>

                            <span>
                                Cintura
                            </span>

                            <strong id="previewWaist">
                                —
                            </strong>

                        </div>


                        <div>

                            <span>
                                Cadera
                            </span>

                            <strong id="previewHip">
                                —
                            </strong>

                        </div>


                        <div>

                            <span>
                                Pecho
                            </span>

                            <strong id="previewChest">
                                —
                            </strong>

                        </div>


                        <div>

                            <span>
                                Brazo
                            </span>

                            <strong id="previewArm">
                                —
                            </strong>

                        </div>


                        <div>

                            <span>
                                Muslo
                            </span>

                            <strong id="previewThigh">
                                —
                            </strong>

                        </div>


                    </div>


                </div>



                {{-- TIP --}}
                <div class="evaluation-tip-glass">


                    <div>

                        <i class="fas fa-seedling"></i>

                    </div>


                    <p>

                        <strong>
                            Seguimiento consistente
                        </strong>

                        Usa condiciones similares en cada medición
                        para obtener comparaciones más útiles.

                    </p>


                </div>


            </aside>

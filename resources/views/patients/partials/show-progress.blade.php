{{-- =========================================================
     PATIENTS SHOW — PROGRESO
     Indicadores de seguimiento nutricional
     ========================================================= --}}

    {{-- =====================================================
         PROGRESO
    ====================================================== --}}

    @if($firstEvaluation && $currentEvaluation)


        <div class="progress-section">


            <div class="section-title-row">


                <div>

                    <span class="section-eyebrow">

                        PROGRESO

                    </span>


                    <h3>
                        Cambios desde la primera evaluación
                    </h3>

                </div>


                <div class="section-small-icon">

                    <i class="fas fa-chart-line"></i>

                </div>

            </div>



            <div class="progress-grid">


                {{-- PESO INICIAL --}}
                <div class="progress-glass-card">


                    <span>
                        PESO INICIAL
                    </span>


                    <h3>

                        {{ $firstEvaluation->weight }}

                        <small>
                            kg
                        </small>

                    </h3>


                    <p>
                        Primera evaluación
                    </p>


                </div>



                {{-- PESO ACTUAL --}}
                <div class="progress-glass-card">


                    <span>
                        PESO ACTUAL
                    </span>


                    <h3>

                        {{ $currentEvaluation->weight }}

                        <small>
                            kg
                        </small>

                    </h3>


                    <p>
                        Última evaluación
                    </p>


                </div>



                {{-- CAMBIO PESO --}}
                <div class="progress-glass-card">


                    <span>
                        CAMBIO DE PESO
                    </span>


                    <h3>

                        @if($weightChange !== null)

                            @if($weightChange > 0)
                                +
                            @endif

                            {{ $weightChange }}

                            <small>
                                kg
                            </small>

                        @else

                            —

                        @endif

                    </h3>


                    @if($weightChange !== null)

                        @if($weightChange < 0)

                            <p class="progress-positive">

                                <i class="fas fa-arrow-down"></i>

                                {{ abs($weightChange) }} kg

                            </p>

                        @elseif($weightChange > 0)

                            <p class="progress-warning">

                                <i class="fas fa-arrow-up"></i>

                                {{ $weightChange }} kg

                            </p>

                        @else

                            <p>
                                Sin cambios
                            </p>

                        @endif

                    @endif


                </div>



                {{-- CAMBIO GRASA --}}
                <div class="progress-glass-card">


                    <span>
                        CAMBIO DE GRASA
                    </span>


                    <h3>

                        @if($fatChange !== null)

                            @if($fatChange > 0)
                                +
                            @endif

                            {{ $fatChange }}

                            <small>
                                %
                            </small>

                        @else

                            —

                        @endif

                    </h3>


                    <p>
                        Desde el inicio
                    </p>


                </div>


            </div>


        </div>


    @endif




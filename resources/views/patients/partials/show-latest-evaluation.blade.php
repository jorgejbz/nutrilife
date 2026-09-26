{{-- =========================================================
     PATIENTS SHOW — ÚLTIMA EVALUACIÓN
     Resumen de la evaluación más reciente
     ========================================================= --}}

    {{-- =====================================================
         ÚLTIMA EVALUACIÓN
    ====================================================== --}}

    @if($latestEvaluation)


        <div class="evaluation-glass">


            <div class="evaluation-header">


                <div>

                    <span class="section-eyebrow">

                        ÚLTIMA EVALUACIÓN

                    </span>


                    <h3>
                        Estado corporal actual
                    </h3>


                    <p>

                        Evaluación realizada el

                        {{ $latestEvaluation
                            ->evaluation_date
                            ->format('d/m/Y')
                        }}

                    </p>

                </div>


                <div class="evaluation-badge">

                    <i class="fas fa-clipboard-check"></i>

                    Evaluación #{{ $evaluations->count() }}

                </div>

            </div>



            <div class="evaluation-metrics">


                {{-- PESO --}}
                <div class="metric-glass">


                    <div class="metric-icon">

                        <i class="fas fa-weight"></i>

                    </div>


                    <span>
                        PESO
                    </span>


                    <h3>

                        {{ $latestEvaluation->weight }}

                        <small>
                            kg
                        </small>

                    </h3>


                </div>



                {{-- IMC --}}
                <div class="metric-glass">


                    <div class="metric-icon">

                        <i class="fas fa-calculator"></i>

                    </div>


                    <span>
                        IMC
                    </span>


                    <h3>

                        {{ $latestEvaluation->bmi }}

                    </h3>


                </div>



                {{-- GRASA --}}
                <div class="metric-glass">


                    <div class="metric-icon">

                        <i class="fas fa-percentage"></i>

                    </div>


                    <span>
                        GRASA CORPORAL
                    </span>


                    <h3>

                        {{ $latestEvaluation->body_fat ?? '—' }}

                        @if(!is_null($latestEvaluation->body_fat))

                            <small>
                                %
                            </small>

                        @endif

                    </h3>


                </div>



                {{-- MÚSCULO --}}
                <div class="metric-glass">


                    <div class="metric-icon">

                        <i class="fas fa-dumbbell"></i>

                    </div>


                    <span>
                        MASA MUSCULAR
                    </span>


                    <h3>

                        {{ $latestEvaluation->muscle_mass ?? '—' }}

                        @if(!is_null($latestEvaluation->muscle_mass))

                            <small>
                                kg
                            </small>

                        @endif

                    </h3>


                </div>


            </div>

        </div>


    @else


        {{-- SIN EVALUACIONES --}}
        <div class="empty-evaluation-glass">


            <div class="empty-evaluation-icon">

                <i class="fas fa-clipboard"></i>

            </div>


            <span>
                SEGUIMIENTO NUTRICIONAL
            </span>


            <h3>
                Aún no hay evaluaciones
            </h3>


            <p>

                Registra la primera evaluación para comenzar
                a medir peso, IMC, grasa corporal y progreso.

            </p>


            <a
                href="{{ route('evaluaciones.create', $paciente) }}"
                class="btn-liquid-primary"
            >

                <i class="fas fa-plus"></i>

                Primera evaluación

            </a>


        </div>


    @endif




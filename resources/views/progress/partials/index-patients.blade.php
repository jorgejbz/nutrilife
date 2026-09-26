{{-- =========================================================
     PROGRESS INDEX — PACIENTES
     Listado de pacientes con seguimiento
     ========================================================= --}}

    {{-- =====================================
         PACIENTES
    ====================================== --}}

    <div class="progress-panel">


        <div class="progress-panel-header">

            <div>

                <span>
                    SEGUIMIENTO
                </span>

                <h4>
                    Evolución de pacientes
                </h4>

            </div>


            <div class="panel-icon">

                <i class="fas fa-chart-area"></i>

            </div>

        </div>



        @if($progressPatients->isEmpty())


            <div class="progress-empty">

                <i class="fas fa-chart-line"></i>

                <h5>
                    Sin pacientes
                </h5>

                <p>
                    Registra pacientes para comenzar
                    a visualizar su progreso.
                </p>

            </div>


        @else


            <div class="progress-grid">


                @foreach($progressPatients as $item)

                    @php

                        $patient = $item['patient'];

                        $latest = $item['latest'];

                        $weightChange =
                            $item['weight_change'];

                        $fatChange =
                            $item['fat_change'];

                        $muscleChange =
                            $item['muscle_change'];

                    @endphp


                    <div class="patient-progress-card">


                        {{-- PACIENTE --}}

                        <div class="progress-patient-header">


                            <div class="patient-progress-avatar">

                                {{ strtoupper(
                                    substr(
                                        $patient->first_name,
                                        0,
                                        1
                                    )
                                ) }}

                                {{ strtoupper(
                                    substr(
                                        $patient->last_name,
                                        0,
                                        1
                                    )
                                ) }}

                            </div>


                            <div>

                                <h5>

                                    {{ $patient->first_name }}
                                    {{ $patient->last_name }}

                                </h5>


                                @if($latest)

                                    <p>

                                        Última evaluación:

                                        {{
                                            $latest
                                                ->evaluation_date
                                                ->format('d/m/Y')
                                        }}

                                    </p>

                                @else

                                    <p>
                                        Sin evaluaciones
                                    </p>

                                @endif

                            </div>


                            <span class="evaluation-counter">

                                {{ $item['evaluation_count'] }}

                                eval.

                            </span>

                        </div>



                        @if($latest)


                            <div class="patient-progress-data">


                                {{-- PESO --}}

                                <div>

                                    <span>
                                        Peso actual
                                    </span>

                                    <strong>

                                        {{
                                            number_format(
                                                $latest->weight,
                                                1
                                            )
                                        }}

                                        kg

                                    </strong>

                                </div>



                                {{-- CAMBIO PESO --}}

                                <div>

                                    <span>
                                        Cambio peso
                                    </span>

                                    <strong
                                        class="{{
                                            !is_null($weightChange)
                                                && $weightChange < 0
                                                ? 'change-down'
                                                : 'change-up'
                                        }}"
                                    >

                                        @if(is_null($weightChange))

                                            —

                                        @elseif($weightChange > 0)

                                            +{{ $weightChange }} kg

                                        @else

                                            {{ $weightChange }} kg

                                        @endif

                                    </strong>

                                </div>



                                {{-- GRASA --}}

                                <div>

                                    <span>
                                        Grasa corporal
                                    </span>

                                    <strong>

                                        @if(!is_null($latest->body_fat))

                                            {{
                                                number_format(
                                                    $latest->body_fat,
                                                    1
                                                )
                                            }}%

                                        @else

                                            —

                                        @endif

                                    </strong>

                                </div>



                                {{-- CAMBIO GRASA --}}

                                <div>

                                    <span>
                                        Cambio grasa
                                    </span>

                                    <strong
                                        class="{{
                                            !is_null($fatChange)
                                                && $fatChange < 0
                                                ? 'change-down'
                                                : 'change-up'
                                        }}"
                                    >

                                        @if(is_null($fatChange))

                                            —

                                        @elseif($fatChange > 0)

                                            +{{ $fatChange }}%

                                        @else

                                            {{ $fatChange }}%

                                        @endif

                                    </strong>

                                </div>


                            </div>


                            @if($item['evaluation_count'] < 2)

                                <div class="comparison-warning">

                                    <i class="fas fa-info-circle"></i>

                                    Se necesita otra evaluación
                                    para comparar el progreso.

                                </div>

                            @endif


                        @else


                            <div class="no-progress">

                                <i class="fas fa-chart-line"></i>

                                Este paciente aún no tiene
                                evaluaciones registradas.

                            </div>


                        @endif



                        {{-- BOTONES --}}

                        <div class="progress-card-actions">


                            <a
                                href="{{ route('progreso.show', $patient) }}"
                                class="progress-view-button"
                            >

                                <i class="fas fa-chart-line"></i>

                                Ver progreso

                            </a>


                            <a
                                href="{{ route('evaluaciones.create', $patient) }}"
                                class="progress-add-button"
                            >

                                <i class="fas fa-plus"></i>

                                Evaluación

                            </a>


                        </div>


                    </div>


                @endforeach


            </div>


        @endif


    </div>

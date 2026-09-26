{{-- =========================================================
     PATIENTS SHOW — HISTORIAL
     Tabla de evaluaciones anteriores
     ========================================================= --}}

    {{-- =====================================================
         HISTORIAL
    ====================================================== --}}

    <div class="history-glass">


        <div class="history-header">


            <div>

                <span class="section-eyebrow">

                    HISTORIAL

                </span>


                <h3>
                    Evaluaciones nutricionales
                </h3>


                <p>

                    Consulta todas las mediciones registradas
                    para este paciente.

                </p>

            </div>


            <a
                href="{{ route('evaluaciones.create', $paciente) }}"
                class="btn-liquid-primary"
            >

                <i class="fas fa-plus"></i>

                Nueva evaluación

            </a>


        </div>



        @if($evaluations->isEmpty())


            <div class="empty-history">

                <i class="far fa-clipboard"></i>

                <p>
                    Aún no hay evaluaciones registradas.
                </p>

            </div>


        @else


            <div class="table-responsive">


                <table class="liquid-table">


                    <thead>

                        <tr>

                            <th>
                                Fecha
                            </th>

                            <th>
                                Peso
                            </th>

                            <th>
                                IMC
                            </th>

                            <th>
                                Grasa
                            </th>

                            <th>
                                Músculo
                            </th>

                            <th>
                                Cintura
                            </th>

                        </tr>

                    </thead>



                    <tbody>

                    @foreach($evaluations as $evaluation)

                        <tr>


                            <td>

                                <div class="table-date">

                                    <div>

                                        {{ $evaluation
                                            ->evaluation_date
                                            ->format('d')
                                        }}

                                    </div>

                                    <span>

                                        {{ strtoupper(
                                            $evaluation
                                            ->evaluation_date
                                            ->translatedFormat('M')
                                        ) }}

                                    </span>

                                </div>

                            </td>


                            <td>

                                <strong>

                                    {{ $evaluation->weight }}

                                </strong>

                                <small>
                                    kg
                                </small>

                            </td>


                            <td>

                                {{ $evaluation->bmi }}

                            </td>


                            <td>

                                {{ $evaluation->body_fat !== null
                                    ? $evaluation->body_fat . ' %'
                                    : '—'
                                }}

                            </td>


                            <td>

                                {{ $evaluation->muscle_mass !== null
                                    ? $evaluation->muscle_mass . ' kg'
                                    : '—'
                                }}

                            </td>


                            <td>

                                {{ $evaluation->waist !== null
                                    ? $evaluation->waist . ' cm'
                                    : '—'
                                }}

                            </td>


                        </tr>

                    @endforeach

                    </tbody>


                </table>


            </div>


        @endif


    </div>

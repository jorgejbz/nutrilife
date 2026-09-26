{{-- =========================================================
     PATIENTS SHOW — GRÁFICA DE PESO
     Canvas de Chart.js (ver resources/js/pages/patients-show.js)
     ========================================================= --}}

    {{-- =====================================================
         GRÁFICA
    ====================================================== --}}

    @if($showWeightChart)


        <div class="weight-chart-glass">


            <div class="chart-header">


                <div>

                    <span class="section-eyebrow">

                        EVOLUCIÓN

                    </span>


                    <h3>

                        Evolución del peso

                    </h3>


                    <p>

                        Seguimiento del peso a través
                        de las evaluaciones registradas.

                    </p>

                </div>


                <div class="chart-icon">

                    <i class="fas fa-chart-line"></i>

                </div>


            </div>


            <div class="chart-wrapper">

                <canvas id="weightChart"></canvas>

            </div>


        </div>


    @elseif($evaluations->count() === 1)


        <div class="chart-waiting-glass">


            <div>

                <i class="fas fa-chart-line"></i>

            </div>


            <span>
                EVOLUCIÓN
            </span>


            <h4>
                La gráfica estará disponible pronto
            </h4>


            <p>

                Registra una segunda evaluación para
                comenzar a visualizar la evolución del peso.

            </p>


        </div>


    @endif




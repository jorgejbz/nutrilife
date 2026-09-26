{{-- =========================================================
     PROGRESS INDEX — MÉTRICAS
     Indicadores generales de progreso
     ========================================================= --}}

    {{-- =====================================
         MÉTRICAS
    ====================================== --}}

    <div class="progress-stats">


        <div class="progress-stat">

            <div class="progress-stat-icon">

                <i class="fas fa-user-check"></i>

            </div>

            <div>

                <span>
                    Con seguimiento
                </span>

                <h3>
                    {{ $patientsWithProgress }}
                </h3>

            </div>

        </div>



        <div class="progress-stat">

            <div class="progress-stat-icon">

                <i class="fas fa-clipboard-list"></i>

            </div>

            <div>

                <span>
                    Evaluaciones
                </span>

                <h3>
                    {{ $totalEvaluations }}
                </h3>

            </div>

        </div>



        <div class="progress-stat">

            <div class="progress-stat-icon comparison">

                <i class="fas fa-chart-line"></i>

            </div>

            <div>

                <span>
                    Con comparativa
                </span>

                <h3>
                    {{ $patientsWithComparison }}
                </h3>

            </div>

        </div>


    </div>


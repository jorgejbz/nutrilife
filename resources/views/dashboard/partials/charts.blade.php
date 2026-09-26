{{-- =========================================================
     DASHBOARD CHARTS
     Gráficas principales y secundarias
     
     NOTA:
     Los canvas solamente se definen aquí.
     El JavaScript se agregará posteriormente.
     ========================================================= --}}

<div class="dashboard-charts">

    {{-- =====================================================
         CRECIMIENTO DE PACIENTES
         ===================================================== --}}
    <section class="dashboard-chart-card dashboard-chart-card--large">

        <div class="dashboard-chart-card__header">

            <div>
                <span class="dashboard-chart-card__eyebrow">
                    Pacientes
                </span>

                <h3 class="dashboard-chart-card__title">
                    Crecimiento de pacientes
                </h3>
            </div>

            <div class="dashboard-chart-card__icon">
                <i class="fas fa-user-plus"></i>
            </div>

        </div>

        <div class="dashboard-chart-card__body">
            <canvas id="patientsGrowthChart"></canvas>
        </div>

    </section>


    {{-- =====================================================
         ESTADO DE PACIENTES
         ===================================================== --}}
    <section class="dashboard-chart-card">

        <div class="dashboard-chart-card__header">

            <div>
                <span class="dashboard-chart-card__eyebrow">
                    Pacientes
                </span>

                <h3 class="dashboard-chart-card__title">
                    Estado de pacientes
                </h3>
            </div>

            <div class="dashboard-chart-card__icon">
                <i class="fas fa-chart-pie"></i>
            </div>

        </div>

        <div class="dashboard-chart-card__body">
            <canvas id="patientStatusChart"></canvas>
        </div>

    </section>


    {{-- =====================================================
         CITAS
         ===================================================== --}}
    <section class="dashboard-chart-card dashboard-chart-card--large">

        <div class="dashboard-chart-card__header">

            <div>
                <span class="dashboard-chart-card__eyebrow">
                    Agenda
                </span>

                <h3 class="dashboard-chart-card__title">
                    Citas de la semana
                </h3>
            </div>

            <div class="dashboard-chart-card__icon">
                <i class="fas fa-calendar-alt"></i>
            </div>

        </div>

        <div class="dashboard-chart-card__body">
            <canvas id="appointmentsChart"></canvas>
        </div>

    </section>


    {{-- =====================================================
         INGRESOS
         ===================================================== --}}
    <section class="dashboard-chart-card">

        <div class="dashboard-chart-card__header">

            <div>
                <span class="dashboard-chart-card__eyebrow">
                    Finanzas
                </span>

                <h3 class="dashboard-chart-card__title">
                    Ingresos mensuales
                </h3>
            </div>

            <div class="dashboard-chart-card__icon">
                <i class="fas fa-chart-line"></i>
            </div>

        </div>

        <div class="dashboard-chart-card__body">
            <canvas id="incomeChart"></canvas>
        </div>

    </section>

</div>
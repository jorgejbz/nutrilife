{{-- =========================================================
     DASHBOARD SUMMARY
     Resumen rápido de indicadores
     ========================================================= --}}

<section class="dashboard-summary">

    <div class="dashboard-summary__header">

        <div>
            <span class="dashboard-summary__eyebrow">
                Resumen
            </span>

            <h2 class="dashboard-summary__title">
                Actividad general
            </h2>
        </div>

        <i class="fas fa-chart-bar"></i>

    </div>

    <div class="dashboard-summary__items">

        <div class="dashboard-summary__item">

            <span class="dashboard-summary__item-icon">
                <i class="fas fa-users"></i>
            </span>

            <div>
                <strong>{{ $activePatients }}</strong>
                <span>Pacientes activos</span>
            </div>

        </div>


        <div class="dashboard-summary__item">

            <span class="dashboard-summary__item-icon">
                <i class="fas fa-calendar-check"></i>
            </span>

            <div>
                <strong>{{ $monthAppointments }}</strong>
                <span>Citas este mes</span>
            </div>

        </div>


        <div class="dashboard-summary__item">

            <span class="dashboard-summary__item-icon">
                <i class="fas fa-check-circle"></i>
            </span>

            <div>
                <strong>{{ $completedAppointments }}</strong>
                <span>Consultas completadas</span>
            </div>

        </div>

    </div>

</section>
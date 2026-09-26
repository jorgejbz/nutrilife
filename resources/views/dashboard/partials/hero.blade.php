{{-- =========================================================
     DASHBOARD HERO
     Resumen general del estado de pacientes
     ========================================================= --}}

<div class="dashboard-hero">

    {{-- Decoración --}}
    <div class="dashboard-hero__decoration dashboard-hero__decoration--one"></div>
    <div class="dashboard-hero__decoration dashboard-hero__decoration--two"></div>

    <div class="dashboard-hero__content">

        <div class="dashboard-hero__icon">
            <i class="fas fa-chart-line"></i>
        </div>

        <div class="dashboard-hero__text">

            <span class="dashboard-hero__eyebrow">
                Resumen nutricional
            </span>

            <h2 class="dashboard-hero__title">
                Tu clínica en un solo lugar
            </h2>

            <p class="dashboard-hero__description">
                Consulta rápidamente el estado de tus pacientes,
                citas y seguimiento nutricional.
            </p>

        </div>

        <div class="dashboard-hero__progress">

            <div class="dashboard-hero__circle"
                 style="--progress: {{ $activePercentage }}%;">

                <span>
                    {{ $activePercentage }}%
                </span>

            </div>

            <div class="dashboard-hero__progress-info">
                <strong>Pacientes activos</strong>
                <span>
                    {{ $activePatients }} de {{ $totalPatients }}
                </span>
            </div>

        </div>

    </div>

    {{-- Elementos decorativos --}}
    <span class="dashboard-hero__leaf dashboard-hero__leaf--one">
        <i class="fas fa-leaf"></i>
    </span>

    <span class="dashboard-hero__leaf dashboard-hero__leaf--two">
        <i class="fas fa-leaf"></i>
    </span>

</div>
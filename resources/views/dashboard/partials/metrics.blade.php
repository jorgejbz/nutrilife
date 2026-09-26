{{-- =========================================================
     DASHBOARD METRICS
     Tarjetas de indicadores principales
     ========================================================= --}}

<div class="dashboard-metrics">

    {{-- Pacientes activos --}}
    <div class="metric-card">

        <div class="metric-card__top">
            <div class="metric-card__icon metric-card__icon--patients">
                <i class="fas fa-user-friends"></i>
            </div>

            <span class="metric-card__growth metric-card__growth--positive">
                <i class="fas fa-arrow-up"></i>
                {{ abs($patientGrowth) }}%
            </span>
        </div>

        <span class="metric-card__label">Pacientes activos</span>
        <h3 class="metric-card__value">{{ $activePatients }}</h3>

        <div class="metric-card__footer">
            <span>{{ $totalPatients }} registrados</span>
            <strong>{{ $activePercentage }}%</strong>
        </div>

        <div class="metric-card__progress">
            <div
                class="metric-card__progress-bar"
                style="width: {{ $activePercentage }}%"
            ></div>
        </div>

    </div>


    {{-- Citas de hoy --}}
    <div class="metric-card">

        <div class="metric-card__top">
            <div class="metric-card__icon metric-card__icon--appointments">
                <i class="far fa-calendar-check"></i>
            </div>

            <span class="metric-card__growth metric-card__growth--positive">
                <i class="fas fa-arrow-up"></i>
                {{ abs($appointmentGrowth) }}%
            </span>
        </div>

        <span class="metric-card__label">Citas de hoy</span>
        <h3 class="metric-card__value">{{ $todayAppointmentsCount }}</h3>

        <div class="metric-card__footer">
            <span>{{ $monthAppointments }} este mes</span>
            <strong>Agenda</strong>
        </div>

        <div class="metric-card__progress">
            <div
                class="metric-card__progress-bar metric-card__progress-bar--appointments"
                style="width: {{ min($todayAppointmentsCount * 12, 100) }}%"
            ></div>
        </div>

    </div>


    {{-- Consultas completadas --}}
    <div class="metric-card">

        <div class="metric-card__top">
            <div class="metric-card__icon metric-card__icon--completed">
                <i class="fas fa-check-double"></i>
            </div>

            <span class="metric-card__mini">MES</span>
        </div>

        <span class="metric-card__label">Consultas completadas</span>
        <h3 class="metric-card__value">{{ $completedAppointments }}</h3>

        <div class="metric-card__footer">
            <span>Seguimiento realizado</span>
            <strong><i class="fas fa-check-circle"></i></strong>
        </div>

        <div class="metric-card__progress">
            <div
                class="metric-card__progress-bar metric-card__progress-bar--completed"
                style="width: {{ min($completedAppointments * 10, 100) }}%"
            ></div>
        </div>

    </div>


    {{-- Ingresos del mes --}}
    <div class="metric-card">

        <div class="metric-card__top">
            <div class="metric-card__icon metric-card__icon--money">
                <i class="fas fa-wallet"></i>
            </div>

            <span class="metric-card__growth {{ $incomeGrowth >= 0 ? 'metric-card__growth--positive' : 'metric-card__growth--negative' }}">
                <i class="fas {{ $incomeGrowth >= 0 ? 'fa-arrow-up' : 'fa-arrow-down' }}"></i>
                {{ abs($incomeGrowth) }}%
            </span>
        </div>

        <span class="metric-card__label">Ingresos del mes</span>
        <h3 class="metric-card__value metric-card__value--money">
            ${{ number_format($monthIncome, 0) }}
        </h3>

        <div class="metric-card__footer">
            <span>Ingresos registrados</span>
            <strong>MXN</strong>
        </div>

        <div class="metric-card__progress">
            <div
                class="metric-card__progress-bar metric-card__progress-bar--money"
                style="width: {{ $monthIncome > 0 ? 70 : 0 }}%"
            ></div>
        </div>

    </div>

</div>

{{-- =========================================================
     DASHBOARD METRICS — datos reales (App\Services\DashboardStats)
     ========================================================= --}}
<div class="dashboard-metrics">

    {{-- Pacientes activos (patients.active) --}}
    <div class="metric-card">
        <div class="metric-card__top">
            <div class="metric-card__icon metric-card__icon--patients">
                <i class="fas fa-user-friends"></i>
            </div>
            @if($newPatientsThisMonth > 0)
                <span class="metric-card__growth metric-card__growth--positive" title="Pacientes registrados este mes">
                    <i class="fas fa-plus"></i>
                    {{ $newPatientsThisMonth }} este mes
                </span>
            @endif
        </div>
        <span class="metric-card__label">Pacientes activos</span>
        <h3 class="metric-card__value">{{ $activePatients }}</h3>
        <div class="metric-card__footer">
            <span>{{ $totalPatients }} registrados · {{ $inactivePatients }} inactivos</span>
            <strong>{{ $activePercentage }}%</strong>
        </div>
        <div class="metric-card__progress" title="Porcentaje de pacientes activos">
            <div class="metric-card__progress-bar" style="width: {{ $activePercentage }}%"></div>
        </div>
    </div>

    {{-- Citas de hoy (appointments, sin canceladas) --}}
    <div class="metric-card">
        <div class="metric-card__top">
            <div class="metric-card__icon metric-card__icon--appointments">
                <i class="far fa-calendar-check"></i>
            </div>
            <span class="metric-card__mini" title="Citas pendientes o confirmadas en los próximos 7 días">
                {{ $nextSevenDays }} próx. 7 días
            </span>
        </div>
        <span class="metric-card__label">Citas de hoy</span>
        <h3 class="metric-card__value">{{ $todayAppointmentsCount }}</h3>
        <div class="metric-card__footer">
            <span>{{ $todayCompletedCount }} atendidas hoy · {{ $monthAppointments }} este mes</span>
            <strong>Agenda</strong>
        </div>
        <div class="metric-card__progress" title="Citas de hoy ya atendidas">
            <div class="metric-card__progress-bar metric-card__progress-bar--appointments"
                 style="width: {{ $todayAppointmentsCount > 0 ? round($todayCompletedCount / $todayAppointmentsCount * 100) : 0 }}%"></div>
        </div>
    </div>

    {{-- Consultas del mes (consultations) --}}
    <div class="metric-card">
        <div class="metric-card__top">
            <div class="metric-card__icon metric-card__icon--completed">
                <i class="fas fa-check-double"></i>
            </div>
            <span class="metric-card__mini">{{ mb_strtoupper(now()->locale('es')->translatedFormat('F')) }}</span>
        </div>
        <span class="metric-card__label">Consultas del mes</span>
        <h3 class="metric-card__value">{{ $monthConsultations }}</h3>
        <div class="metric-card__footer">
            @if($monthAttendance !== null)
                <span>Asistencia a citas del mes</span>
                <strong>{{ $monthAttendance }}%</strong>
            @else
                <span>Aún no hay citas pasadas este mes</span>
                <strong><i class="fas fa-minus"></i></strong>
            @endif
        </div>
        <div class="metric-card__progress" title="Citas del mes ya pasadas que quedaron completadas">
            <div class="metric-card__progress-bar metric-card__progress-bar--completed" style="width: {{ $monthAttendance ?? 0 }}%"></div>
        </div>
    </div>

    {{-- Ingresos del mes (payments Pagado) --}}
    <div class="metric-card">
        <div class="metric-card__top">
            <div class="metric-card__icon metric-card__icon--money">
                <i class="fas fa-wallet"></i>
            </div>
            @if($incomeGrowth !== null)
                <span class="metric-card__growth {{ $incomeGrowth >= 0 ? 'metric-card__growth--positive' : 'metric-card__growth--negative' }}" title="Contra el mes anterior">
                    <i class="fas {{ $incomeGrowth >= 0 ? 'fa-arrow-up' : 'fa-arrow-down' }}"></i>
                    {{ abs($incomeGrowth) }}%
                </span>
            @endif
        </div>
        <span class="metric-card__label">Ingresos del mes</span>
        <h3 class="metric-card__value metric-card__value--money">
            ${{ number_format($monthIncome, 0) }}
        </h3>
        <div class="metric-card__footer">
            @if($pendingPaymentsCount > 0)
                <span>{{ $pendingPaymentsCount }} {{ $pendingPaymentsCount === 1 ? 'pago pendiente' : 'pagos pendientes' }} · ${{ number_format($pendingPaymentsAmount, 0) }}</span>
            @else
                <span>Sin pagos pendientes</span>
            @endif
            <strong>MXN</strong>
        </div>
        @php
            $incomeBase = $monthIncome + $pendingPaymentsAmount;
        @endphp
        <div class="metric-card__progress" title="Cobrado del total registrado (cobrado + pendiente)">
            <div class="metric-card__progress-bar metric-card__progress-bar--money"
                 style="width: {{ $incomeBase > 0 ? round($monthIncome / $incomeBase * 100) : 0 }}%"></div>
        </div>
    </div>

</div>

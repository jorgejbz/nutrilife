{{-- =========================================================
     DASHBOARD CHARTS — datos de DashboardStats (window.dashboardData);
     las gráficas las dibuja resources/js/dashboard.js.
     ========================================================= --}}
<div class="dashboard-charts-head">
    <div>
        <span class="dashboard-section-heading__eyebrow">Tendencias</span>
        <h2 class="dashboard-section-heading__title">Últimos {{ $months }} meses</h2>
    </div>

    {{-- Periodo de las gráficas mensuales (?meses=6|12) --}}
    <form method="GET" action="{{ route('dashboard.index') }}" class="dashboard-period" data-auto-submit>
        @foreach($periods as $period)
            <label class="diet-chip {{ $months === $period ? 'is-selected' : '' }}">
                <input type="radio" name="meses" value="{{ $period }}" @checked($months === $period) onchange="this.form.submit()">
                {{ $period }} meses
            </label>
        @endforeach
        <noscript><button type="submit" class="diet-btn diet-btn--ghost">Aplicar</button></noscript>
    </form>
</div>

<div class="dashboard-charts">

    <section class="dashboard-chart-card dashboard-chart-card--large">
        <div class="dashboard-chart-card__header">
            <div>
                <span class="dashboard-chart-card__eyebrow">Pacientes</span>
                <h3 class="dashboard-chart-card__title">Pacientes nuevos y total acumulado</h3>
                <small class="dashboard-chart-card__hint">Altas de cada mes (barras) y total al cierre del mes (línea) · sin eliminados</small>
            </div>
            {{-- Atajo al mismo formulario de "Acciones rápidas" (pacientes.create) --}}
            <a href="{{ route('pacientes.create') }}" class="dashboard-chart-card__icon is-action" title="Registrar paciente" aria-label="Registrar paciente nuevo"><i class="fas fa-user-plus" aria-hidden="true"></i></a>
        </div>
        <div class="dashboard-chart-card__body">
            @if(array_sum($charts['patients']['total']) === 0)
                <p class="dashboard-chart-empty">Aún no hay pacientes registrados.</p>
            @else
                <div class="dashboard-chart-canvas">
                    <canvas id="patientsGrowthChart" role="img" aria-label="Pacientes nuevos y total acumulado por mes"></canvas>
                </div>
                <ul class="dashboard-legend">
                    <li><span class="is-cat-1"></span> Total acumulado <strong>{{ last($charts['patients']['total']) }}</strong></li>
                    <li><span class="is-cat-2 is-square"></span> Nuevos en el periodo <strong>{{ array_sum($charts['patients']['new']) }}</strong></li>
                </ul>
            @endif
        </div>
    </section>

    <section class="dashboard-chart-card">
        <div class="dashboard-chart-card__header">
            <div>
                <span class="dashboard-chart-card__eyebrow">Pacientes</span>
                <h3 class="dashboard-chart-card__title">Estado de pacientes</h3>
            </div>
            <div class="dashboard-chart-card__icon"><i class="fas fa-chart-pie"></i></div>
        </div>
        <div class="dashboard-chart-card__body dashboard-chart-card__body--donut">
            @if($totalPatients === 0)
                <p class="dashboard-chart-empty">Sin pacientes.</p>
            @else
                <canvas id="patientStatusChart" role="img" aria-label="{{ $activePatients }} activos y {{ $inactivePatients }} inactivos"></canvas>
                <ul class="dashboard-legend">
                    <li><span class="is-active"></span> Activos <strong>{{ $activePatients }}</strong></li>
                    <li><span class="is-inactive"></span> Inactivos <strong>{{ $inactivePatients }}</strong></li>
                </ul>
            @endif
        </div>
    </section>

    <section class="dashboard-chart-card dashboard-chart-card--large">
        <div class="dashboard-chart-card__header">
            <div>
                <span class="dashboard-chart-card__eyebrow">Agenda</span>
                <h3 class="dashboard-chart-card__title">Citas de esta semana</h3>
                <small class="dashboard-chart-card__hint">{{ $charts['appointments']['range'] }} · sin canceladas</small>
            </div>
            <div class="dashboard-chart-card__icon"><i class="fas fa-calendar-alt"></i></div>
        </div>
        <div class="dashboard-chart-card__body">
            @if(array_sum($charts['appointments']['data']) === 0)
                <p class="dashboard-chart-empty">No hay citas esta semana.</p>
            @else
                <canvas id="appointmentsChart" role="img" aria-label="Citas por día de esta semana"></canvas>
            @endif
        </div>
    </section>

    <section class="dashboard-chart-card">
        <div class="dashboard-chart-card__header">
            <div>
                <span class="dashboard-chart-card__eyebrow">Finanzas</span>
                <h3 class="dashboard-chart-card__title">Ingresos cobrados vs. pendientes</h3>
                <small class="dashboard-chart-card__hint">Por mes del pago · MXN · sin cancelados</small>
            </div>
            <div class="dashboard-chart-card__icon"><i class="fas fa-chart-column"></i></div>
        </div>
        <div class="dashboard-chart-card__body">
            @if(array_sum($charts['income']['data']) + array_sum($charts['income']['pending']) == 0)
                <p class="dashboard-chart-empty">Aún no hay pagos cobrados ni pendientes en este periodo.</p>
            @else
                <div class="dashboard-chart-canvas">
                    <canvas id="incomeChart" role="img" aria-label="Ingresos cobrados y pendientes por mes"></canvas>
                </div>
                <ul class="dashboard-legend">
                    <li><span class="is-cat-1 is-square"></span> Cobrado <strong>${{ number_format(array_sum($charts['income']['data']), 0) }}</strong></li>
                    <li><span class="is-cat-2 is-square"></span> Pendiente <strong>${{ number_format(array_sum($charts['income']['pending']), 0) }}</strong></li>
                </ul>
            @endif
        </div>
    </section>

</div>

{{-- =========================================================
     DASHBOARD — ANÁLISIS (App\Services\DashboardStats)
     Agenda, consultas y finanzas del periodo (?meses=6|12) y el
     perfil de los pacientes vigentes. Las gráficas Chart.js las
     dibuja resources/js/dashboard.js; el mapa de demanda es una
     tabla HTML (también sirve como vista accesible de los datos).
     Cada gráfica con varias series lleva leyenda con sus totales.
     ========================================================= --}}
@php
    $statusSeries = $charts['appointmentStatus']['series'];
    $statusTotals = array_map('array_sum', $statusSeries);
    $methodTotal = array_sum($charts['paymentMethods']['data']);
    $demoTotal = array_sum(array_map('array_sum', $charts['demographics']['series']));
    $sexTotals = array_map('array_sum', $charts['demographics']['series']);
    $bmiTotal = array_sum($charts['bmi']['data']);
    $heatLevel = function (int $count) use ($demand): int {
        if ($count === 0 || $demand['max'] === 0) {
            return 0;
        }

        return max(1, (int) ceil($count / $demand['max'] * 4));
    };
@endphp

{{-- ======================= AGENDA Y FINANZAS ======================= --}}
<div class="dashboard-charts-head">
    <div>
        <span class="dashboard-section-heading__eyebrow">Agenda y finanzas</span>
        <h2 class="dashboard-section-heading__title">Detalle de los últimos {{ $months }} meses</h2>
    </div>
</div>

<div class="dashboard-charts">

    {{-- 4. Estado de las citas por mes --}}
    <section class="dashboard-chart-card dashboard-chart-card--large">
        <div class="dashboard-chart-card__header">
            <div>
                <span class="dashboard-chart-card__eyebrow">Agenda</span>
                <h3 class="dashboard-chart-card__title">Estado de las citas por mes</h3>
                <small class="dashboard-chart-card__hint">Todas las citas del periodo, incluidas las canceladas</small>
            </div>
            <div class="dashboard-kpi-pill" title="Citas canceladas entre todas las del periodo">
                <span>Canceladas</span>
                <strong>{{ $cancellationRate === null ? '—' : $cancellationRate.'%' }}</strong>
            </div>
        </div>
        <div class="dashboard-chart-card__body">
            @if(array_sum($statusTotals) === 0)
                <p class="dashboard-chart-empty">No hay citas en este periodo.</p>
            @else
                <div class="dashboard-chart-canvas">
                    <canvas id="appointmentStatusChart" role="img" aria-label="Citas por estado y mes"></canvas>
                </div>
                <ul class="dashboard-legend">
                    @foreach(array_keys($statusSeries) as $i => $status)
                        <li><span class="is-cat-{{ $i + 1 }} is-square"></span> {{ $status }} <strong>{{ $statusTotals[$status] }}</strong></li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>

    {{-- 2. Ingresos por método de pago --}}
    <section class="dashboard-chart-card">
        <div class="dashboard-chart-card__header">
            <div>
                <span class="dashboard-chart-card__eyebrow">Finanzas</span>
                <h3 class="dashboard-chart-card__title">Ingresos por método de pago</h3>
                <small class="dashboard-chart-card__hint">Cobrado en el periodo · MXN</small>
            </div>
            <div class="dashboard-chart-card__icon"><i class="fas fa-wallet"></i></div>
        </div>
        <div class="dashboard-chart-card__body">
            @if($methodTotal == 0)
                <p class="dashboard-chart-empty">Aún no hay pagos cobrados en este periodo.</p>
            @else
                <canvas id="paymentMethodsChart" role="img" aria-label="Ingresos por método de pago: {{ collect($charts['paymentMethods']['labels'])->map(fn ($label, $i) => $label.' $'.number_format($charts['paymentMethods']['data'][$i], 0))->join(', ') }}"></canvas>
            @endif
        </div>
    </section>

    {{-- 5. Consultas realizadas por mes --}}
    <section class="dashboard-chart-card dashboard-chart-card--large">
        <div class="dashboard-chart-card__header">
            <div>
                <span class="dashboard-chart-card__eyebrow">Consultas</span>
                <h3 class="dashboard-chart-card__title">Consultas realizadas por mes</h3>
                <small class="dashboard-chart-card__hint">{{ array_sum($charts['consultations']['data']) }} en el periodo</small>
            </div>
            <a href="{{ route('consultas.index') }}" class="dashboard-chart-card__icon is-action" title="Ver consultas" aria-label="Ver consultas"><i class="fas fa-stethoscope" aria-hidden="true"></i></a>
        </div>
        <div class="dashboard-chart-card__body">
            @if(array_sum($charts['consultations']['data']) === 0)
                <p class="dashboard-chart-empty">No hay consultas registradas en este periodo.</p>
            @else
                <canvas id="consultationsChart" role="img" aria-label="Consultas realizadas por mes"></canvas>
            @endif
        </div>
    </section>

    {{-- 9. Evolución de peso --}}
    <section class="dashboard-chart-card">
        <div class="dashboard-chart-card__header">
            <div>
                <span class="dashboard-chart-card__eyebrow">Progreso</span>
                <h3 class="dashboard-chart-card__title">Evolución de peso</h3>
                <small class="dashboard-chart-card__hint">Primera vs. última evaluación · pacientes con 2 o más</small>
            </div>
            <div class="dashboard-chart-card__icon"><i class="fas fa-weight-scale"></i></div>
        </div>
        <div class="dashboard-chart-card__body">
            @if($weightEvolution['patients'] === 0)
                <p class="dashboard-chart-empty">Se necesita al menos un paciente con dos evaluaciones de peso.</p>
            @else
                <div class="dashboard-weight-kpi">
                    <div>
                        <strong>{{ $weightEvolution['lost_pct'] }}%</strong>
                        <span>bajó de peso</span>
                    </div>
                    <div>
                        <strong>{{ $weightEvolution['average_change'] > 0 ? '+' : '' }}{{ number_format($weightEvolution['average_change'], 1) }} kg</strong>
                        <span>cambio promedio</span>
                    </div>
                </div>
                <div class="dashboard-chart-canvas">
                    <canvas id="weightChart" role="img" aria-label="{{ $weightEvolution['lost'] }} bajaron, {{ $weightEvolution['kept'] }} mantuvieron y {{ $weightEvolution['gained'] }} subieron de peso"></canvas>
                </div>
                <small class="dashboard-chart-note">{{ $weightEvolution['patients'] }} {{ $weightEvolution['patients'] === 1 ? 'paciente' : 'pacientes' }} · «mantuvo» = menos de ±{{ number_format(\App\Services\DashboardStats::WEIGHT_STABLE_KG, 1) }} kg</small>
            @endif
        </div>
    </section>

</div>

{{-- ======================= PERFIL DE PACIENTES ======================= --}}
<div class="dashboard-charts-head">
    <div>
        <span class="dashboard-section-heading__eyebrow">Perfil de pacientes</span>
        <h2 class="dashboard-section-heading__title">Quiénes son y cuándo te visitan</h2>
    </div>
</div>

<div class="dashboard-charts">

    {{-- 6. Pacientes por sexo y rango de edad --}}
    <section class="dashboard-chart-card dashboard-chart-card--large">
        <div class="dashboard-chart-card__header">
            <div>
                <span class="dashboard-chart-card__eyebrow">Pacientes</span>
                <h3 class="dashboard-chart-card__title">Pacientes por sexo y rango de edad</h3>
                <small class="dashboard-chart-card__hint">
                    Edad calculada desde la fecha de nacimiento
                    @if($charts['demographics']['missing'] > 0)
                        · {{ $charts['demographics']['missing'] }} sin fecha de nacimiento o sexo (no se incluyen)
                    @endif
                </small>
            </div>
            <div class="dashboard-chart-card__icon"><i class="fas fa-users"></i></div>
        </div>
        <div class="dashboard-chart-card__body">
            @if($demoTotal === 0)
                <p class="dashboard-chart-empty">Registra la fecha de nacimiento y el sexo de tus pacientes para ver esta gráfica.</p>
            @else
                <div class="dashboard-chart-canvas">
                    <canvas id="demographicsChart" role="img" aria-label="Pacientes por sexo y rango de edad"></canvas>
                </div>
                <ul class="dashboard-legend">
                    {{-- 3 series: azul, ámbar, rosa (cat-2..4, validadas entre sí) --}}
                    @foreach($sexTotals as $sex => $count)
                        <li><span class="is-cat-{{ $loop->index + 2 }} is-square"></span> {{ $sex }} <strong>{{ $count }}</strong></li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>

    {{-- 8. Clasificación de IMC --}}
    <section class="dashboard-chart-card">
        <div class="dashboard-chart-card__header">
            <div>
                <span class="dashboard-chart-card__eyebrow">Clínico</span>
                <h3 class="dashboard-chart-card__title">Clasificación de IMC</h3>
                <small class="dashboard-chart-card__hint">Última evaluación de cada paciente · OMS</small>
            </div>
            <div class="dashboard-chart-card__icon"><i class="fas fa-ruler-vertical"></i></div>
        </div>
        <div class="dashboard-chart-card__body">
            @if($bmiTotal === 0)
                <p class="dashboard-chart-empty">Aún no hay evaluaciones con IMC.</p>
            @else
                <canvas id="bmiChart" role="img" aria-label="Pacientes por categoría de IMC: {{ collect($charts['bmi']['labels'])->map(fn ($label, $i) => $label.' '.$charts['bmi']['data'][$i])->join(', ') }}"></canvas>
            @endif
        </div>
    </section>

    {{-- 10. Demanda por día y hora (mapa de calor) --}}
    <section class="dashboard-chart-card dashboard-chart-card--large">
        <div class="dashboard-chart-card__header">
            <div>
                <span class="dashboard-chart-card__eyebrow">Agenda</span>
                <h3 class="dashboard-chart-card__title">Demanda por día y hora</h3>
                <small class="dashboard-chart-card__hint">{{ $demand['total'] }} citas del periodo, sin canceladas · más intenso = más citas</small>
            </div>
            <div class="dashboard-chart-card__icon"><i class="fas fa-table-cells"></i></div>
        </div>
        <div class="dashboard-chart-card__body dashboard-chart-card__body--auto">
            @if($demand['total'] === 0)
                <p class="dashboard-chart-empty">No hay citas en este periodo.</p>
            @else
                <div class="dashboard-heatmap-scroll">
                    <table class="dashboard-heatmap">
                        <caption class="visually-hidden">Citas por día de la semana y hora</caption>
                        <thead>
                            <tr>
                                <th scope="col"><span class="visually-hidden">Hora</span></th>
                                @foreach($demand['days'] as $day)
                                    <th scope="col">{{ $day }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($demand['hours'] as $h => $hour)
                                <tr>
                                    <th scope="row">{{ str_pad($hour, 2, '0', STR_PAD_LEFT) }}:00</th>
                                    @foreach($demand['days'] as $d => $day)
                                        @php $count = $demand['grid'][$d][$h]; @endphp
                                        <td class="is-level-{{ $heatLevel($count) }}"
                                            title="{{ $day }} {{ str_pad($hour, 2, '0', STR_PAD_LEFT) }}:00 — {{ $count }} {{ $count === 1 ? 'cita' : 'citas' }}">
                                            {{ $count ?: '' }}
                                            @if($count === 0)<span class="visually-hidden">0</span>@endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>

    {{-- 7. Patologías más frecuentes --}}
    <section class="dashboard-chart-card">
        <div class="dashboard-chart-card__header">
            <div>
                <span class="dashboard-chart-card__eyebrow">Clínico</span>
                <h3 class="dashboard-chart-card__title">Patologías más frecuentes</h3>
                <small class="dashboard-chart-card__hint">Pacientes por patología · top {{ \App\Services\DashboardStats::TOP_PATHOLOGIES }}</small>
            </div>
            <div class="dashboard-chart-card__icon"><i class="fas fa-notes-medical"></i></div>
        </div>
        <div class="dashboard-chart-card__body">
            @if(empty($charts['pathologies']['data']))
                <p class="dashboard-chart-empty">Ningún paciente tiene patologías registradas.</p>
            @else
                <canvas id="pathologiesChart" role="img" aria-label="Patologías más frecuentes: {{ collect($charts['pathologies']['labels'])->map(fn ($label, $i) => $label.' '.$charts['pathologies']['data'][$i])->join(', ') }}"></canvas>
            @endif
        </div>
    </section>

</div>

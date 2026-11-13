@extends('adminlte::page')

@section('title', 'Reportes avanzados | NutriLife')

@php
    $labels = \App\Services\AdvancedReport::kpiLabels();
    $money = fn ($v) => $v === null ? '—' : '$'.number_format((float) $v, 2, '.', ',');
    $num = fn ($v, $d = 0) => $v === null ? '—' : number_format((float) $v, $d, '.', ',');
    $signed = fn ($v, $d = 1) => $v === null ? '—' : ($v > 0 ? '+' : '').number_format((float) $v, $d, '.', ',');
    $kpiFormat = function ($key, $v) use ($money, $num) {
        return match (true) {
            in_array($key, ['income', 'average_ticket', 'pending_amount'], true) => $money($v),
            in_array($key, ['attendance_rate', 'cancellation_rate'], true) => $v === null ? '—' : $num($v, 1).' %',
            default => $num($v),
        };
    };
    // Indicadores donde subir es malo (el color del cambio se invierte)
    $lowerIsBetter = ['cancelled', 'cancellation_rate', 'pending_amount'];
    $mainKpis = ['income', 'consultations', 'patients_attended', 'new_patients', 'attendance_rate', 'average_ticket', 'returning_patients', 'cancellation_rate'];
    $query = $builder->query();
    $maxIncome = max(1, collect($report['monthly'])->max('income'));
    $maxConsult = max(1, collect($report['monthly'])->max('consultations'));
    $maxWeekday = max(1, collect($report['appointments_by_weekday'])->max('count'));
    $clinical = $report['clinical'];
@endphp

@section('content_header')
<div class="rp-header">
    <div>
        <span class="rp-eyebrow"><i class="fas fa-chart-pie me-1"></i> REPORTES AVANZADOS</span>
        <h1>Reportes de tu consulta</h1>
        <p>{{ $report['period'] }} · comparado con {{ $report['previous_period'] }}</p>
    </div>
    <div class="rp-actions">
        <a href="{{ route('reports.csv', $query) }}" class="rp-btn">
            <i class="fas fa-file-csv"></i> Descargar CSV
        </a>
        @can('pdf-export')
            <a href="{{ route('reports.pdf', $query) }}" class="rp-btn rp-btn--primary">
                <i class="far fa-file-pdf"></i> Exportar PDF
            </a>
        @endcan
    </div>
</div>
@stop

@section('content')
<div class="rp-page">

    {{-- Filtro de periodo --}}
    <form method="GET" action="{{ route('reports.index') }}" class="rp-card rp-filter">
        <div class="rp-presets" role="group" aria-label="Periodos rápidos">
            @foreach ($presets as $key => $label)
                <a href="{{ route('reports.index', ['periodo' => $key]) }}"
                   @class(['rp-chip', 'is-active' => $builder->preset() === $key])>{{ $label }}</a>
            @endforeach
        </div>
        <div class="rp-range">
            <label>
                <span>Desde</span>
                <input type="date" name="desde" value="{{ $builder->from()->toDateString() }}" required>
            </label>
            <label>
                <span>Hasta</span>
                <input type="date" name="hasta" value="{{ $builder->to()->toDateString() }}" required>
            </label>
            <button type="submit" class="rp-btn rp-btn--primary"><i class="fas fa-filter"></i> Aplicar</button>
        </div>
    </form>

    {{-- Indicadores principales con variación --}}
    <div class="rp-kpis">
        @foreach ($mainKpis as $key)
            @php($kpi = $report['kpis'][$key])
            @php($good = $kpi['change'] === null ? null : (in_array($key, $lowerIsBetter, true) ? $kpi['change'] <= 0 : $kpi['change'] >= 0))
            <div class="rp-card rp-kpi">
                <span class="rp-kpi__label">{{ $labels[$key] }}</span>
                <strong class="rp-kpi__value">{{ $kpiFormat($key, $kpi['value']) }}</strong>
                <span class="rp-kpi__prev">
                    @if ($kpi['change'] === null)
                        Antes: {{ $kpiFormat($key, $kpi['previous']) }}
                    @else
                        <span @class(['rp-delta', 'is-good' => $good, 'is-bad' => ! $good])>
                            <i class="fas fa-arrow-{{ $kpi['change'] >= 0 ? 'up' : 'down' }}"></i>
                            {{ $signed($kpi['change']) }} %
                        </span>
                        vs {{ $kpiFormat($key, $kpi['previous']) }}
                    @endif
                </span>
            </div>
        @endforeach
    </div>

    {{-- Serie mensual --}}
    <section class="rp-card">
        <div class="rp-card__head">
            <div>
                <span class="rp-card__eyebrow">TENDENCIA</span>
                <h3>Evolución mensual</h3>
            </div>
            <div class="rp-legend">
                <span><i class="rp-dot rp-dot--a"></i> Ingresos</span>
                <span><i class="rp-dot rp-dot--b"></i> Consultas</span>
            </div>
        </div>
        <div class="rp-table-wrap">
            <table class="rp-table">
                <thead>
                    <tr>
                        <th>Mes</th>
                        <th class="num">Pacientes nuevos</th>
                        <th class="num">Consultas</th>
                        <th class="num">Ingresos</th>
                        <th class="rp-col-bars"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($report['monthly'] as $m)
                        <tr>
                            <td>{{ $m['label'] }}</td>
                            <td class="num">{{ $m['new_patients'] }}</td>
                            <td class="num">{{ $m['consultations'] }}</td>
                            <td class="num">{{ $money($m['income']) }}</td>
                            <td class="rp-col-bars">
                                <div class="rp-bar"><div class="rp-bar__fill rp-bar__fill--a" style="width: {{ round($m['income'] / $maxIncome * 100) }}%"></div></div>
                                <div class="rp-bar"><div class="rp-bar__fill rp-bar__fill--b" style="width: {{ round($m['consultations'] / $maxConsult * 100) }}%"></div></div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <div class="rp-grid">
        {{-- Ingresos por método --}}
        <section class="rp-card">
            <span class="rp-card__eyebrow">INGRESOS</span>
            <h3>Por método de pago</h3>
            @forelse ($report['income_by_method'] as $r)
                <div class="rp-row">
                    <div class="rp-row__top"><span>{{ $r['label'] }}</span><strong>{{ $money($r['amount']) }}</strong></div>
                    <div class="rp-bar"><div class="rp-bar__fill rp-bar__fill--a" style="width: {{ $r['share'] }}%"></div></div>
                    <small>{{ $r['count'] }} {{ $r['count'] === 1 ? 'pago' : 'pagos' }} · {{ $num($r['share'], 1) }} %</small>
                </div>
            @empty
                <p class="rp-empty">Sin pagos cobrados en el periodo.</p>
            @endforelse
        </section>

        {{-- Ingresos por concepto --}}
        <section class="rp-card">
            <span class="rp-card__eyebrow">INGRESOS</span>
            <h3>Por concepto</h3>
            @forelse ($report['income_by_concept'] as $r)
                <div class="rp-row">
                    <div class="rp-row__top"><span>{{ $r['label'] }}</span><strong>{{ $money($r['amount']) }}</strong></div>
                    <div class="rp-bar"><div class="rp-bar__fill rp-bar__fill--a" style="width: {{ $r['share'] }}%"></div></div>
                    <small>{{ $r['count'] }} {{ $r['count'] === 1 ? 'pago' : 'pagos' }} · {{ $num($r['share'], 1) }} %</small>
                </div>
            @empty
                <p class="rp-empty">Sin pagos cobrados en el periodo.</p>
            @endforelse
        </section>

        {{-- Citas por estado --}}
        <section class="rp-card">
            <span class="rp-card__eyebrow">AGENDA</span>
            <h3>Citas por estado</h3>
            @foreach ($report['appointments_by_status'] as $r)
                <div class="rp-row">
                    <div class="rp-row__top"><span>{{ $r['label'] }}</span><strong>{{ $r['count'] }}</strong></div>
                    <div class="rp-bar"><div @class(['rp-bar__fill', 'rp-bar__fill--b', 'rp-bar__fill--bad' => $r['label'] === 'Cancelada']) style="width: {{ $r['share'] }}%"></div></div>
                    <small>{{ $num($r['share'], 1) }} % de las citas</small>
                </div>
            @endforeach
        </section>

        {{-- Citas por día --}}
        <section class="rp-card">
            <span class="rp-card__eyebrow">AGENDA</span>
            <h3>Días con más citas</h3>
            @foreach ($report['appointments_by_weekday'] as $r)
                <div class="rp-row rp-row--inline">
                    <span class="rp-row__label">{{ $r['label'] }}</span>
                    <div class="rp-bar"><div class="rp-bar__fill rp-bar__fill--b" style="width: {{ round($r['count'] / $maxWeekday * 100) }}%"></div></div>
                    <strong>{{ $r['count'] }}</strong>
                </div>
            @endforeach
        </section>
    </div>

    {{-- Resultados clínicos --}}
    <section class="rp-card">
        <div class="rp-card__head">
            <div>
                <span class="rp-card__eyebrow">RESULTADOS CLÍNICOS</span>
                <h3>Evolución de tus pacientes</h3>
                <p class="rp-note">Pacientes con dos o más evaluaciones dentro del periodo (primera contra última).</p>
            </div>
        </div>
        <div class="rp-mini-kpis">
            <div><span>Evaluaciones</span><strong>{{ $clinical['evaluations'] }}</strong></div>
            <div><span>Pacientes con seguimiento</span><strong>{{ $clinical['patients'] }}</strong></div>
            <div><span>Cambio de peso promedio</span><strong>{{ $clinical['avg_weight_change'] === null ? '—' : $signed($clinical['avg_weight_change']).' kg' }}</strong></div>
            <div><span>Kilos perdidos en total</span><strong>{{ $num($clinical['total_kg_lost'], 1) }} kg</strong></div>
            <div><span>Bajó · mantuvo · subió</span><strong>{{ $clinical['lost'] }} · {{ $clinical['maintained'] }} · {{ $clinical['gained'] }}</strong></div>
        </div>
        @if (! empty($clinical['rows']))
            <div class="rp-table-wrap">
                <table class="rp-table">
                    <thead>
                        <tr>
                            <th>Paciente</th>
                            <th class="num">Evaluaciones</th>
                            <th class="num">Peso inicial</th>
                            <th class="num">Peso final</th>
                            <th class="num">Δ Peso</th>
                            <th class="num">Δ IMC</th>
                            <th class="num">Δ Grasa</th>
                            <th class="num">Δ Cintura</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($clinical['rows'] as $r)
                            <tr>
                                <td>{{ $r['name'] }}</td>
                                <td class="num">{{ $r['evaluations'] }}</td>
                                <td class="num">{{ $num($r['weight_start'], 1) }} kg</td>
                                <td class="num">{{ $num($r['weight_end'], 1) }} kg</td>
                                <td @class(['num', 'rp-good' => ($r['weight_change'] ?? 0) < 0, 'rp-bad' => ($r['weight_change'] ?? 0) > 0])>{{ $signed($r['weight_change']) }} kg</td>
                                <td class="num">{{ $signed($r['bmi_change']) }}</td>
                                <td class="num">{{ $r['fat_change'] === null ? '—' : $signed($r['fat_change']).' %' }}</td>
                                <td class="num">{{ $r['waist_change'] === null ? '—' : $signed($r['waist_change']).' cm' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="rp-empty">Todavía no hay pacientes con dos evaluaciones en este periodo.</p>
        @endif
    </section>

    <div class="rp-grid">
        {{-- Pacientes con más ingresos --}}
        <section class="rp-card">
            <span class="rp-card__eyebrow">PACIENTES</span>
            <h3>Con más ingresos</h3>
            @forelse ($report['top_patients'] as $i => $r)
                <div class="rp-rank">
                    <span class="rp-rank__pos">{{ $i + 1 }}</span>
                    <span class="rp-rank__name">{{ $r['name'] }}<small>{{ $r['count'] }} {{ $r['count'] === 1 ? 'pago' : 'pagos' }}</small></span>
                    <strong>{{ $money($r['amount']) }}</strong>
                </div>
            @empty
                <p class="rp-empty">Sin pagos cobrados en el periodo.</p>
            @endforelse
        </section>

        {{-- Planes alimenticios --}}
        <section class="rp-card">
            <span class="rp-card__eyebrow">NUTRICIÓN</span>
            <h3>Planes alimenticios</h3>
            <div class="rp-mini-kpis rp-mini-kpis--2">
                <div><span>Creados en el periodo</span><strong>{{ $report['diet_plans']['created'] }}</strong></div>
                <div><span>Pacientes con plan nuevo</span><strong>{{ $report['diet_plans']['patients'] }}</strong></div>
                <div><span>Planes activos hoy</span><strong>{{ $report['diet_plans']['active_now'] }}</strong></div>
                <div><span>Meta calórica promedio</span><strong>{{ $report['diet_plans']['avg_target_kcal'] === null ? '—' : $num($report['diet_plans']['avg_target_kcal']).' kcal' }}</strong></div>
            </div>
        </section>
    </div>

</div>
@stop

@section('css')
    {{-- Estilos del panel: tokens + public/css/admin/* + temas + responsive (config/stylesheets.php) --}}
    @include('partials.theme-styles', ['section' => 'admin'])
@stop

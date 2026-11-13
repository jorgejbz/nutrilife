@extends('pdf.layout')

@php
    $labels = \App\Services\AdvancedReport::kpiLabels();
    $money = fn ($v) => $v === null ? '—' : '$'.number_format((float) $v, 2, '.', ',');
    $num = fn ($v, $d = 0) => $v === null ? '—' : number_format((float) $v, $d, '.', ',');
    $kpiFormat = function ($key, $v) use ($money, $num) {
        return match (true) {
            in_array($key, ['income', 'average_ticket', 'pending_amount'], true) => $money($v),
            in_array($key, ['attendance_rate', 'cancellation_rate'], true) => $v === null ? '—' : $num($v, 1).' %',
            default => $num($v),
        };
    };
    $maxIncome = max(1, collect($report['monthly'])->max('income'));
    $maxWeekday = max(1, collect($report['appointments_by_weekday'])->max('count'));
    $clinical = $report['clinical'];
@endphp

@section('content')
    <h1>Reporte avanzado</h1>
    <p class="subtitle">Periodo: {{ $report['period'] }} · Comparado con: {{ $report['previous_period'] }}</p>

    <h2>Indicadores principales</h2>
    <table class="data">
        <tr>
            <th style="width: 40%;">Indicador</th>
            <th class="num">Periodo</th>
            <th class="num">Periodo anterior</th>
            <th class="num">Cambio</th>
        </tr>
        @foreach ($report['kpis'] as $key => $kpi)
            <tr @class(['alt' => $loop->even])>
                <td>{{ $labels[$key] ?? $key }}</td>
                <td class="num"><strong>{{ $kpiFormat($key, $kpi['value']) }}</strong></td>
                <td class="num">{{ $kpiFormat($key, $kpi['previous']) }}</td>
                <td class="num">
                    @if ($kpi['change'] === null)
                        —
                    @else
                        <span class="{{ $kpi['change'] >= 0 ? 'up' : 'down' }}">{{ $kpi['change'] > 0 ? '+' : '' }}{{ $num($kpi['change'], 1) }} %</span>
                    @endif
                </td>
            </tr>
        @endforeach
    </table>

    <div class="keep">
        <h2>Evolución mensual</h2>
        <table class="data">
            <tr>
                <th style="width: 14%;">Mes</th>
                <th class="num" style="width: 14%;">Pacientes nuevos</th>
                <th class="num" style="width: 12%;">Consultas</th>
                <th class="num" style="width: 18%;">Ingresos</th>
                <th style="width: 42%;"></th>
            </tr>
            @foreach ($report['monthly'] as $m)
                <tr>
                    <td>{{ $m['label'] }}</td>
                    <td class="num">{{ $m['new_patients'] }}</td>
                    <td class="num">{{ $m['consultations'] }}</td>
                    <td class="num">{{ $money($m['income']) }}</td>
                    <td><div class="bar"><div style="width: {{ round($m['income'] / $maxIncome * 100) }}%;"></div></div></td>
                </tr>
            @endforeach
        </table>
    </div>

    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="width: 50%; vertical-align: top; padding-right: 8px;">
                <h2>Ingresos por método de pago</h2>
                <table class="data">
                    <tr><th>Método</th><th class="num">Monto</th><th class="num">Pagos</th><th class="num">%</th></tr>
                    @forelse ($report['income_by_method'] as $r)
                        <tr><td>{{ $r['label'] }}</td><td class="num">{{ $money($r['amount']) }}</td><td class="num">{{ $r['count'] }}</td><td class="num">{{ $num($r['share'], 1) }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="muted">Sin pagos cobrados en el periodo.</td></tr>
                    @endforelse
                </table>
            </td>
            <td style="width: 50%; vertical-align: top; padding-left: 8px;">
                <h2>Ingresos por concepto</h2>
                <table class="data">
                    <tr><th>Concepto</th><th class="num">Monto</th><th class="num">%</th></tr>
                    @forelse ($report['income_by_concept'] as $r)
                        <tr><td>{{ $r['label'] }}</td><td class="num">{{ $money($r['amount']) }}</td><td class="num">{{ $num($r['share'], 1) }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="muted">Sin pagos cobrados en el periodo.</td></tr>
                    @endforelse
                </table>
            </td>
        </tr>
    </table>

    <table style="width: 100%; border-collapse: collapse;" class="keep">
        <tr>
            <td style="width: 50%; vertical-align: top; padding-right: 8px;">
                <h2>Citas por estado</h2>
                <table class="data">
                    <tr><th>Estado</th><th class="num">Citas</th><th class="num">%</th></tr>
                    @foreach ($report['appointments_by_status'] as $r)
                        <tr><td>{{ $r['label'] }}</td><td class="num">{{ $r['count'] }}</td><td class="num">{{ $num($r['share'], 1) }}</td></tr>
                    @endforeach
                </table>
            </td>
            <td style="width: 50%; vertical-align: top; padding-left: 8px;">
                <h2>Citas por día de la semana</h2>
                <table class="data">
                    @foreach ($report['appointments_by_weekday'] as $r)
                        <tr>
                            <td style="width: 32%;">{{ $r['label'] }}</td>
                            <td style="width: 52%;"><div class="bar"><div style="width: {{ round($r['count'] / $maxWeekday * 100) }}%;"></div></div></td>
                            <td class="num">{{ $r['count'] }}</td>
                        </tr>
                    @endforeach
                </table>
            </td>
        </tr>
    </table>

    @if (! empty($report['top_patients']))
        <div class="keep">
            <h2>Pacientes con más ingresos</h2>
            <table class="data">
                <tr><th>Paciente</th><th class="num">Pagos</th><th class="num">Monto</th></tr>
                @foreach ($report['top_patients'] as $r)
                    <tr><td>{{ $r['name'] }}</td><td class="num">{{ $r['count'] }}</td><td class="num">{{ $money($r['amount']) }}</td></tr>
                @endforeach
            </table>
        </div>
    @endif

    <h2>Resultados clínicos</h2>
    <p class="small muted">Pacientes con dos o más evaluaciones dentro del periodo (primera contra última).</p>
    <table class="cards">
        <tr>
            <td><div class="card-label">Evaluaciones</div><div class="card-value">{{ $clinical['evaluations'] }}</div></td>
            <td><div class="card-label">Pacientes con seguimiento</div><div class="card-value">{{ $clinical['patients'] }}</div></td>
            <td><div class="card-label">Cambio de peso promedio</div><div class="card-value">{{ $clinical['avg_weight_change'] === null ? '—' : $num($clinical['avg_weight_change'], 1).' kg' }}</div></td>
            <td><div class="card-label">Kilos perdidos en total</div><div class="card-value">{{ $num($clinical['total_kg_lost'], 1) }} kg</div></td>
            <td><div class="card-label">Bajó / mantuvo / subió</div><div class="card-value">{{ $clinical['lost'] }} / {{ $clinical['maintained'] }} / {{ $clinical['gained'] }}</div></td>
        </tr>
    </table>
    @if (! empty($clinical['rows']))
        <table class="data">
            <tr>
                <th>Paciente</th>
                <th class="num">Eval.</th>
                <th class="num">Peso inicial</th>
                <th class="num">Peso final</th>
                <th class="num">Δ Peso (kg)</th>
                <th class="num">Δ IMC</th>
                <th class="num">Δ Grasa (%)</th>
                <th class="num">Δ Cintura (cm)</th>
            </tr>
            @foreach ($clinical['rows'] as $r)
                <tr @class(['alt' => $loop->even])>
                    <td>{{ $r['name'] }}</td>
                    <td class="num">{{ $r['evaluations'] }}</td>
                    <td class="num">{{ $num($r['weight_start'], 1) }}</td>
                    <td class="num">{{ $num($r['weight_end'], 1) }}</td>
                    <td class="num">{{ $r['weight_change'] === null ? '—' : ($r['weight_change'] > 0 ? '+' : '').$num($r['weight_change'], 1) }}</td>
                    <td class="num">{{ $r['bmi_change'] === null ? '—' : ($r['bmi_change'] > 0 ? '+' : '').$num($r['bmi_change'], 1) }}</td>
                    <td class="num">{{ $r['fat_change'] === null ? '—' : ($r['fat_change'] > 0 ? '+' : '').$num($r['fat_change'], 1) }}</td>
                    <td class="num">{{ $r['waist_change'] === null ? '—' : ($r['waist_change'] > 0 ? '+' : '').$num($r['waist_change'], 1) }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <div class="keep">
        <h2>Planes alimenticios</h2>
        <table class="cards">
            <tr>
                <td><div class="card-label">Creados en el periodo</div><div class="card-value">{{ $report['diet_plans']['created'] }}</div></td>
                <td><div class="card-label">Pacientes con plan nuevo</div><div class="card-value">{{ $report['diet_plans']['patients'] }}</div></td>
                <td><div class="card-label">Planes activos hoy</div><div class="card-value">{{ $report['diet_plans']['active_now'] }}</div></td>
                <td><div class="card-label">Meta calórica promedio</div><div class="card-value">{{ $report['diet_plans']['avg_target_kcal'] === null ? '—' : $num($report['diet_plans']['avg_target_kcal']).' kcal' }}</div></td>
            </tr>
        </table>
    </div>
@endsection

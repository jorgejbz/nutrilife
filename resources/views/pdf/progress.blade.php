@extends('pdf.layout')

@php
    $fmt = fn ($v, $d = 1) => $v === null ? '—' : number_format((float) $v, $d, '.', ',');
    $delta = function ($a, $b, $d = 1) {
        if ($a === null || $b === null) {
            return '';
        }
        $diff = round($b - $a, $d);
        return ($diff > 0 ? '+' : '').number_format($diff, $d, '.', ',');
    };
    $first = $evaluations->first();
    $last = $evaluations->last();

    // Gráfica de peso como SVG (dompdf la dibuja como imagen).
    $points = $evaluations->filter(fn ($e) => $e->weight !== null)->values();
    $chart = null;
    if ($points->count() >= 2) {
        $w = 520; $h = 150; $pad = 28;
        $min = $points->min('weight'); $max = $points->max('weight');
        $span = max(0.1, $max - $min);
        $n = $points->count();
        $coords = $points->map(fn ($e, $i) => [
            round($pad + $i * ($w - 2 * $pad) / ($n - 1), 1),
            round($h - $pad - (($e->weight - $min) / $span) * ($h - 2 * $pad), 1),
            $e,
        ]);
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="'.$w.'" height="'.$h.'" viewBox="0 0 '.$w.' '.$h.'">';
        $svg .= '<rect width="'.$w.'" height="'.$h.'" fill="#F6FAF3"/>';
        $svg .= '<polyline fill="none" stroke="#459C4D" stroke-width="2.5" points="'.$coords->map(fn ($c) => $c[0].','.$c[1])->implode(' ').'"/>';
        foreach ($coords as $c) {
            $svg .= '<circle cx="'.$c[0].'" cy="'.$c[1].'" r="3.5" fill="#1B3625"/>';
            $svg .= '<text x="'.$c[0].'" y="'.($c[1] - 7).'" font-size="9" text-anchor="middle" fill="#1B3625">'.number_format($c[2]->weight, 1).'</text>';
            $svg .= '<text x="'.$c[0].'" y="'.($h - 8).'" font-size="8" text-anchor="middle" fill="#6F876F">'.$c[2]->evaluation_date->format('d/m/y').'</text>';
        }
        $svg .= '</svg>';
        $chart = 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
@endphp

@section('content')
    <h1>{{ $patient->first_name }} {{ $patient->last_name }}</h1>
    <p class="subtitle">Reporte de evolución y seguimiento nutricional</p>

    <table class="data">
        <tr>
            <th style="width: 25%;">Edad</th>
            <th style="width: 25%;">Sexo</th>
            <th style="width: 25%;">Teléfono</th>
            <th style="width: 25%;">Correo</th>
        </tr>
        <tr>
            <td>{{ $patient->birth_date ? $patient->birth_date->age.' años' : '—' }}</td>
            <td>{{ $patient->sex ?: '—' }}</td>
            <td>{{ $patient->phone ?: '—' }}</td>
            <td>{{ $patient->email ?: '—' }}</td>
        </tr>
    </table>

    @if ($patient->goal)
        <div class="box"><span class="card-label">Objetivo</span><br>{{ $patient->goal }}</div>
    @endif

    @if ($patient->allergens->isNotEmpty() || $patient->pathologies->isNotEmpty())
        <div class="box keep">
            @if ($patient->allergens->isNotEmpty())
                <span class="card-label">Alergias e intolerancias</span><br>
                @foreach ($patient->allergens as $allergen)
                    <span class="pill danger">{{ $allergen->name }}@if ($allergen->pivot?->severity) · {{ ucfirst($allergen->pivot->severity) }}@endif</span>
                @endforeach
                <br>
            @endif
            @if ($patient->pathologies->isNotEmpty())
                <span class="card-label">Patologías</span><br>
                @foreach ($patient->pathologies as $pathology)
                    <span class="pill warn">{{ $pathology->name }}</span>
                @endforeach
            @endif
        </div>
    @endif

    @if ($evaluations->isEmpty())
        <h2>Evaluaciones</h2>
        <p class="muted">Este paciente todavía no tiene evaluaciones registradas.</p>
    @else
        <h2>Resumen: primera contra última evaluación</h2>
        <p class="small muted">
            {{ $first->evaluation_date->format('d/m/Y') }} → {{ $last->evaluation_date->format('d/m/Y') }}
            · {{ $evaluations->count() }} {{ $evaluations->count() === 1 ? 'evaluación' : 'evaluaciones' }}
        </p>
        <table class="cards">
            <tr>
                @foreach ([
                    ['Peso', 'weight', 'kg'],
                    ['IMC', 'bmi', ''],
                    ['Grasa corporal', 'body_fat', '%'],
                    ['Masa muscular', 'muscle_mass', 'kg'],
                    ['Cintura', 'waist', 'cm'],
                ] as [$label, $key, $unit])
                    <td style="width: 20%;">
                        <div class="card-label">{{ $label }}</div>
                        <div class="card-value">{{ $fmt($last->{$key}) }} <span class="small muted">{{ $unit }}</span></div>
                        <div class="card-note">
                            Inicio: {{ $fmt($first->{$key}) }}
                            @if ($delta($first->{$key}, $last->{$key}) !== '') · {{ $delta($first->{$key}, $last->{$key}) }}@endif
                        </div>
                    </td>
                @endforeach
            </tr>
        </table>

        @if ($chart)
            <h2>Evolución del peso (kg)</h2>
            <img src="{{ $chart }}" style="width: 100%;" alt="Gráfica de peso">
        @endif

        <div class="keep">
        <h2>Historial de evaluaciones</h2>
        <table class="data">
            <thead>
            <tr>
                <th>Fecha</th>
                <th class="num">Peso (kg)</th>
                <th class="num">Estatura (cm)</th>
                <th class="num">IMC</th>
                <th class="num">Grasa (%)</th>
                <th class="num">Músculo (kg)</th>
                <th class="num">Cintura (cm)</th>
                <th class="num">Cadera (cm)</th>
            </tr>
            </thead>
            @foreach ($evaluations->reverse() as $i => $e)
                <tr @class(['alt' => $loop->even])>
                    <td>{{ $e->evaluation_date->format('d/m/Y') }}</td>
                    <td class="num">{{ $fmt($e->weight) }}</td>
                    <td class="num">{{ $fmt($e->height, 0) }}</td>
                    <td class="num">{{ $fmt($e->bmi) }}</td>
                    <td class="num">{{ $fmt($e->body_fat) }}</td>
                    <td class="num">{{ $fmt($e->muscle_mass) }}</td>
                    <td class="num">{{ $fmt($e->waist) }}</td>
                    <td class="num">{{ $fmt($e->hip) }}</td>
                </tr>
            @endforeach
        </table>
        </div>
    @endif

    @if ($consultations->isNotEmpty())
        <h2>Últimas consultas</h2>
        @foreach ($consultations as $c)
            <div class="box keep">
                <h3>{{ $c->consultation_at->format('d/m/Y') }} · {{ $c->reason ?: 'Consulta' }}</h3>
                @if ($c->observations)<p><span class="card-label">Observaciones</span><br>{{ $c->observations }}</p>@endif
                @if ($c->recommendations)<p><span class="card-label">Recomendaciones</span><br>{{ $c->recommendations }}</p>@endif
                @if ($c->next_visit)<p class="small muted">Próxima visita sugerida: {{ $c->next_visit->format('d/m/Y') }}</p>@endif
            </div>
        @endforeach
    @endif
@endsection

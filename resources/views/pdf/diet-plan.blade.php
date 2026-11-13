@extends('pdf.layout')

@php
    $patient = $plan->patient;
    $patientName = trim(($patient?->first_name ?? '').' '.($patient?->last_name ?? ''));
    $fmt = fn ($v, $d = 0) => number_format((float) $v, $d, '.', ',');
@endphp

@section('content')
    <h1>{{ $plan->name }}</h1>
    <p class="subtitle">
        Paciente: <strong>{{ $patientName ?: 'Sin paciente' }}</strong>
        · Vigencia: {{ $plan->starts_at?->format('d/m/Y') ?? '—' }} — {{ $plan->ends_at?->format('d/m/Y') ?? 'actualidad' }}
        · Estado: {{ $plan->statusLabel() }}
    </p>

    @if ($plan->goal)
        <div class="box"><span class="card-label">Objetivo</span><br>{{ $plan->goal }}</div>
    @endif

    {{-- Energía y macronutrientes: aporte del plan contra la meta --}}
    <table class="cards">
        <tr>
            @foreach ([
                ['Calorías', 'kcal', 'kcal', 0],
                ['Proteína', 'protein_g', 'g', 1],
                ['Carbohidratos', 'carbs_g', 'g', 1],
                ['Grasas', 'lipids_g', 'g', 1],
            ] as [$label, $key, $unit, $dec])
                <td style="width: 25%;">
                    <div class="card-label">{{ $label }}</div>
                    <div class="card-value">{{ $fmt($totals[$key], $dec) }} <span class="small muted">{{ $unit }}</span></div>
                    <div class="card-note">Meta: {{ $targets[$key] > 0 ? $fmt($targets[$key], $dec).' '.$unit : '—' }}@if ($targets[$key] > 0) · {{ $fmt($progress[$key], 0) }} %@endif</div>
                    @if ($targets[$key] > 0)
                        <div class="bar"><div style="width: {{ min(100, max(0, $progress[$key])) }}%;"></div></div>
                    @endif
                </td>
            @endforeach
        </tr>
    </table>

    @if ($patient && ($patient->allergens->isNotEmpty() || $patient->pathologies->isNotEmpty()))
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

    <h2>Distribución del día</h2>

    @forelse ($plan->meals as $meal)
        @php($mealTotals = $meal->totals())
        <div class="keep">
            <h3>
                {{ $meal->meal_name }}
                @if ($meal->timeLabel()) <span class="muted small">· {{ $meal->timeLabel() }} h</span>@endif
                <span class="muted small">· {{ $fmt($mealTotals['kcal']) }} kcal</span>
            </h3>
            <table class="data">
                <tr>
                    <th style="width: 34%;">Alimento</th>
                    <th style="width: 22%;">Porción</th>
                    <th class="num">Equiv.</th>
                    <th class="num">Gramos</th>
                    <th class="num">kcal</th>
                    <th class="num">P (g)</th>
                    <th class="num">C (g)</th>
                    <th class="num">G (g)</th>
                </tr>
                @foreach ($meal->entries() as $entry)
                    @if ($entry['dish_id'])
                        @php($dishTotals = \App\Models\DietPlanItem::sumNutrients($entry['items']->map->nutrients()))
                        <tr class="alt">
                            <td colspan="4"><strong>{{ $entry['items']->first()->dish?->name ?? 'Platillo' }}</strong></td>
                            <td class="num"><strong>{{ $fmt($dishTotals['kcal']) }}</strong></td>
                            <td class="num">{{ $fmt($dishTotals['protein_g'], 1) }}</td>
                            <td class="num">{{ $fmt($dishTotals['carbs_g'], 1) }}</td>
                            <td class="num">{{ $fmt($dishTotals['lipids_g'], 1) }}</td>
                        </tr>
                    @endif
                    @foreach ($entry['items'] as $item)
                        @php($n = $item->nutrients())
                        @php($alerts = $item->food_id ? ($foodAlerts[$item->food_id] ?? []) : [])
                        <tr>
                            <td>
                                @if ($entry['dish_id'])<span class="muted">— </span>@endif{{ $item->displayName() }}
                                @foreach ($alerts as $alert)
                                    <br><span class="pill danger">Alergia: {{ $alert }}</span>
                                @endforeach
                            </td>
                            <td>{{ $item->food?->portion_description ?? $item->custom_portion_description ?? '—' }}</td>
                            <td class="num">{{ $item->food && $item->equivalents !== null ? $fmt($item->equivalents, 1) : '—' }}</td>
                            <td class="num">{{ $fmt($item->grams, 0) }}</td>
                            <td class="num">{{ $fmt($n['kcal']) }}</td>
                            <td class="num">{{ $fmt($n['protein_g'], 1) }}</td>
                            <td class="num">{{ $fmt($n['carbs_g'], 1) }}</td>
                            <td class="num">{{ $fmt($n['lipids_g'], 1) }}</td>
                        </tr>
                    @endforeach
                @endforeach
                @if ($meal->items->isEmpty())
                    <tr><td colspan="8" class="muted">Sin alimentos en esta comida.</td></tr>
                @endif
            </table>
            @if ($meal->notes)
                <p class="small muted">Nota: {{ $meal->notes }}</p>
            @endif
        </div>
    @empty
        <p class="muted">Este plan todavía no tiene comidas.</p>
    @endforelse

    <table class="data keep">
        <tr class="total">
            <td style="width: 56%;">Total del día</td>
            <td class="num">{{ $fmt($totals['kcal']) }} kcal</td>
            <td class="num">P {{ $fmt($totals['protein_g'], 1) }} g</td>
            <td class="num">C {{ $fmt($totals['carbs_g'], 1) }} g</td>
            <td class="num">G {{ $fmt($totals['lipids_g'], 1) }} g</td>
        </tr>
    </table>

    @if ($plan->instructions)
        <h2>Indicaciones del nutriólogo</h2>
        <div class="box keep">{!! nl2br(e($plan->instructions)) !!}</div>
    @endif

    <p class="small muted" style="margin-top: 16px;">
        Equiv. = equivalentes del Sistema Mexicano de Alimentos Equivalentes. P = proteína, C = carbohidratos, G = grasas.
        Este plan es personal; no lo modifiques sin consultar a tu nutriólogo.
    </p>
@endsection

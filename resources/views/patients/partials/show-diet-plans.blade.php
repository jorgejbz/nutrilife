{{-- =========================================================
     PATIENTS SHOW — PLANES ALIMENTICIOS
     Solo se incluye con @can('diet-plans') (Pro, Consultorio y
     prueba gratuita). $dietPlans: planes del paciente que son del
     nutriólogo (PatientController@show).
     ========================================================= --}}
<div class="glass-card diet-patient-plans">
    <div class="card-heading">
        <div>
            <span>NUTRICIÓN</span>
            <h4>Planes alimenticios</h4>
        </div>

        @if(! $paciente->trashed() && $paciente->active)
            <a href="{{ route('diet-plans.create', ['paciente' => $paciente->id]) }}" class="diet-btn diet-btn--soft">
                <i class="fas fa-plus" aria-hidden="true"></i>
                Nuevo plan
            </a>
        @endif
    </div>

    @forelse($dietPlans as $dietPlan)
        @php $kcal = $dietPlan->totals()['kcal']; @endphp

        <a href="{{ route('diet-plans.show', $dietPlan) }}" class="diet-patient-plan">
            <div>
                <strong>{{ $dietPlan->name }}</strong>
                <small>
                    {{ $dietPlan->starts_at->format('d/m/Y') }}
                    — {{ $dietPlan->ends_at ? $dietPlan->ends_at->format('d/m/Y') : 'sin fecha de fin' }}
                    · {{ number_format($kcal, 0) }} de {{ number_format((float) $dietPlan->target_kcal, 0) }} kcal
                </small>
            </div>
            <span class="diet-status {{ $dietPlan->active ? 'is-active' : 'is-finished' }}">{{ $dietPlan->statusLabel() }}</span>
        </a>
    @empty
        <p class="clinical-show-empty">
            Sin planes alimenticios.
            @if(! $paciente->active)
                Activa al paciente para crearle uno.
            @endif
        </p>
    @endforelse
</div>

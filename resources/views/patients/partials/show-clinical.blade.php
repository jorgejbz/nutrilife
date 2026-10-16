{{-- =========================================================
     PATIENTS SHOW — HISTORIAL CLÍNICO
     patient_pathologies (fecha de diagnóstico, notas) y
     patient_allergens (severidad). Se edita en "Editar paciente".
     ========================================================= --}}
@php
    $severityLabels = ['leve' => 'Leve', 'moderada' => 'Moderada', 'severa' => 'Severa'];
@endphp

<div class="clinical-show-grid">

    {{-- PATOLOGÍAS --}}
    <div class="glass-card">
        <div class="card-heading">
            <div>
                <span>HISTORIAL CLÍNICO</span>
                <h4>Patologías</h4>
            </div>
            <div class="heading-icon"><i class="fas fa-notes-medical"></i></div>
        </div>

        @forelse ($paciente->pathologies as $pathology)
            <div class="clinical-show-item">
                <div>
                    <strong>{{ $pathology->name }}</strong>
                    @if ($pathology->pivot->notes)
                        <p>{{ $pathology->pivot->notes }}</p>
                    @endif
                </div>
                @if ($pathology->pivot->diagnosed_at)
                    <span class="clinical-show-date">
                        Desde {{ \Illuminate\Support\Carbon::parse($pathology->pivot->diagnosed_at)->format('d/m/Y') }}
                    </span>
                @endif
            </div>
        @empty
            <p class="clinical-show-empty">Sin patologías registradas.</p>
        @endforelse
    </div>

    {{-- ALERGIAS --}}
    <div class="glass-card">
        <div class="card-heading">
            <div>
                <span>HISTORIAL CLÍNICO</span>
                <h4>Alergias e intolerancias</h4>
            </div>
            <div class="heading-icon"><i class="fas fa-triangle-exclamation"></i></div>
        </div>

        @forelse ($paciente->allergens as $allergen)
            <div class="clinical-show-item">
                <strong>{{ $allergen->name }}</strong>
                @if ($allergen->pivot->severity)
                    <span class="severity-badge severity-badge--{{ $allergen->pivot->severity }}">
                        {{ $severityLabels[$allergen->pivot->severity] ?? $allergen->pivot->severity }}
                    </span>
                @else
                    <span class="severity-badge">Sin severidad</span>
                @endif
            </div>
        @empty
            <p class="clinical-show-empty">Sin alergias registradas.</p>
        @endforelse
    </div>

</div>

{{-- =========================================================
     ESTADO DEL PACIENTE (patients.active) — componente único
     Activo  → verde de acento + icono ✓
     Inactivo → gris neutro + icono pausa
     Texto + icono: no depende solo del color.
     Uso: @include('partials.patient-status', ['active' => $patient->active, 'variant' => 'pill|badge', 'long' => false])
     ========================================================= --}}
@php
    $long = $long ?? false;
    $label = $active ? ($long ? 'Paciente activo' : 'Activo') : ($long ? 'Paciente inactivo' : 'Inactivo');
@endphp
<span class="nl-patient-status {{ $active ? 'is-active' : 'is-inactive' }} {{ ($variant ?? 'pill') === 'badge' ? 'nl-patient-status--sm' : '' }}">
    <i class="fas {{ $active ? 'fa-check' : 'fa-pause' }}" aria-hidden="true"></i>
    {{ $label }}
</span>

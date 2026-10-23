{{-- Estado de una cita (appointments.status) con icono y texto: nunca solo color. --}}
@php
    $statusIcon = [
        'Pendiente' => 'far fa-clock',
        'Confirmada' => 'fas fa-check',
        'Completada' => 'fas fa-check-double',
        'Cancelada' => 'fas fa-ban',
    ][$status] ?? 'far fa-circle';
@endphp
<span class="consultation-status consultation-status--{{ \Illuminate\Support\Str::slug($status) }}">
    <i class="{{ $statusIcon }}" aria-hidden="true"></i>
    {{ $status }}
</span>

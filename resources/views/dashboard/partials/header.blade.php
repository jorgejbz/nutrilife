{{-- =========================================================
     DASHBOARD HEADER
     Encabezado principal del panel
     ========================================================= --}}

@php
    $userName = Auth::user()->name ?? 'Usuario';
    $currentDate = now()->translatedFormat('d \d\e F, Y');
@endphp

<div class="dashboard-header">

    <div class="dashboard-header__content">

        <div class="dashboard-header__welcome">
            <span class="dashboard-header__eyebrow">
                Panel de control
            </span>

            <h1 class="dashboard-header__title">
                ¡Hola, {{ $userName }}!
            </h1>

            <p class="dashboard-header__date">
                {{ $currentDate }}
            </p>
        </div>

        <div class="dashboard-header__status">
            <span class="dashboard-status-dot"></span>
            <span>Sistema operativo</span>
        </div>

    </div>

</div>
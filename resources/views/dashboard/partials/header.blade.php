{{-- =========================================================
     DASHBOARD HEADER
     Encabezado principal del panel
     ========================================================= --}}

@php
    $userName = Auth::user()->name ?? 'Usuario';
    $currentDate = ucfirst(now()->locale('es')->translatedFormat('l d \d\e F, Y'));
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

        {{-- Antes decía "Sistema operativo" sin revisar nada; ahora muestra
             la hora a la que se calcularon los datos. --}}
        <div class="dashboard-header__status">
            <i class="far fa-clock" aria-hidden="true"></i>
            <span>Datos al {{ now()->format('H:i') }}</span>
        </div>

    </div>

</div>
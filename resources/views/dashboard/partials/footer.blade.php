{{-- =========================================================
     DASHBOARD FOOTER
     Información y estado del sistema
     ========================================================= --}}

<footer class="dashboard-footer">

    <div class="dashboard-footer__brand">

        <div class="dashboard-footer__logo">
            <img
                src="{{ asset('images/brand/nutrilife-emblem-64.png') }}"
                alt="NutriLife"
                width="64"
                height="64"
            >
        </div>

        <div>
            <strong>NutriLife</strong>
            <span>Tu clínica de nutrición digital</span>
        </div>

    </div>

    {{-- Antes decía "Sistema funcionando correctamente" sin comprobar
         nada; se retiró para no mostrar un estado que no se verifica. --}}


    <div class="dashboard-footer__version">
        NutriLife
    </div>

</footer>
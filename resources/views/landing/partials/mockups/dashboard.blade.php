{{-- =========================================================
     MAQUETA: DASHBOARD (sustituye a la captura anterior, ahora en resources/brand/legado/landing/: dashboard.png,
     que mostraba la marca y paleta anteriores).
     Datos ilustrativos. Estilos: public/css/app.css → "MAQUETAS".
     ========================================================= --}}
<div class="nlm nlm-dashboard" role="img" aria-label="Vista ilustrativa del panel de NutriLife: pacientes, citas, ingresos y crecimiento">
    <aside class="nlm-side">
        <img src="{{ asset('images/brand/nutrilife-emblem-64.png') }}" alt="" class="nlm-side-logo" width="64" height="64">
        <span class="nlm-side-item is-active"></span>
        <span class="nlm-side-item"></span>
        <span class="nlm-side-item"></span>
        <span class="nlm-side-item"></span>
        <span class="nlm-side-item"></span>
    </aside>

    <div class="nlm-main">
        <div class="nlm-top">
            <div>
                <small>PANEL DE CONTROL</small>
                <strong>Buenos días, Dra. Andrea</strong>
            </div>
            <span class="nlm-pill">Hoy · 6 citas</span>
        </div>

        <div class="nlm-stats">
            <div class="nlm-stat"><small>Pacientes activos</small><strong>48</strong><span class="nlm-bar" style="--w:82%"></span></div>
            <div class="nlm-stat"><small>Citas de hoy</small><strong>6</strong><span class="nlm-bar" style="--w:55%"></span></div>
            <div class="nlm-stat"><small>Consultas del mes</small><strong>32</strong><span class="nlm-bar" style="--w:68%"></span></div>
            <div class="nlm-stat"><small>Ingresos del mes</small><strong>$18,400</strong><span class="nlm-bar is-gold" style="--w:74%"></span></div>
        </div>

        <div class="nlm-row">
            <div class="nlm-card nlm-chart">
                <small>Crecimiento de pacientes</small>
                <svg viewBox="0 0 300 110" preserveAspectRatio="none" aria-hidden="true">
                    <defs>
                        <linearGradient id="nlmArea" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0" stop-color="rgb(var(--nl-accent-rgb))" stop-opacity=".35"/>
                            <stop offset="1" stop-color="rgb(var(--nl-accent-rgb))" stop-opacity="0"/>
                        </linearGradient>
                    </defs>
                    <g class="nlm-grid"><line x1="0" y1="27" x2="300" y2="27"/><line x1="0" y1="55" x2="300" y2="55"/><line x1="0" y1="83" x2="300" y2="83"/></g>
                    <path d="M0 96 C30 92 45 84 75 80 S120 70 150 58 S205 48 225 36 S275 20 300 14 L300 110 L0 110Z" fill="url(#nlmArea)"/>
                    <path d="M0 96 C30 92 45 84 75 80 S120 70 150 58 S205 48 225 36 S275 20 300 14" class="nlm-line"/>
                </svg>
            </div>

            <div class="nlm-card nlm-list">
                <small>Próximas citas</small>
                <div class="nlm-item"><span class="nlm-avatar">ML</span><div><b>María López</b><i>Seguimiento</i></div><em>10:30</em></div>
                <div class="nlm-item"><span class="nlm-avatar">JR</span><div><b>Jorge Ruiz</b><i>Primera consulta</i></div><em>12:00</em></div>
                <div class="nlm-item"><span class="nlm-avatar">SA</span><div><b>Sofía Aguilar</b><i>Evaluación</i></div><em>16:15</em></div>
            </div>
        </div>
    </div>
</div>

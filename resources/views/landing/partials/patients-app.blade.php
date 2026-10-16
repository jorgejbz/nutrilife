{{-- =========================================================
     APP PARA PACIENTES
     Basado en la BD definitiva: users.access_code (6 dígitos, único)
     + patients.email (único por nutriólogo) son la credencial del
     paciente para la app móvil. La app aún no existe: se marca
     como "Próximamente".
     ========================================================= --}}
<section class="patients-app-section" id="pacientes">
    <div class="landing-container patients-app-grid">

        <div class="patients-app-content">
            <span class="section-eyebrow">
                <i class="fas fa-mobile-screen-button" aria-hidden="true"></i>
                TUS PACIENTES, CONECTADOS
            </span>

            <h2>
                Tu consulta también en
                <span>el celular de tus pacientes.</span>
            </h2>

            <p>
                Al crear tu cuenta recibes un código de acceso de 6 dígitos.
                Tus pacientes entrarán a la app móvil de NutriLife con su correo
                y ese código para consultar su progreso y su plan de alimentación.
            </p>

            <span class="soon-badge">
                <i class="fas fa-seedling" aria-hidden="true"></i>
                App móvil para pacientes · Próximamente
            </span>

            <ol class="patients-app-steps">
                <li>
                    <span>1</span>
                    <div>
                        <strong>Registra a tu paciente</strong>
                        <p>Con su nombre y su correo electrónico.</p>
                    </div>
                </li>
                <li>
                    <span>2</span>
                    <div>
                        <strong>Compártele tu código</strong>
                        <p>Lo encuentras en tu panel, en <em>Mi perfil</em>.</p>
                    </div>
                </li>
                <li>
                    <span>3</span>
                    <div>
                        <strong>Da seguimiento juntos</strong>
                        <p>Tu paciente verá su evolución desde su celular.</p>
                    </div>
                </li>
            </ol>
        </div>

        {{-- Maqueta ilustrativa del acceso en la app --}}
        <div class="patients-app-visual" aria-hidden="true">
            <div class="phone-mock">
                <div class="phone-notch"></div>
                <img src="{{ asset('images/brand/nutrilife-emblem-192.png') }}" alt="" class="phone-logo" width="192" height="192">
                <strong class="phone-title">Bienvenida</strong>
                <span class="phone-sub">Entra con tu correo y el código de tu nutriólogo</span>
                <div class="phone-field">maria@correo.com</div>
                <div class="phone-code">
                    <span>4</span><span>8</span><span>2</span><span>9</span><span>1</span><span>5</span>
                </div>
                <div class="phone-button">Entrar</div>
                <div class="phone-progress">
                    <small>Tu progreso</small>
                    <svg viewBox="0 0 120 34" preserveAspectRatio="none">
                        <polyline points="2,6 26,10 50,16 74,20 98,26 118,30" class="nlm-line"/>
                    </svg>
                </div>
            </div>
        </div>

    </div>
</section>

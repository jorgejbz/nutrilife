{{-- =========================================================
     CONFIANZA (debajo del hero)
     Solo afirmaciones que el sistema cumple hoy:
       - contraseñas cifradas (Hash::make)
       - cada nutriólogo ve únicamente sus pacientes (user_id + validaciones)
       - el historial se conserva (borrado lógico en patients, FK sin cascada)
       - funciona en el navegador, en computadora, tablet y celular
     Sin testimonios ni cifras inventadas.
     ========================================================= --}}
<section class="trust-section" aria-label="Por qué confiar en NutriLife">
    <div class="landing-container">
        <ul class="trust-list">
            <li>
                <span class="trust-icon"><i class="fas fa-lock" aria-hidden="true"></i></span>
                <div>
                    <strong>Datos protegidos</strong>
                    <span>Contraseñas cifradas y acceso solo con tu cuenta.</span>
                </div>
            </li>
            <li>
                <span class="trust-icon"><i class="fas fa-user-shield" aria-hidden="true"></i></span>
                <div>
                    <strong>Expedientes privados</strong>
                    <span>Cada nutriólogo ve únicamente a sus pacientes.</span>
                </div>
            </li>
            <li>
                <span class="trust-icon"><i class="fas fa-clock-rotate-left" aria-hidden="true"></i></span>
                <div>
                    <strong>Historial completo</strong>
                    <span>Citas, consultas, pagos y evaluaciones se conservan.</span>
                </div>
            </li>
            <li>
                <span class="trust-icon"><i class="fas fa-laptop-medical" aria-hidden="true"></i></span>
                <div>
                    <strong>Sin instalar nada</strong>
                    <span>Desde el navegador, en computadora, tablet o celular.</span>
                </div>
            </li>
        </ul>
    </div>
</section>

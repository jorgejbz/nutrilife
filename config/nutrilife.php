<?php

/*
|--------------------------------------------------------------------------
| Datos públicos de NutriLife (sitio público / landing)
|--------------------------------------------------------------------------
| No se guardan en la BD (la BD definitiva no tiene tabla de ajustes).
| Si un valor queda vacío, la landing simplemente no lo muestra: no se
| inventan datos de contacto.
*/

return [

    // Correo de contacto para "¿No encontraste tu respuesta?" y el pie de página.
    'contact_email' => env('NUTRILIFE_CONTACT_EMAIL'),

    /*
    |--------------------------------------------------------------------------
    | Qué incluye cada plan (se muestra en "Mi perfil")
    |--------------------------------------------------------------------------
    | La BD solo guarda precio, periodo y max_patients (subscription_plans).
    | Esta lista describe las funciones de cada plan, igual que la landing y
    | docs/GLOSARIO_PLANES.md. La clave es subscription_plans.name.
    | 'available' = false → la función todavía no existe en el sistema y se
    | muestra como "Próximamente".
    */
    /*
    |--------------------------------------------------------------------------
    | Prueba gratuita
    |--------------------------------------------------------------------------
    | Duración en días. La prueba empieza cuando la cuenta queda activa
    | (subscriptions.created_at guarda la fecha y hora reales) y vence
    | exactamente N días después. Al vencer, el panel se bloquea y se muestra
    | la pantalla de contratación (middleware EnsureTrialSubscription).
    */
    'trial_days' => (int) env('NUTRILIFE_TRIAL_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Activación de cuentas nuevas (simulación)
    |--------------------------------------------------------------------------
    | Al registrarse, la cuenta queda "pendiente" y se habilita sola cuando
    | pasan estos minutos (users.activates_at). Se revisa en el servidor al
    | iniciar sesión o al entrar a cualquier ruta privada: no depende de que
    | la página siga abierta. 0 = se habilita de inmediato.
    |
    | Ojo: esto NO verifica la cédula ni el título. La verificación
    | profesional real se guarda aparte (users.professional_verified_at).
    */
    // Cambia NUTRILIFE_ACTIVATION_DELAY_MINUTES en .env (1 = un minuto,
    // 5 = cinco, 10 = diez…) y, si usas `php artisan config:cache`, vuelve a
    // ejecutarlo. Lo lee SOLO App\Services\AccountActivation::delayMinutes().
    // El tiempo se calcula al registrarse (users.activates_at): cambiar el
    // valor afecta a los registros NUEVOS, no a cuentas que ya esperan.
    'account_activation' => [
        'delay_minutes' => (int) env('NUTRILIFE_ACTIVATION_DELAY_MINUTES', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Verificación de correo simulada (solo entorno de pruebas)
    |--------------------------------------------------------------------------
    | NutriLife todavía no envía correos de verificación reales
    | (MAIL_MAILER=log). Con true, "Mi perfil" muestra el correo como
    | "Verificado (simulado)" aunque users.email_verified_at esté vacío.
    | Es SOLO visual: no llena email_verified_at y nunca aplica en
    | APP_ENV=production (ver User::emailVerificationState()).
    */
    'simulated_email_verification' => (bool) env('NUTRILIFE_SIMULATED_EMAIL_VERIFICATION', false),

    /*
    |--------------------------------------------------------------------------
    | Consultorios (clínica con varios nutriólogos)
    |--------------------------------------------------------------------------
    | 'plan'        → subscription_plans.name que convierte una cuenta en
    |                 clínica (puede crear subperfiles de nutriólogo).
    | 'member_plan' → plan cuyas funciones reciben los subperfiles. Se reutiliza
    |                 la misma regla de 'features' (no hay otra implementación).
    |                 Los subperfiles NO tienen suscripción ni cobro propio:
    |                 usan la licencia de su clínica mientras esté vigente.
    | 'max_members' → límite de subperfiles por clínica (null = sin límite).
    |                 El negocio aún no define uno; se cambia aquí o con
    |                 NUTRILIFE_CLINIC_MAX_MEMBERS en .env.
    */
    'clinic' => [
        'plan' => 'Consultorio',
        'member_plan' => 'Pro',
        'max_members' => env('NUTRILIFE_CLINIC_MAX_MEMBERS') !== null && env('NUTRILIFE_CLINIC_MAX_MEMBERS') !== ''
            ? max((int) env('NUTRILIFE_CLINIC_MAX_MEMBERS'), 0)
            : null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Contratación de paquetes
    |--------------------------------------------------------------------------
    | No hay pasarela de pago. Con 'simulated_payments' = true, elegir un
    | paquete lo activa por un periodo y registra el cobro en
    | subscription_payments con payment_method = 'Simulado'.
    | Para conectar una pasarela real, ver App\Services\PlanCheckout.
    */
    'checkout' => [
        'simulated_payments' => (bool) env('NUTRILIFE_SIMULATED_PAYMENTS', true),
        'payment_method' => 'Simulado',
    ],

    /*
    |--------------------------------------------------------------------------
    | Funciones que dependen del plan contratado
    |--------------------------------------------------------------------------
    | Fuente única de "quién puede usar qué". La BD no guarda funciones por
    | plan (subscription_plans solo tiene precio, periodo y max_patients), así
    | que aquí se define qué planes (subscription_plans.name) desbloquean cada
    | función. Lo leen User::hasFeature(), el Gate de AppServiceProvider, el
    | middleware EnsureFeature y la DietPlanPolicy.
    |
    | 'plans' → planes que la incluyen con suscripción pagada y vigente
    |           (status activa, o cancelada hasta que termine el periodo).
    | 'trial' → si la prueba gratuita de 7 días también la incluye.
    */
    'features' => [
        'diet_plans' => [
            'label' => 'Planes alimenticios',
            'plans' => ['Pro', 'Consultorio'],
            'trial' => true,
        ],
    ],

    'plan_features' => [
        'Esencial' => [
            ['label' => 'Gestión de pacientes', 'available' => true],
            ['label' => 'Agenda de citas', 'available' => true],
            ['label' => 'Consultas y evaluaciones', 'available' => true],
            ['label' => 'Seguimiento de progreso', 'available' => true],
            ['label' => 'Control de pagos', 'available' => true],
        ],
        'Pro' => [
            ['label' => 'Todo lo incluido en Esencial', 'available' => true],
            ['label' => 'Planes alimenticios', 'available' => true],
            ['label' => 'Cálculo de calorías y macros', 'available' => true],
            ['label' => 'Exportación a PDF', 'available' => false],
            ['label' => 'Reportes avanzados', 'available' => false],
        ],
        'Consultorio' => [
            ['label' => 'Todo lo incluido en Pro', 'available' => true],
            ['label' => 'Pacientes sin límite', 'available' => true],
            ['label' => 'Varios profesionales (subperfiles con funciones Pro)', 'available' => true],
            ['label' => 'Gestión centralizada de pacientes y agenda', 'available' => true],
            ['label' => 'Soporte prioritario', 'available' => true],
        ],
    ],

];

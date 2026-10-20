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
    'account_activation' => [
        'delay_minutes' => (int) env('NUTRILIFE_ACTIVATION_DELAY_MINUTES', 5),
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
            ['label' => 'Varios profesionales', 'available' => false],
            ['label' => 'Gestión centralizada', 'available' => false],
            ['label' => 'Soporte prioritario', 'available' => true],
        ],
    ],

];

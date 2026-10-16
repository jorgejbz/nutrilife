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
            ['label' => 'Planes alimenticios', 'available' => false],
            ['label' => 'Cálculo de calorías y macros', 'available' => false],
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

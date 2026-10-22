<?php

/*
|--------------------------------------------------------------------------
| Hojas de estilo de NutriLife (public/css)
|--------------------------------------------------------------------------
| Orden de carga de cada sección. El ORDEN IMPORTA: los archivos son partes
| contiguas de las hojas originales y la cascada depende de él.
|
|   1. base/tokens.css          paleta de marca (todas las secciones)
|   2. módulos base             estructura, componentes y páginas
|   3. themes/*-light|dark.css  valores del modo claro / oscuro; theme.js
|                               deshabilita el que no está activo
|   4. responsive/<sección>/    TODAS las @media, de mayor a menor ancho:
|                               desktop → laptop → tablet → mobile → preferences
|
| Lo usan partials/theme-styles.blade.php (secciones) y
| diet-plans/partials/styles.blade.php (módulo de planes alimenticios).
| Para agregar una hoja nueva: créala en public/css/… y añádela aquí en el
| lugar que le corresponda.
*/

return [

    'sections' => [

        'admin' => [
            'base' => [
                'base/tokens.css',
                'admin/layout.css',
                'admin/pages/dashboard.css',
                'admin/pages/patients.css',
                'admin/pages/appointments.css',
                'admin/pages/consultations.css',
                'admin/pages/payments.css',
                'admin/pages/evaluations.css',
                'admin/pages/progress.css',
                'admin/pages/account.css',
                'admin/pages/patients-clinical.css',
                'admin/pages/diet-plans.css',
                'admin/pages/subscription.css',
                'admin/components/fields-status.css',
                'admin/pages/dashboard-data.css',
                'admin/pages/diet-plans-extras.css',
                'admin/components/user-menu.css',
                'admin/pages/food-catalog.css',
                'admin/components/global.css',
                'admin/components/confirm-dialog.css',
                'admin/components/date-picker.css',
                'admin/pages/clinic-admin.css',
                'admin/components/interface.css',
                'admin/components/payment-reference.css',
                'admin/components/utilities.css',
            ],
            'themes' => [
                'light' => 'themes/admin-light.css',
                'dark' => 'themes/admin-dark.css',
            ],
            'responsive' => [
                'responsive/admin/laptop.css',
                'responsive/admin/tablet.css',
                'responsive/admin/mobile.css',
                'responsive/admin/preferences.css',
            ],
        ],

        'app' => [
            'base' => [
                'base/tokens.css',
                'app/layout.css',
                'app/hero.css',
                'app/sections.css',
                'app/hero-organic.css',
                'app/mockups.css',
                'app/sections-extra.css',
            ],
            'themes' => [
                'light' => 'themes/app-light.css',
                'dark' => 'themes/app-dark.css',
            ],
            'responsive' => [
                'responsive/app/laptop.css',
                'responsive/app/tablet.css',
                'responsive/app/mobile.css',
            ],
        ],

        'login' => [
            'base' => [
                'base/tokens.css',
                'login/base.css',
                'login/login.css',
                'login/register.css',
                'login/guest.css',
            ],
            'themes' => [
                'light' => 'themes/login-light.css',
                'dark' => 'themes/login-dark.css',
            ],
            'responsive' => [
                'responsive/login/laptop.css',
                'responsive/login/tablet.css',
                'responsive/login/mobile.css',
            ],
        ],

    ],

    // Planes alimenticios y catálogo de platillos: se cargan DESPUÉS de las
    // hojas del panel (sección admin).
    'modules' => [
        'diet-plans' => [
            'diet-plans/common.css',
            'diet-plans/index.css',
            'diet-plans/form.css',
            'diet-plans/builder.css',
            'diet-plans/drawers.css',
            'diet-plans/dishes.css',
            'responsive/diet-plans/laptop.css',
            'responsive/diet-plans/tablet.css',
            'responsive/diet-plans/mobile.css',
            'responsive/diet-plans/preferences.css',
        ],
    ],

];

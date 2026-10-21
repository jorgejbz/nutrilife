import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                // Base
                // (Tailwind: solo lo usa el layout de Breeze layouts/app.blade.php)
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/theme.js',
                'resources/js/forms.js',
                'resources/js/date-picker.js',

                // Dashboard
                // Los estilos ya no pasan por Vite: viven en public/css
                // (admin.css + admin-light.css / admin-dark.css).
                'resources/js/dashboard.js',

                // Pacientes
                'resources/js/pages/patients-index.js',
                'resources/js/pages/patients-create.js',
                'resources/js/pages/patients-edit.js',
                'resources/js/pages/patients-clinical.js',
                'resources/js/pages/patients-show.js',

                // Citas
                'resources/js/pages/appointments-index.js',
                'resources/js/pages/appointments-create.js',

                // Consultas
                'resources/js/pages/consultations-create.js',
                'resources/js/pages/payments-create.js',

                // Planes alimenticios (Pro, Consultorio y prueba)
                'resources/js/pages/diet-plans.js',
                'resources/js/pages/foods-index.js',
                'resources/js/pages/dishes-index.js',

                // Evaluaciones
                'resources/js/pages/evaluations-create.js',

                // Progreso
                'resources/js/pages/progress-show.js',

                // Marketing / Auth
                'resources/js/pages/marketing.js',
                'resources/js/pages/auth-login.js',
                'resources/js/pages/auth-register.js',
                'resources/js/pages/profile-edit.js',
            ],
            refresh: true,
        }),
    ],
});

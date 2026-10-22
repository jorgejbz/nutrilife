<?php

namespace Database\Seeders;

/**
 * Pacientes ficticios para DemoDataSeeder (datos de prueba, NO clínicos).
 *
 * 'start'      → días atrás de la primera consulta (se ajusta si la cuenta
 *                 del nutriólogo es más reciente).
 * 'max_visits' → consultas pasadas como máximo (una cada 14 días).
 * 'cancel'     → índices de visitas que quedaron canceladas.
 * Peso, grasa, músculo y cintura cambian con 'step' en cada consulta.
 *
 * Los pacientes de esencial@ usan los mismos correos que
 * database/sql/demo_datos_nutriologo_esencial.sql: si ya cargaste ese SQL,
 * el seeder los reconoce y no los duplica.
 */
class DemoPatients
{
    /** [comida, hora, indicación, [[alimento, gramos, escala con kcal]]] (base 1,600 kcal) */
    public const MEAL_TEMPLATE = [
        ['Desayuno', '08:00', 'Agua natural al despertar.', [
            ['Avena en hojuelas (cruda)', 40, true], ['Leche descremada', 240, false], ['Fresas', 170, false], ['Almendras', 12, true],
        ]],
        ['Colación matutina', '11:00', null, [
            ['Manzana', 106, false], ['Queso panela', 40, true],
        ]],
        ['Comida', '14:30', 'Verduras a libre demanda; sin bebidas azucaradas.', [
            ['Pechuga de pollo sin piel (cocida)', 120, true], ['Arroz blanco (cocido)', 100, true], ['Brócoli (cocido)', 92, false],
            ['Tortilla de maíz', 30, true], ['Aguacate Hass', 30, true],
        ]],
        ['Colación vespertina', '17:30', null, [
            ['Yogur natural descremado', 230, false], ['Papaya', 140, false],
        ]],
        ['Cena', '20:00', 'Cenar al menos 2 horas antes de dormir.', [
            ['Huevo entero (crudo)', 50, false], ['Claras de huevo (crudas)', 66, true], ['Tortilla de maíz', 30, true],
            ['Nopales (cocidos)', 150, false], ['Jitomate', 120, false],
        ]],
    ];

    /**
     * Gramos de proteína, hidratos y lípidos para unas kcal y un reparto
     * en % (4 kcal/g proteína e hidratos, 9 kcal/g lípidos).
     *
     * @return array{0: float, 1: float, 2: float}
     */
    public static function macros(int $kcal, array $split): array
    {
        [$protein, $carbs, $lipids] = $split;

        return [
            round($kcal * $protein / 100 / 4, 1),
            round($kcal * $carbs / 100 / 4, 1),
            round($kcal * $lipids / 100 / 9, 1),
        ];
    }

    private static function patient(array $data): array
    {
        return $data + [
            'pathologies' => [],
            'allergens' => [],
            'active' => true,
            'phone' => null,
            'occupation' => null,
            'weight_step' => -0.6,
            'fat_step' => -0.4,
            'muscle' => 25.0,
            'muscle_step' => 0.1,
            'waist_step' => -0.8,
            'hip_ratio' => 1.12,
            'max_visits' => 8,
            'cancel' => [],
            'future' => true,
            'price' => [650, 450],
            'last_payment_pending' => false,
            'recommendations' => [
                'Mantener horarios fijos de comida e hidratación de 2 L de agua al día.',
                'Continuar con el plan y registrar cómo se siente después de cada comida.',
            ],
            'plan' => null,
        ];
    }

    private static function plan(string $name, int $kcal, array $split, string $goal, string $instructions): array
    {
        return compact('name', 'kcal', 'split', 'goal', 'instructions');
    }

    /* ==================================================================
     | esencial@nutrilife.test (sin planes alimenticios: plan Esencial)
     * ================================================================== */
    public static function esencial(): array
    {
        return array_map([self::class, 'patient'], [
            ['first' => 'Mariana', 'last' => 'López Castillo', 'sex' => 'Femenino', 'birth' => '1990-04-12', 'phone' => '4421583920', 'email' => 'mariana.lopez@pacientes.test', 'occupation' => 'Contadora', 'goal' => 'Bajar de peso de forma sostenible y mejorar la resistencia a la insulina.',
                'pathologies' => [['Prediabetes / resistencia a la insulina', '2026-05-20', 'Glucosa en ayuno de 112 mg/dL.'], ['Obesidad', null, null]], 'allergens' => [['Lactosa (intolerancia)', 'leve']],
                'height' => 162, 'weight' => 82.4, 'fat' => 38.2, 'muscle' => 27.1, 'waist' => 96, 'start' => 48],
            ['first' => 'Jorge', 'last' => 'Ramírez Soto', 'sex' => 'Masculino', 'birth' => '1978-11-03', 'phone' => '4423017745', 'email' => 'jorge.ramirez@pacientes.test', 'occupation' => 'Ingeniero civil', 'goal' => 'Controlar la presión arterial y el colesterol con la alimentación.',
                'pathologies' => [['Hipertensión arterial', '2023-03-10', 'Toma losartán.'], ['Dislipidemia', '2025-11-02', 'LDL de 168 mg/dL.']],
                'height' => 176, 'weight' => 96.8, 'fat' => 29.5, 'muscle' => 38.4, 'waist' => 104, 'hip_ratio' => 1.04, 'start' => 47, 'price' => [650, 450]],
            ['first' => 'Daniela', 'last' => 'Fuentes Aguilar', 'sex' => 'Femenino', 'birth' => '1996-07-25', 'phone' => '4425591032', 'email' => 'daniela.fuentes@pacientes.test', 'occupation' => 'Diseñadora gráfica', 'goal' => 'Regular ciclos y mejorar la composición corporal.',
                'pathologies' => [['Síndrome de ovario poliquístico (SOP)', '2024-08-15', 'En tratamiento con ginecología.']],
                'height' => 165, 'weight' => 71.5, 'fat' => 33.0, 'muscle' => 25.6, 'waist' => 84, 'start' => 45, 'cancel' => [2]],
            ['first' => 'Luis Fernando', 'last' => 'Hernández Vega', 'sex' => 'Masculino', 'birth' => '2001-02-14', 'phone' => '4427764410', 'email' => 'luisf.hernandez@pacientes.test', 'occupation' => 'Estudiante de Educación Física', 'goal' => 'Aumentar masa muscular sin ganar grasa.',
                'allergens' => [['Cacahuate', 'severa']],
                'height' => 178, 'weight' => 68.2, 'weight_step' => 0.4, 'fat' => 14.0, 'fat_step' => 0.0, 'muscle' => 31.5, 'muscle_step' => 0.5, 'waist' => 76, 'waist_step' => 0.2, 'hip_ratio' => 1.2, 'start' => 42, 'price' => [450, 350], 'last_payment_pending' => true],
            ['first' => 'Patricia', 'last' => 'Morales Rivas', 'sex' => 'Femenino', 'birth' => '1965-09-30', 'phone' => '4421129087', 'email' => 'patricia.morales@pacientes.test', 'occupation' => 'Docente jubilada', 'goal' => 'Mejorar el control de la glucosa y bajar de peso.',
                'pathologies' => [['Diabetes mellitus tipo 2', '2018-06-01', 'Metformina 850 mg.'], ['Hipertensión arterial', '2020-01-15', null]], 'allergens' => [['Mariscos (crustáceos y moluscos)', 'moderada']],
                'height' => 158, 'weight' => 78.3, 'fat' => 41.0, 'muscle' => 22.0, 'waist' => 101, 'start' => 41],
            ['first' => 'Ricardo', 'last' => 'Peña Domínguez', 'sex' => 'Masculino', 'birth' => '1985-12-08', 'phone' => '4426638851', 'email' => 'ricardo.pena@pacientes.test', 'occupation' => 'Chef', 'goal' => 'Reducir grasa abdominal y mejorar estudios de hígado y ácido úrico.',
                'pathologies' => [['Hígado graso (esteatosis hepática)', '2026-07-10', null], ['Hiperuricemia / gota', '2025-02-20', null]],
                'height' => 180, 'weight' => 102.5, 'fat' => 31.5, 'muscle' => 40.2, 'waist' => 110, 'hip_ratio' => 1.0, 'start' => 40],
            ['first' => 'Sofía', 'last' => 'Navarro Ruiz', 'sex' => 'Femenino', 'birth' => '1999-05-17', 'phone' => '4422240968', 'email' => 'sofia.navarro@pacientes.test', 'occupation' => 'Abogada', 'goal' => 'Subir de peso de forma saludable con dieta sin gluten.',
                'pathologies' => [['Enfermedad celíaca', '2021-09-01', 'Diagnóstico por biopsia.']], 'allergens' => [['Gluten (trigo, cebada, centeno)', 'severa']],
                'height' => 160, 'weight' => 54.0, 'weight_step' => 0.4, 'fat' => 24.0, 'fat_step' => 0.1, 'muscle' => 21.0, 'muscle_step' => 0.2, 'waist' => 68, 'waist_step' => 0.2, 'hip_ratio' => 1.35, 'start' => 38],
            ['first' => 'Alejandro', 'last' => 'Torres Mejía', 'sex' => 'Masculino', 'birth' => '1992-08-21', 'phone' => '4428817350', 'email' => 'alejandro.torres@pacientes.test', 'occupation' => 'Desarrollador de software', 'goal' => 'Disminuir molestias digestivas y bajar de peso.',
                'pathologies' => [['Gastritis', '2025-10-05', null], ['Síndrome de intestino irritable', '2026-03-12', null]],
                'height' => 174, 'weight' => 84.0, 'fat' => 26.0, 'muscle' => 34.5, 'waist' => 95, 'hip_ratio' => 1.06, 'start' => 35],
            ['first' => 'Gabriela', 'last' => 'Ortiz Salas', 'sex' => 'Femenino', 'birth' => '1988-03-02', 'phone' => '4424456712', 'email' => 'gabriela.ortiz@pacientes.test', 'occupation' => 'Enfermera', 'goal' => 'Mejorar energía y niveles de hierro.',
                'pathologies' => [['Hipotiroidismo', '2019-04-11', null], ['Anemia', '2026-08-01', null]], 'allergens' => [['Huevo', 'leve']],
                'height' => 163, 'weight' => 69.8, 'fat' => 34.0, 'muscle' => 23.8, 'waist' => 86, 'start' => 32],
            ['first' => 'Fernando', 'last' => 'Castro Lara', 'sex' => 'Masculino', 'birth' => '1972-06-19', 'phone' => '4429903318', 'email' => 'fernando.castro@pacientes.test', 'occupation' => 'Comerciante', 'goal' => 'Bajar de peso y controlar la glucosa.', 'active' => false,
                'pathologies' => [['Diabetes mellitus tipo 2', '2022-10-01', null], ['Obesidad', null, null]],
                'height' => 172, 'weight' => 110.3, 'fat' => 36.5, 'muscle' => 39.0, 'waist' => 118, 'hip_ratio' => 0.98, 'start' => 40, 'max_visits' => 2, 'cancel' => [1]],
            ['first' => 'Valeria', 'last' => 'Sánchez Ibarra', 'sex' => 'Femenino', 'birth' => '2003-10-11', 'phone' => '4421678804', 'email' => 'valeria.sanchez@pacientes.test', 'occupation' => 'Estudiante universitaria', 'goal' => 'Aprender a comer mejor y tener más energía.',
                'allergens' => [['Nueces y frutos de cáscara', 'leve']],
                'height' => 166, 'weight' => 60.0, 'fat' => 27.0, 'waist' => 72, 'start' => -2, 'max_visits' => 0],
            ['first' => 'Héctor', 'last' => 'Jiménez Cruz', 'sex' => 'Masculino', 'birth' => '1958-01-27', 'phone' => '4425120476', 'email' => 'hector.jimenez@pacientes.test', 'occupation' => 'Contador público', 'goal' => 'Cuidar la función renal y la presión arterial.',
                'pathologies' => [['Enfermedad renal crónica', '2024-11-20', 'Etapa 3a.'], ['Hipertensión arterial', '2015-05-08', null]],
                'height' => 168, 'weight' => 74.6, 'weight_step' => -0.3, 'fat' => 27.0, 'muscle' => 29.5, 'waist' => 96, 'hip_ratio' => 1.04, 'start' => 26],
        ]);
    }

    /* ==================================================================
     | pro@nutrilife.test
     * ================================================================== */
    public static function pro(): array
    {
        $deficit = self::plan('Déficit moderado 1,600 kcal', 1600, [25, 45, 30], 'Pérdida de grasa gradual (0.5 kg por semana).', "Pesar los alimentos la primera semana.\nPreferir agua natural; café sin azúcar.\nCaminar 30 minutos después de la comida.");
        $balance = self::plan('Alimentación equilibrada 1,900 kcal', 1900, [20, 50, 30], 'Mantener el peso y mejorar la calidad de la dieta.', "Incluir verdura en comida y cena.\nLimitar alimentos ultraprocesados a 1 vez por semana.");
        $gain = self::plan('Ganancia muscular 2,600 kcal', 2600, [25, 50, 25], 'Aumentar masa muscular con superávit moderado.', "Colación con proteína después de entrenar.\nNo saltarse el desayuno.");

        return array_map([self::class, 'patient'], [
            ['first' => 'Ana Lucía', 'last' => 'Gómez Prieto', 'sex' => 'Femenino', 'birth' => '1987-02-09', 'phone' => '5512870034', 'email' => 'ana.gomez@pacientes-pro.test', 'occupation' => 'Gerente de ventas', 'goal' => 'Bajar 8 kg antes de fin de año.',
                'pathologies' => [['Prediabetes / resistencia a la insulina', '2026-02-11', null]], 'height' => 164, 'weight' => 79.0, 'fat' => 36.0, 'waist' => 92, 'start' => 75, 'plan' => $deficit],
            ['first' => 'Roberto', 'last' => 'Salinas Ochoa', 'sex' => 'Masculino', 'birth' => '1975-07-30', 'phone' => '5534120987', 'email' => 'roberto.salinas@pacientes-pro.test', 'occupation' => 'Taxista', 'goal' => 'Controlar diabetes y bajar triglicéridos.',
                'pathologies' => [['Diabetes mellitus tipo 2', '2016-09-01', 'Metformina.'], ['Dislipidemia', '2024-01-15', null]], 'height' => 170, 'weight' => 92.4, 'fat' => 32.0, 'muscle' => 34.0, 'waist' => 108, 'hip_ratio' => 0.98, 'start' => 70, 'cancel' => [3], 'plan' => $deficit],
            ['first' => 'Fernanda', 'last' => 'Lozano Ruiz', 'sex' => 'Femenino', 'birth' => '1995-11-21', 'phone' => '5587120456', 'email' => 'fernanda.lozano@pacientes-pro.test', 'occupation' => 'Maestra de yoga', 'goal' => 'Mejorar la alimentación vegetariana.',
                'allergens' => [['Huevo', 'severa']], 'height' => 161, 'weight' => 57.8, 'weight_step' => 0.0, 'fat' => 25.0, 'fat_step' => -0.2, 'waist' => 70, 'waist_step' => -0.1, 'start' => 60, 'plan' => $balance],
            ['first' => 'Diego', 'last' => 'Montes Arriaga', 'sex' => 'Masculino', 'birth' => '1999-04-04', 'phone' => '5590123344', 'email' => 'diego.montes@pacientes-pro.test', 'occupation' => 'Futbolista amateur', 'goal' => 'Ganar masa muscular.',
                'height' => 181, 'weight' => 72.0, 'weight_step' => 0.5, 'fat' => 13.0, 'fat_step' => 0.0, 'muscle' => 34.0, 'muscle_step' => 0.5, 'waist' => 78, 'waist_step' => 0.1, 'hip_ratio' => 1.18, 'start' => 56, 'plan' => $gain],
            ['first' => 'Claudia', 'last' => 'Rangel Pérez', 'sex' => 'Femenino', 'birth' => '1969-08-13', 'phone' => '5523345566', 'email' => 'claudia.rangel@pacientes-pro.test', 'occupation' => 'Administradora', 'goal' => 'Bajar de peso y cuidar la presión.',
                'pathologies' => [['Hipertensión arterial', '2019-03-03', null], ['Hipotiroidismo', '2012-06-20', null]], 'allergens' => [['Leche (proteína de la leche)', 'moderada']], 'height' => 157, 'weight' => 74.0, 'fat' => 39.0, 'waist' => 95, 'start' => 50, 'plan' => $deficit],
            ['first' => 'Mauricio', 'last' => 'Treviño León', 'sex' => 'Masculino', 'birth' => '1983-12-01', 'phone' => '5578904432', 'email' => 'mauricio.trevino@pacientes-pro.test', 'occupation' => 'Abogado', 'goal' => 'Reducir grasa abdominal.',
                'pathologies' => [['Hígado graso (esteatosis hepática)', '2026-05-02', null]], 'height' => 177, 'weight' => 95.0, 'fat' => 30.0, 'muscle' => 37.0, 'waist' => 104, 'hip_ratio' => 1.0, 'start' => 44, 'last_payment_pending' => true, 'plan' => $deficit],
            ['first' => 'Itzel', 'last' => 'Cervantes Mora', 'sex' => 'Femenino', 'birth' => '2005-01-18', 'phone' => '5544556677', 'email' => 'itzel.cervantes@pacientes-pro.test', 'occupation' => 'Estudiante', 'goal' => 'Aprender a comer sin gluten.',
                'pathologies' => [['Enfermedad celíaca', '2025-12-10', null]], 'allergens' => [['Gluten (trigo, cebada, centeno)', 'severa']], 'height' => 159, 'weight' => 52.0, 'weight_step' => 0.2, 'fat' => 23.0, 'fat_step' => 0.0, 'waist' => 66, 'waist_step' => 0.0, 'start' => 30, 'plan' => $balance],
            ['first' => 'Javier', 'last' => 'Ortega Núñez', 'sex' => 'Masculino', 'birth' => '1960-05-25', 'phone' => '5511223344', 'email' => 'javier.ortega@pacientes-pro.test', 'occupation' => 'Jubilado', 'goal' => 'Mejorar glucosa y peso.', 'active' => false,
                'pathologies' => [['Diabetes mellitus tipo 2', '2010-01-01', null]], 'height' => 169, 'weight' => 88.0, 'fat' => 33.0, 'waist' => 103, 'hip_ratio' => 0.98, 'start' => 80, 'max_visits' => 3, 'plan' => $deficit],
            ['first' => 'Regina', 'last' => 'Bustamante Gil', 'sex' => 'Femenino', 'birth' => '1991-09-09', 'phone' => '5566778899', 'email' => 'regina.bustamante@pacientes-pro.test', 'occupation' => 'Arquitecta', 'goal' => 'Recuperar hábitos después del embarazo.',
                'pathologies' => [['Anemia', '2026-06-15', null]], 'height' => 166, 'weight' => 70.5, 'fat' => 33.5, 'waist' => 86, 'start' => 20, 'plan' => $balance],
            ['first' => 'Emiliano', 'last' => 'Paredes Soto', 'sex' => 'Masculino', 'birth' => '1997-03-14', 'phone' => '5522113300', 'email' => 'emiliano.paredes@pacientes-pro.test', 'occupation' => 'Programador', 'goal' => 'Organizar horarios de comida.',
                'height' => 175, 'weight' => 77.0, 'weight_step' => -0.2, 'fat' => 21.0, 'waist' => 84, 'start' => -3, 'max_visits' => 0],
        ]);
    }

    /**
     * Paciente de pro@ que ProDemoSeeder da de baja (soft delete) después de
     * cargar su historial: sirve para ver cómo quedan sus planes, pagos y
     * consultas cuando el paciente ya no está en la lista.
     */
    public static function proArchived(): array
    {
        $deficit = self::plan('Déficit moderado 1,800 kcal', 1800, [25, 45, 30], 'Bajar de peso antes de su cirugía de rodilla.', "Caminar 20 minutos diarios.\nEvitar refrescos y jugos.");

        return array_map([self::class, 'patient'], [
            ['first' => 'Óscar', 'last' => 'Villalobos Prado', 'sex' => 'Masculino', 'birth' => '1970-10-02', 'phone' => '5590887766', 'email' => 'oscar.villalobos@pacientes-pro.test', 'occupation' => 'Chofer de transporte', 'goal' => 'Bajar 10 kg antes de su cirugía.', 'active' => false,
                'pathologies' => [['Obesidad', null, null], ['Hipertensión arterial', '2020-08-14', null]], 'height' => 173, 'weight' => 104.0, 'fat' => 35.0, 'muscle' => 38.0, 'waist' => 112, 'hip_ratio' => 0.97, 'start' => 45, 'max_visits' => 2, 'future' => false, 'plan' => $deficit],
        ]);
    }

    /**
     * Paciente que DemoAccountsSeeder da de baja (soft delete) en cada cuenta
     * después de cargar su historial. Con plan solo si el paquete los incluye.
     */
    public static function archivedFor(string $domain, string $first, string $last, bool $withPlan): array
    {
        $plan = $withPlan
            ? self::plan('Plan de control 1,700 kcal', 1700, [22, 48, 30], 'Mejorar hábitos antes de su cambio de ciudad.', "Desayunar antes de las 9:00.\nIncluir verdura en dos comidas al día.")
            : null;

        $slug = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $first.'.'.explode(' ', $last)[0]));

        return array_map([self::class, 'patient'], [
            ['first' => $first, 'last' => $last, 'sex' => 'Masculino', 'birth' => '1977-05-19', 'phone' => '5530112233', 'email' => preg_replace('/[^a-z.]/', '', $slug).'@'.$domain, 'occupation' => 'Agente de ventas', 'goal' => 'Bajar de peso y mejorar la presión arterial.', 'active' => false,
                'pathologies' => [['Hipertensión arterial', '2021-04-04', null]], 'height' => 174, 'weight' => 96.0, 'fat' => 31.0, 'muscle' => 36.0, 'waist' => 104, 'hip_ratio' => 0.98, 'start' => 40, 'max_visits' => 2, 'future' => false, 'plan' => $plan],
        ]);
    }

    /* ==================================================================
     | josemanuelgarcializaola2212@gmail.com (Manuel, paquete Pro)
     * ================================================================== */

    public static function manuel(): array
    {
        $deficit = self::plan('Déficit calórico 1,700 kcal', 1700, [25, 45, 30], 'Bajar grasa corporal de forma gradual.', "Pesar los alimentos la primera semana.\nCaminar 30 minutos al día.");
        $sport = self::plan('Rendimiento deportivo 2,800 kcal', 2800, [22, 55, 23], 'Mejorar el rendimiento y la recuperación.', "Hidratarse antes, durante y después de entrenar.\nColación con carbohidrato y proteína al terminar.");
        $balance = self::plan('Alimentación equilibrada 2,000 kcal', 2000, [20, 50, 30], 'Mantener el peso con mejores hábitos.', "Comer sentado y sin pantallas.\nLimitar ultraprocesados a 1 vez por semana.");

        return array_map([self::class, 'patient'], [
            ['first' => 'Andrea', 'last' => 'Salazar Quintana', 'sex' => 'Femenino', 'birth' => '1992-06-14', 'phone' => '3312004501', 'email' => 'andrea.salazar@pacientes-manuel.test', 'occupation' => 'Dentista', 'goal' => 'Bajar 6 kg y mejorar la resistencia a la insulina.',
                'pathologies' => [['Prediabetes / resistencia a la insulina', '2026-04-10', null]], 'height' => 162, 'weight' => 74.0, 'fat' => 35.0, 'waist' => 88, 'start' => 30, 'plan' => $deficit],
            ['first' => 'Bruno', 'last' => 'Esparza Lima', 'sex' => 'Masculino', 'birth' => '2000-01-23', 'phone' => '3312004502', 'email' => 'bruno.esparza@pacientes-manuel.test', 'occupation' => 'Ciclista amateur', 'goal' => 'Mejorar rendimiento en ruta.',
                'allergens' => [['Nueces y frutos de cáscara', 'moderada']], 'height' => 176, 'weight' => 68.0, 'weight_step' => 0.2, 'fat' => 12.0, 'fat_step' => -0.1, 'muscle' => 33.0, 'muscle_step' => 0.3, 'waist' => 76, 'waist_step' => 0.0, 'hip_ratio' => 1.15, 'start' => 28, 'plan' => $sport],
            ['first' => 'Carmen', 'last' => 'Ochoa Villa', 'sex' => 'Femenino', 'birth' => '1966-03-08', 'phone' => '3312004503', 'email' => 'carmen.ochoa@pacientes-manuel.test', 'occupation' => 'Comerciante', 'goal' => 'Controlar la diabetes y la presión.',
                'pathologies' => [['Diabetes mellitus tipo 2', '2015-02-02', 'Metformina.'], ['Hipertensión arterial', '2019-08-08', null]], 'allergens' => [['Mariscos (crustáceos y moluscos)', 'severa']], 'height' => 155, 'weight' => 78.0, 'fat' => 40.0, 'waist' => 98, 'start' => 27, 'cancel' => [1], 'plan' => $deficit],
            ['first' => 'Damián', 'last' => 'Rojas Peralta', 'sex' => 'Masculino', 'birth' => '1988-11-30', 'phone' => '3312004504', 'email' => 'damian.rojas@pacientes-manuel.test', 'occupation' => 'Contador', 'goal' => 'Reducir grasa abdominal y triglicéridos.',
                'pathologies' => [['Dislipidemia', '2025-09-09', null], ['Hígado graso (esteatosis hepática)', '2026-01-15', null]], 'height' => 172, 'weight' => 91.0, 'fat' => 29.0, 'muscle' => 36.0, 'waist' => 101, 'hip_ratio' => 1.0, 'start' => 25, 'last_payment_pending' => true, 'plan' => $deficit],
            ['first' => 'Elena', 'last' => 'Fierro Campos', 'sex' => 'Femenino', 'birth' => '1997-07-07', 'phone' => '3312004505', 'email' => 'elena.fierro@pacientes-manuel.test', 'occupation' => 'Bailarina', 'goal' => 'Comer bien sin lácteos.',
                'allergens' => [['Leche (proteína de la leche)', 'severa']], 'height' => 166, 'weight' => 55.0, 'weight_step' => 0.0, 'fat' => 21.0, 'fat_step' => -0.1, 'waist' => 64, 'waist_step' => 0.0, 'start' => 22, 'plan' => $balance],
            ['first' => 'Gerardo', 'last' => 'Vela Montoya', 'sex' => 'Masculino', 'birth' => '1959-04-17', 'phone' => '3312004506', 'email' => 'gerardo.vela@pacientes-manuel.test', 'occupation' => 'Jubilado', 'goal' => 'Mejorar la glucosa.', 'active' => false,
                'pathologies' => [['Diabetes mellitus tipo 2', '2009-09-09', null]], 'height' => 168, 'weight' => 86.0, 'fat' => 32.0, 'waist' => 102, 'hip_ratio' => 0.98, 'start' => 26, 'max_visits' => 1, 'plan' => $deficit],
            ['first' => 'Ximena', 'last' => 'Arroyo Báez', 'sex' => 'Femenino', 'birth' => '2002-12-01', 'phone' => '3312004507', 'email' => 'ximena.arroyo@pacientes-manuel.test', 'occupation' => 'Estudiante', 'goal' => 'Organizar horarios de comida.',
                'height' => 160, 'weight' => 58.0, 'fat' => 26.0, 'waist' => 70, 'start' => -2, 'max_visits' => 0],
        ]);
    }

    /* ==================================================================
     | jorgejb234@gmail.com (prueba Esencial vencida: sin planes)
     * ================================================================== */

    public static function jorge(): array
    {
        return array_map([self::class, 'patient'], [
            ['first' => 'Lorena', 'last' => 'Méndez Arias', 'sex' => 'Femenino', 'birth' => '1990-02-18', 'phone' => '3398001101', 'email' => 'lorena.mendez@pacientes-jorge.test', 'occupation' => 'Maestra', 'goal' => 'Bajar de peso después del embarazo.',
                'pathologies' => [['Hipotiroidismo', '2020-05-05', null]], 'height' => 161, 'weight' => 72.0, 'fat' => 35.0, 'waist' => 87, 'start' => 22],
            ['first' => 'Marco', 'last' => 'Ibarra Toledo', 'sex' => 'Masculino', 'birth' => '1984-09-09', 'phone' => '3398001102', 'email' => 'marco.ibarra@pacientes-jorge.test', 'occupation' => 'Supervisor de almacén', 'goal' => 'Controlar ácido úrico.',
                'pathologies' => [['Hiperuricemia / gota', '2025-03-03', null]], 'allergens' => [['Pescado', 'moderada']], 'height' => 175, 'weight' => 94.0, 'fat' => 30.0, 'muscle' => 36.0, 'waist' => 103, 'hip_ratio' => 1.0, 'start' => 21, 'last_payment_pending' => true],
            ['first' => 'Natalia', 'last' => 'Cruz Esquivel', 'sex' => 'Femenino', 'birth' => '2001-08-28', 'phone' => '3398001103', 'email' => 'natalia.cruz@pacientes-jorge.test', 'occupation' => 'Estudiante de medicina', 'goal' => 'Mejorar energía durante guardias.',
                'pathologies' => [['Anemia', '2026-07-07', null]], 'height' => 158, 'weight' => 50.0, 'weight_step' => 0.2, 'fat' => 22.0, 'fat_step' => 0.0, 'waist' => 64, 'waist_step' => 0.0, 'start' => 20, 'cancel' => [1]],
            ['first' => 'Pedro', 'last' => 'Solís Garza', 'sex' => 'Masculino', 'birth' => '1972-01-12', 'phone' => '3398001104', 'email' => 'pedro.solis@pacientes-jorge.test', 'occupation' => 'Taxista', 'goal' => 'Bajar de peso.', 'active' => false,
                'pathologies' => [['Obesidad', null, null]], 'height' => 170, 'weight' => 108.0, 'fat' => 37.0, 'waist' => 118, 'hip_ratio' => 0.97, 'start' => 21, 'max_visits' => 1],
        ]);
    }

    /* ==================================================================
     | consultorio@nutrilife.test
     * ================================================================== */
    public static function consultorio(): array
    {
        $deficit = self::plan('Plan reducción 1,500 kcal', 1500, [25, 45, 30], 'Pérdida de peso gradual con control de glucosa.', "Respetar los horarios aunque no haya hambre.\nRegistrar glucosa capilar en ayuno 3 veces por semana.");
        $renal = self::plan('Plan cardiometabólico 1,800 kcal', 1800, [18, 52, 30], 'Cuidar presión arterial y lípidos.', "Menos de 2 g de sodio al día; no agregar sal en la mesa.\nEvitar embutidos y enlatados.");
        $kids = self::plan('Plan escolar 1,700 kcal', 1700, [18, 55, 27], 'Mejorar hábitos y energía para la escuela.', "Lunch con fruta y agua natural.\nEvitar bebidas azucaradas.");

        $names = [
            ['Teresa', 'Aguirre Delgado', 'Femenino', '1962-02-02', 'Ama de casa', 'Controlar la diabetes.', [['Diabetes mellitus tipo 2', '2014-05-05', null]], [], 155, 76.0, $deficit, 85],
            ['Rafael', 'Benítez Cano', 'Masculino', '1970-10-10', 'Chofer', 'Bajar presión y peso.', [['Hipertensión arterial', '2018-09-09', null], ['Obesidad', null, null]], [], 171, 101.0, $renal, 82],
            ['Lucía', 'Cordero Ibáñez', 'Femenino', '1994-06-06', 'Diseñadora', 'Comer mejor con SOP.', [['Síndrome de ovario poliquístico (SOP)', '2023-03-03', null]], [['Lactosa (intolerancia)', 'leve']], 163, 68.0, $deficit, 78],
            ['Óscar', 'Dávila Fuentes', 'Masculino', '1980-01-30', 'Ingeniero', 'Bajar colesterol.', [['Dislipidemia', '2025-07-07', null]], [], 178, 90.0, $renal, 74],
            ['Marisol', 'Echeverría Luna', 'Femenino', '1976-12-12', 'Contadora', 'Mejorar hábitos.', [], [['Mariscos (crustáceos y moluscos)', 'severa']], 160, 71.0, $renal, 70],
            ['Tomás', 'Figueroa Rey', 'Masculino', '2012-04-04', 'Estudiante de secundaria', 'Mejorar alimentación escolar.', [], [], 152, 52.0, $kids, 66],
            ['Paula', 'García Robles', 'Femenino', '2010-09-19', 'Estudiante', 'Más energía para entrenar natación.', [], [['Cacahuate', 'severa']], 160, 50.0, $kids, 63],
            ['Ignacio', 'Herrera Vidal', 'Masculino', '1955-07-07', 'Jubilado', 'Cuidar riñón y presión.', [['Enfermedad renal crónica', '2023-01-01', 'Etapa 3b.'], ['Hipertensión arterial', '2005-02-02', null]], [], 166, 70.0, $renal, 60],
            ['Brenda', 'Ibarra Solís', 'Femenino', '1989-08-08', 'Recepcionista', 'Bajar de peso.', [['Obesidad', null, null]], [], 158, 88.0, $deficit, 55],
            ['Saúl', 'Juárez Méndez', 'Masculino', '1993-05-05', 'Mesero', 'Mejorar digestión.', [['Enfermedad por reflujo gastroesofágico (ERGE)', '2025-04-04', null]], [], 173, 80.0, $renal, 50],
            ['Karla', 'Lara Quintero', 'Femenino', '1985-03-03', 'Psicóloga', 'Control de glucosa.', [['Prediabetes / resistencia a la insulina', '2026-01-01', null]], [['Huevo', 'moderada']], 162, 74.0, $deficit, 42],
            ['Arturo', 'Medina Rosas', 'Masculino', '1968-11-11', 'Comerciante', 'Bajar ácido úrico.', [['Hiperuricemia / gota', '2024-10-10', null]], [], 170, 93.0, $renal, 36],
            ['Valentina', 'Navarrete Paz', 'Femenino', '1998-02-14', 'Enfermera', 'Comer bien con turnos nocturnos.', [], [], 165, 63.0, $renal, 28],
            ['Hugo', 'Olvera Campos', 'Masculino', '1979-09-29', 'Albañil', 'Bajar de peso.', [['Obesidad', null, null]], [], 168, 104.0, $deficit, 14],
        ];

        $patients = [];

        foreach ($names as $i => [$first, $last, $sex, $birth, $occupation, $goal, $pathologies, $allergens, $height, $weight, $plan, $start]) {
            $slug = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $first.'.'.explode(' ', $last)[0]));

            $patients[] = [
                'first' => $first, 'last' => $last, 'sex' => $sex, 'birth' => $birth,
                'phone' => '55'.str_pad((string) (40000000 + $i * 1371), 8, '0', STR_PAD_LEFT),
                'email' => preg_replace('/[^a-z.]/', '', $slug).'@pacientes-consultorio.test',
                'occupation' => $occupation, 'goal' => $goal,
                'pathologies' => $pathologies, 'allergens' => $allergens,
                'height' => $height, 'weight' => $weight,
                'weight_step' => $plan === $kids ? 0.2 : -0.6,
                'fat' => $sex === 'Femenino' ? 34.0 : 27.0, 'fat_step' => $plan === $kids ? 0.0 : -0.4,
                'muscle' => $sex === 'Femenino' ? 23.0 : 33.0,
                'waist' => round($weight * 1.05, 1), 'waist_step' => $plan === $kids ? 0.2 : -0.8,
                'hip_ratio' => $sex === 'Femenino' ? 1.12 : 1.0,
                'start' => $start,
                'active' => $i !== 4,                  // Marisol: inactiva
                'cancel' => $i % 5 === 1 ? [2] : [],
                'last_payment_pending' => $i % 6 === 2,
                'price' => [700, 500],
                'plan' => $plan,
            ];
        }

        return array_map([self::class, 'patient'], $patients);
    }

    /* ==================================================================
     | prueba@nutrilife.test (prueba gratuita vigente)
     * ================================================================== */
    public static function trial(): array
    {
        $plan = self::plan('Primer plan 1,700 kcal', 1700, [22, 48, 30], 'Ordenar horarios y bajar de peso.', 'Llevar registro de comidas durante la primera semana.');

        return array_map([self::class, 'patient'], [
            ['first' => 'Nadia', 'last' => 'Quiroz Bello', 'sex' => 'Femenino', 'birth' => '1993-03-21', 'phone' => '3312450099', 'email' => 'nadia.quiroz@pacientes-prueba.test', 'occupation' => 'Contadora', 'goal' => 'Bajar de peso.',
                'height' => 160, 'weight' => 70.0, 'fat' => 33.0, 'waist' => 85, 'start' => 1, 'plan' => $plan],
            ['first' => 'Pablo', 'last' => 'Rentería Gil', 'sex' => 'Masculino', 'birth' => '1984-12-05', 'phone' => '3398760011', 'email' => 'pablo.renteria@pacientes-prueba.test', 'occupation' => 'Vendedor', 'goal' => 'Controlar la presión.',
                'pathologies' => [['Hipertensión arterial', '2022-02-22', null]], 'height' => 174, 'weight' => 86.0, 'fat' => 28.0, 'waist' => 98, 'hip_ratio' => 1.0, 'start' => -1, 'max_visits' => 0],
        ]);
    }

    /* ==================================================================
     | Subperfiles de la clínica consultorio@nutrilife.test (ClinicDemoSeeder)
     * ================================================================== */

    /** Lic. Andrea Solís (nutriologa.andrea@vidasana.test) */
    public static function clinicAndrea(): array
    {
        $deficit = self::plan('Plan reducción 1,600 kcal', 1600, [25, 45, 30], 'Bajar de peso de forma gradual.', "Cenar ligero antes de las 21:00.\nCaminar 30 minutos al día.");
        $gest = self::plan('Plan embarazo 2,100 kcal', 2100, [20, 50, 30], 'Cubrir requerimientos del segundo trimestre.', "Fraccionar en 5 tiempos.\nSuplemento de ácido fólico según indicación médica.");

        return array_map([self::class, 'patient'], [
            ['first' => 'Mónica', 'last' => 'Treviño Ruiz', 'sex' => 'Femenino', 'birth' => '1991-05-12', 'phone' => '5544100201', 'email' => 'monica.trevino@pacientes-vidasana.test', 'occupation' => 'Abogada', 'goal' => 'Bajar 5 kg y mejorar energía.',
                'height' => 164, 'weight' => 72.0, 'fat' => 34.0, 'waist' => 86, 'start' => 40, 'plan' => $deficit],
            ['first' => 'Sofía', 'last' => 'Garza Méndez', 'sex' => 'Femenino', 'birth' => '1995-09-03', 'phone' => '5544100202', 'email' => 'sofia.garza@pacientes-vidasana.test', 'occupation' => 'Arquitecta', 'goal' => 'Alimentación adecuada en el embarazo.',
                'pathologies' => [['Anemia', '2026-06-06', null]], 'height' => 160, 'weight' => 63.0, 'weight_step' => 0.4, 'fat' => 29.0, 'fat_step' => 0.1, 'waist' => 82, 'waist_step' => 0.5, 'start' => 35, 'plan' => $gest],
            ['first' => 'Ernesto', 'last' => 'Lugo Paredes', 'sex' => 'Masculino', 'birth' => '1975-02-27', 'phone' => '5544100203', 'email' => 'ernesto.lugo@pacientes-vidasana.test', 'occupation' => 'Gerente de ventas', 'goal' => 'Controlar triglicéridos.',
                'pathologies' => [['Dislipidemia', '2025-11-11', null]], 'height' => 176, 'weight' => 95.0, 'fat' => 30.0, 'muscle' => 35.0, 'waist' => 104, 'hip_ratio' => 1.0, 'start' => 30, 'last_payment_pending' => true, 'plan' => $deficit],
        ]);
    }

    /** Lic. Diego Navarro (nutriologo.diego@vidasana.test) */
    public static function clinicDiego(): array
    {
        $sport = self::plan('Plan deportivo 2,600 kcal', 2600, [22, 55, 23], 'Mejorar rendimiento y recuperación.', "Colación con proteína después de entrenar.\nHidratación con electrolitos en sesiones largas.");
        $renal = self::plan('Plan cuidado renal 1,800 kcal', 1800, [12, 58, 30], 'Cuidar la función renal.', "Limitar sodio y potasio según laboratorios.\nEvitar caldos concentrados.");

        return array_map([self::class, 'patient'], [
            ['first' => 'Rodrigo', 'last' => 'Ibáñez Coronado', 'sex' => 'Masculino', 'birth' => '1999-07-19', 'phone' => '5544100301', 'email' => 'rodrigo.ibanez@pacientes-vidasana.test', 'occupation' => 'Triatleta', 'goal' => 'Preparación para medio Ironman.',
                'height' => 178, 'weight' => 72.0, 'weight_step' => 0.1, 'fat' => 13.0, 'fat_step' => -0.1, 'muscle' => 36.0, 'muscle_step' => 0.2, 'waist' => 78, 'waist_step' => 0.0, 'hip_ratio' => 1.12, 'start' => 32, 'plan' => $sport],
            ['first' => 'Guadalupe', 'last' => 'Sandoval Mora', 'sex' => 'Femenino', 'birth' => '1958-12-08', 'phone' => '5544100302', 'email' => 'guadalupe.sandoval@pacientes-vidasana.test', 'occupation' => 'Jubilada', 'goal' => 'Cuidar los riñones.',
                'pathologies' => [['Enfermedad renal crónica', '2022-04-04', 'Etapa 3a.'], ['Hipertensión arterial', '2010-01-01', null]], 'height' => 156, 'weight' => 66.0, 'fat' => 36.0, 'waist' => 90, 'start' => 28, 'cancel' => [1], 'plan' => $renal],
            ['first' => 'Iván', 'last' => 'Robles Estrada', 'sex' => 'Masculino', 'birth' => '2004-03-15', 'phone' => '5544100303', 'email' => 'ivan.robles@pacientes-vidasana.test', 'occupation' => 'Estudiante', 'goal' => 'Ganar masa muscular.',
                'allergens' => [['Cacahuate', 'moderada']], 'height' => 172, 'weight' => 61.0, 'weight_step' => 0.3, 'fat' => 15.0, 'fat_step' => 0.0, 'muscle' => 30.0, 'muscle_step' => 0.3, 'waist' => 72, 'waist_step' => 0.1, 'start' => 20, 'plan' => $sport],
        ]);
    }
}

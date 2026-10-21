<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\DietPlan;
use App\Models\DietPlanDish;
use App\Models\DietPlanDishItem;
use App\Models\DietPlanItem;
use App\Models\DietPlanMeal;
use App\Models\Food;
use App\Models\Patient;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * DATOS DE PRUEBA POR CUENTA DE NUTRIÓLOGO (entorno local).
 *
 *   php artisan db:seed --class=DemoAccountsSeeder          (todas las cuentas de ACCOUNTS)
 *   php artisan db:seed --class=ProDemoSeeder               (solo pro@)
 *
 * Para cada cuenta carga lo necesario para probar lo que su paquete permite:
 *  - Pacientes (DemoPatients) con patologías y alergias, citas pasadas
 *    (completadas y canceladas) y futuras, consultas, evaluaciones
 *    (progreso), pagos (uno pendiente) y un paciente inactivo.
 *  - Un paciente dado de baja (soft delete) con su historial.
 *  - Citas de hoy (dashboard) y una próxima cita para cada paciente activo
 *    que no tenga ninguna a futuro.
 *  - Solo si el paquete incluye planes (Pro, Consultorio): planes (activo y
 *    finalizado), platillos propios, platillos insertados en planes (copia
 *    de ingredientes), un alimento personalizado y avisos de alergia.
 *  - 'subscription': si la cuenta no tiene un paquete vigente, le crea uno
 *    pagado (cobro "Simulado").
 *
 * No borra nada, no toca cuentas fuera de ACCOUNTS y se puede ejecutar
 * varias veces sin duplicar. Se niega a correr en producción. Fechas
 * relativas al día en que se ejecuta.
 */
class DemoAccountsSeeder extends Seeder
{
    /**
     * correo => configuración
     *  patients   → método de DemoPatients
     *  archived   → [dominio, nombre, apellidos] del paciente dado de baja
     *  plans      → el paquete incluye planes alimenticios
     *  today      → [[correo del paciente, hora, estado, motivo, nota]]
     *  own_dishes → nombre => [[alimento del catálogo | [personalizado: nombre, kcal, P, C, G], gramos]]
     *  dishes     → [[correo del paciente, comida nueva, hora, 'system:Nombre' | 'own:Nombre']]
     *  custom     → [correo del paciente, comida existente, [renglón personalizado]]
     *  subscription → paquete a activar si no tiene uno vigente
     */
    public const ACCOUNTS = [
        'pro@nutrilife.test' => [
            'patients' => 'pro',
            'archived' => 'proArchived',
            'plans' => true,
            'today' => [
                ['regina.bustamante@pacientes-pro.test', 13, 'Confirmada', 'Revisión de estudios de laboratorio', 'Confirmó por WhatsApp.'],
                ['itzel.cervantes@pacientes-pro.test', 19, 'Pendiente', 'Consulta de seguimiento', null],
            ],
            'own_dishes' => [
                'Ensalada de Nopales con Queso Panela' => [['Nopales (cocidos)', 150], ['Jitomate', 60], ['Cebolla blanca (cruda)', 20], ['Queso panela', 40], ['Aguacate Hass', 30]],
            ],
            'dishes' => [
                ['ana.gomez@pacientes-pro.test', 'Cena de fin de semana', '20:30', 'system:Tacos de Pescado a la Mexicana con Tortilla de Maíz'],
                ['diego.montes@pacientes-pro.test', 'Post-entreno', '19:00', 'system:Sándwich Integral de Pollo y Aguacate'],
                ['regina.bustamante@pacientes-pro.test', 'Comida (opción vegetariana)', '14:30', 'own:Ensalada de Nopales con Queso Panela'],
                // Alergia severa al huevo: el renglón muestra el aviso
                ['fernanda.lozano@pacientes-pro.test', 'Desayuno de domingo', '10:00', 'system:Tostadas de Aguacate con Huevo Pochado'],
            ],
            'custom' => ['ana.gomez@pacientes-pro.test', 'Colación vespertina', ['custom_name' => 'Barra de proteína comercial', 'custom_portion_description' => '1 pieza (45 g)', 'custom_kcal' => 190, 'custom_protein_g' => 15, 'custom_carbs_g' => 20, 'custom_lipids_g' => 6, 'grams' => 45]],
        ],

        'consultorio@nutrilife.test' => [
            'patients' => 'consultorio',
            'archived' => ['pacientes-consultorio.test', 'Rubén', 'Castañeda Ríos'],
            'plans' => true,
            'today' => [
                ['teresa.aguirre@pacientes-consultorio.test', 10, 'Confirmada', 'Revisión de glucosa', 'Traerá su registro de glucosa.'],
                ['brenda.ibarra@pacientes-consultorio.test', 17, 'Pendiente', 'Consulta de seguimiento', null],
            ],
            'own_dishes' => [
                'Caldo de Pollo con Verduras' => [['Pechuga de pollo sin piel (cocida)', 90], ['Zanahoria (cruda)', 64], ['Calabacita (cocida)', 90], ['Chayote (cocido)', 80], ['Arroz blanco (cocido)', 80]],
                'Lunch Escolar de Sándwich Integral' => [['Pan integral', 64], ['Queso panela', 40], ['Jitomate', 60], ['Manzana', 106], [['Agua de jamaica sin azúcar', 5, 0, 1, 0], 250]],
            ],
            'dishes' => [
                ['teresa.aguirre@pacientes-consultorio.test', 'Comida de domingo', '14:00', 'own:Caldo de Pollo con Verduras'],
                ['tomas.figueroa@pacientes-consultorio.test', 'Lunch escolar', '11:00', 'own:Lunch Escolar de Sándwich Integral'],
                ['rafael.benitez@pacientes-consultorio.test', 'Cena ligera', '20:00', 'system:Crema de Calabacita con Pechuga Deshebrada'],
                // Alergia moderada al huevo: el renglón muestra el aviso
                ['karla.lara@pacientes-consultorio.test', 'Desayuno de fin de semana', '09:30', 'system:Omelette de Claras con Espinacas y Queso'],
            ],
            'custom' => ['brenda.ibarra@pacientes-consultorio.test', 'Colación vespertina', ['custom_name' => 'Gelatina light de la tienda', 'custom_portion_description' => '1 vaso (125 g)', 'custom_kcal' => 15, 'custom_protein_g' => 2, 'custom_carbs_g' => 1, 'custom_lipids_g' => 0, 'grams' => 125]],
        ],

        'josemanuelgarcializaola2212@gmail.com' => [
            'patients' => 'manuel',
            'archived' => ['pacientes-manuel.test', 'Iván', 'Pacheco Luna'],
            'plans' => true,
            'subscription' => 'Pro',
            'today' => [
                ['andrea.salazar@pacientes-manuel.test', 12, 'Confirmada', 'Consulta de seguimiento', 'Confirmó por llamada.'],
                ['bruno.esparza@pacientes-manuel.test', 18, 'Pendiente', 'Ajuste de plan por competencia', null],
            ],
            'own_dishes' => [
                'Bowl de Arroz con Pollo y Frijoles' => [['Arroz integral (cocido)', 98], ['Pechuga de pollo sin piel (cocida)', 120], ['Frijol negro (cocido)', 86], ['Aguacate Hass', 30], ['Jitomate', 60]],
                'Licuado Recuperador de Plátano y Avena' => [['Leche descremada', 244], ['Plátano', 108], ['Avena en hojuelas (cruda)', 27], ['Miel de abeja', 7], [['Proteína en polvo (scoop)', 120, 24, 3, 1.5], 30]],
            ],
            'dishes' => [
                ['bruno.esparza@pacientes-manuel.test', 'Post-entreno', '19:30', 'own:Licuado Recuperador de Plátano y Avena'],
                ['andrea.salazar@pacientes-manuel.test', 'Comida de oficina', '14:00', 'own:Bowl de Arroz con Pollo y Frijoles'],
                ['damian.rojas@pacientes-manuel.test', 'Cena ligera', '20:00', 'system:Ensalada Griega con Pollo y Aceitunas'],
                // Alergia severa a la leche: el renglón muestra el aviso
                ['elena.fierro@pacientes-manuel.test', 'Desayuno de domingo', '10:00', 'system:Bowl de Avena con Plátano y Crema de Maní'],
            ],
            'custom' => ['bruno.esparza@pacientes-manuel.test', 'Colación matutina', ['custom_name' => 'Gel energético deportivo', 'custom_portion_description' => '1 sobre (32 g)', 'custom_kcal' => 100, 'custom_protein_g' => 0, 'custom_carbs_g' => 25, 'custom_lipids_g' => 0, 'grams' => 32]],
        ],

        // Paquete Esencial: sin planes alimenticios, platillos ni catálogos
        'esencial@nutrilife.test' => [
            'patients' => 'esencial',
            'archived' => ['pacientes.test', 'Samuel', 'Ordaz Pineda'],
            'plans' => false,
            'today' => [
                ['mariana.lopez@pacientes.test', 11, 'Confirmada', 'Consulta de seguimiento', 'Confirmó por WhatsApp.'],
                ['jorge.ramirez@pacientes.test', 18, 'Pendiente', 'Revisión de presión arterial', null],
            ],
        ],

        // Prueba Esencial vencida: los datos se ven al contratar un paquete
        'jorgejb234@gmail.com' => [
            'patients' => 'jorge',
            'archived' => ['pacientes-jorge.test', 'Tadeo', 'Luna Robles'],
            'plans' => false,
            'today' => [
                ['lorena.mendez@pacientes-jorge.test', 16, 'Confirmada', 'Consulta de seguimiento', null],
            ],
        ],
    ];

    private array $stats = [];

    public function run(): void
    {
        foreach (array_keys(self::ACCOUNTS) as $email) {
            $this->seedAccount($email);
        }
    }

    public function seedAccount(string $email): void
    {
        if (app()->environment('production')) {
            $this->command?->error('DemoAccountsSeeder no se ejecuta en producción.');

            return;
        }

        $config = self::ACCOUNTS[$email] ?? null;
        $user = User::where('email', $email)->first();

        if (! $config || ! $user) {
            $this->command?->warn("{$email}: no existe (o no está en ACCOUNTS); se omite.");

            return;
        }

        $this->stats = [];

        if (isset($config['subscription'])) {
            $this->ensureSubscription($user, $config['subscription']);
        }

        $demo = $this->container->make(DemoDataSeeder::class)->setContainer($this->container)->setCommand($this->command);

        if ($config['plans']) {
            // Restricciones de ejemplo (globales, marcadas "Dato de prueba")
            $demo->demoFoodRestrictions();
        }

        $before = Patient::withTrashed()->where('user_id', $user->id)->count();

        $demo->seedPatientsFor($user, DemoPatients::{$config['patients']}(), withDietPlans: $config['plans']);

        $archived = is_array($config['archived'])
            ? DemoPatients::archivedFor($config['archived'][0], $config['archived'][1], $config['archived'][2], $config['plans'])
            : DemoPatients::{$config['archived']}();
        $demo->seedPatientsFor($user, $archived, withDietPlans: $config['plans']);

        $this->stats['pacientes nuevos'] = Patient::withTrashed()->where('user_id', $user->id)->count() - $before;

        DB::transaction(function () use ($user, $config, $archived) {
            $this->archivePatient($user, $archived[0]['email']);
            $this->todayAppointments($user, $config['today'] ?? []);
            $this->nextAppointments($user);

            if ($config['plans']) {
                $own = $this->ownDishes($user, $config['own_dishes'] ?? []);
                $this->dishesInPlans($user, $config['dishes'] ?? [], $own);
                $this->customFood($user, $config['custom'] ?? null);
            }
        });

        $this->command?->info("{$email}: ".collect($this->stats)->map(fn ($n, $k) => "$n $k")->join(', ').'.');
    }

    /** Paquete pagado y vigente (1 mes) si la cuenta no tiene acceso pagado. */
    private function ensureSubscription(User $user, string $planName): void
    {
        $latest = $user->subscriptions()->latest('id')->first();

        if ($latest && $latest->hasPaidAccess() && $latest->plan?->name === $planName) {
            return;
        }

        $plan = SubscriptionPlan::where('name', $planName)->firstOrFail();
        $start = now()->startOfDay();

        $subscription = new Subscription;
        $subscription->forceFill([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => $start->toDateString(),
            'current_period_ends_at' => $start->copy()->addMonthNoOverflow()->toDateString(),
        ])->save();

        SubscriptionPayment::firstOrCreate(
            ['reference' => 'SEED-'.$user->access_code.'-'.$start->format('Ymd')],
            [
                'subscription_id' => $subscription->id,
                'amount' => $plan->price,
                'paid_at' => now(),
                'payment_method' => 'Simulado',
                'status' => 'pagado',
            ]
        );

        $this->stats['paquete '.$planName.' activado'] = 1;
    }

    /** El paciente de baja: historial cargado y después soft delete. */
    private function archivePatient(User $user, string $email): void
    {
        $patient = Patient::where('user_id', $user->id)->where('email', $email)->first();

        if ($patient) {
            $patient->deleted_at = now()->subDays(3);
            $patient->save();
            $this->stats['pacientes dados de baja'] = 1;
        }
    }

    /** Citas de hoy para el resumen del dashboard. */
    private function todayAppointments(User $user, array $appointments): void
    {
        $created = 0;

        foreach ($appointments as [$email, $hour, $status, $reason, $notes]) {
            $patient = Patient::where('user_id', $user->id)->where('email', $email)->first();

            if (! $patient || Appointment::where('patient_id', $patient->id)->whereDate('appointment_at', today())->exists()) {
                continue;
            }

            $this->appointment($user, $patient, today()->setTime($hour, 0), $status, $reason, $notes);
            $created++;
        }

        $this->stats['citas de hoy'] = $created;
    }

    /**
     * Próxima cita para cada paciente activo sin ninguna a futuro (también
     * los que ya existían en la cuenta), para que "Próximas citas" no quede
     * vacía.
     */
    private function nextAppointments(User $user): void
    {
        $created = 0;

        $patients = Patient::where('user_id', $user->id)->where('active', true)
            ->whereDoesntHave('appointments', fn ($query) => $query
                ->where('appointment_at', '>', now())
                ->whereIn('status', ['Pendiente', 'Confirmada']))
            ->orderBy('id')->get();

        foreach ($patients as $i => $patient) {
            $day = today()->addDays(2 + ($i % 9));
            if ($day->isSunday()) {
                $day->addDay();
            }

            $this->appointment($user, $patient, $day->setTime([9, 10, 12, 16, 17][$i % 5], 30), 'Pendiente', 'Consulta de seguimiento', null);
            $created++;
        }

        $this->stats['próximas citas'] = $created;
    }

    private function appointment(User $user, Patient $patient, $at, string $status, string $reason, ?string $notes): void
    {
        while (Appointment::where('user_id', $user->id)->where('appointment_at', $at)->exists()) {
            $at = $at->copy()->addMinutes(30);
        }

        $appointment = new Appointment;
        $appointment->forceFill([
            'user_id' => $user->id,
            'patient_id' => $patient->id,
            'appointment_at' => $at,
            'reason' => $reason,
            'status' => $status,
            'notes' => $notes,
        ])->save();
    }

    /** @return array<string, DietPlanDish> */
    private function ownDishes(User $user, array $definitions): array
    {
        $names = collect($definitions)->flatten(1)->pluck(0)->filter(fn ($name) => is_string($name));
        $foods = Food::whereIn('name', $names)->orderBy('id')->get()->unique('name')->keyBy('name');

        $dishes = [];
        $created = 0;

        foreach ($definitions as $name => $ingredients) {
            $dish = DietPlanDish::firstOrCreate(['user_id' => $user->id, 'name' => $name]);

            if ($dish->items()->doesntExist()) {
                foreach ($ingredients as [$ingredient, $grams]) {
                    if (is_array($ingredient)) {
                        [$customName, $kcal, $protein, $carbs, $lipids] = $ingredient;
                        $dish->items()->create(['custom_name' => $customName, 'custom_kcal' => $kcal, 'custom_protein_g' => $protein, 'custom_carbs_g' => $carbs, 'custom_lipids_g' => $lipids, 'grams' => $grams]);
                    } elseif (isset($foods[$ingredient])) {
                        $dish->items()->create(['food_id' => $foods[$ingredient]->id, 'grams' => $grams]);
                    }
                }
                $created++;
            }

            $dishes[$name] = $dish->load('items.food');
        }

        $this->stats['platillos propios'] = $created;

        return $dishes;
    }

    /**
     * Platillos insertados en planes activos, como lo hace el constructor
     * (DietPlanItemController@storeDish): se copian sus ingredientes.
     */
    private function dishesInPlans(User $user, array $targets, array $own): void
    {
        $inserted = 0;

        foreach ($targets as [$email, $mealName, $time, $reference]) {
            [$kind, $name] = explode(':', $reference, 2);
            $dish = $kind === 'own'
                ? ($own[$name] ?? null)
                : DietPlanDish::whereNull('user_id')->where('name', $name)->with('items.food')->first();

            $plan = $this->activePlan($user, $email);

            if (! $plan || ! $dish || $dish->items->isEmpty() || $plan->meals()->where('meal_name', $mealName)->exists()) {
                continue;
            }

            $meal = $plan->meals()->create([
                'meal_name' => $mealName,
                'meal_order' => min((int) $plan->meals()->max('meal_order') + 1, DietPlanMeal::MAX_ORDER),
                'meal_time' => $time,
                'notes' => 'Platillo «'.$dish->name.'» (dato de prueba).',
            ]);

            $meal->items()->createMany($dish->items->map(fn (DietPlanDishItem $ingredient) => $ingredient->isCustom()
                ? [
                    'diet_plan_dish_id' => $dish->id,
                    'custom_image_url' => $dish->image_url,
                    'custom_name' => $ingredient->custom_name,
                    'custom_kcal' => $ingredient->custom_kcal,
                    'custom_protein_g' => $ingredient->custom_protein_g,
                    'custom_carbs_g' => $ingredient->custom_carbs_g,
                    'custom_lipids_g' => $ingredient->custom_lipids_g,
                    'grams' => $ingredient->grams,
                    'equivalents' => 1,
                ]
                : [
                    'diet_plan_dish_id' => $dish->id,
                    'custom_image_url' => $dish->image_url,
                    'food_id' => $ingredient->food_id,
                    'grams' => $ingredient->grams,
                    'equivalents' => min(DietPlanItem::equivalentsFor($ingredient->food, (float) $ingredient->grams) ?? 1, DietPlanItem::MAX_EQUIVALENTS),
                ])->all());

            $inserted++;
        }

        $this->stats['platillos insertados en planes'] = $inserted;
    }

    /** Alimento personalizado suelto (fuera del catálogo) en una comida existente. */
    private function customFood(User $user, ?array $target): void
    {
        if (! $target) {
            return;
        }

        [$email, $mealName, $row] = $target;
        $meal = $this->activePlan($user, $email)?->meals()->where('meal_name', $mealName)->first();

        if ($meal && $meal->items()->where('custom_name', $row['custom_name'])->doesntExist()) {
            $meal->items()->create($row + ['equivalents' => 1]);
            $this->stats['alimentos personalizados'] = 1;
        }
    }

    private function activePlan(User $user, string $email): ?DietPlan
    {
        $patient = Patient::where('user_id', $user->id)->where('email', $email)->first();

        return $patient
            ? DietPlan::where('user_id', $user->id)->where('patient_id', $patient->id)->where('active', true)->latest('id')->first()
            : null;
    }
}

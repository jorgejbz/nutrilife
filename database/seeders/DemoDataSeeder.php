<?php

namespace Database\Seeders;

use App\Models\Allergen;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\DietPlan;
use App\Models\Evaluation;
use App\Models\Food;
use App\Models\Pathology;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * DATOS DE PRUEBA COMPLETOS (solo entorno local / pruebas).
 *
 *   php artisan db:seed --class=DemoDataSeeder
 *
 * - NO borra nada y se puede ejecutar varias veces: usuarios por correo,
 *   pacientes por (nutriólogo, correo). Si un paciente ya existía, no se le
 *   vuelven a crear citas, consultas, pagos ni planes.
 * - Conserva los 3 usuarios base (esencial@, pro@ y consultorio@
 *   nutrilife.test, los de database/sql/demo_usuarios_por_plan.sql). Si no
 *   existen, los crea con los mismos datos. Si su paquete ya venció, les
 *   agrega un periodo nuevo (cobro con método "Simulado").
 * - Agrega cuentas para probar cada estado: prueba vigente, prueba vencida,
 *   cuenta pendiente de activación, paquete cancelado y paquete vencido.
 * - Las fechas son RELATIVAS al día en que se ejecuta: siempre hay citas
 *   pasadas y futuras, pruebas vigentes y vencidas.
 * - Todo es ficticio (nombres, correos @…test, cédulas). Contraseña de todas
 *   las cuentas: NutriLife2026!  ← solo para desarrollo.
 *
 * Se niega a correr en producción.
 */
class DemoDataSeeder extends Seeder
{
    public const PASSWORD = 'NutriLife2026!';

    /** Cuentas base (mismos datos que demo_usuarios_por_plan.sql) */
    private const BASE_USERS = [
        'esencial@nutrilife.test' => ['name' => 'Dra. Laura Méndez Ortiz', 'code' => '724901', 'plan' => 'Esencial', 'license' => '10000001', 'degree' => 'LN-2012-00041'],
        'pro@nutrilife.test' => ['name' => 'Dr. Carlos Ibarra Ruiz', 'code' => '724902', 'plan' => 'Pro', 'license' => '10000002', 'degree' => 'LN-2010-00877'],
        'consultorio@nutrilife.test' => ['name' => 'Clínica Vida Sana (Dra. Paola Herrera)', 'code' => '724903', 'plan' => 'Consultorio', 'license' => '10000003', 'degree' => 'LN-2008-01290'],
    ];

    private array $usedSlots = [];

    private ?\Illuminate\Support\Collection $foods = null;

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->error('DemoDataSeeder no se ejecuta en producción.');

            return;
        }

        $this->call([
            SubscriptionPlanSeeder::class,
            ClinicalCatalogSeeder::class,
            FoodSeeder::class,
        ]);

        $this->demoFoodRestrictions();

        $this->foods = Food::with('allergens')->get()->keyBy('name');

        // 1) Cuentas base con su paquete pagado y vigente
        $base = [];
        foreach (self::BASE_USERS as $email => $data) {
            $base[$data['plan']] = $this->baseUser($email, $data);
        }

        // 2) Cuentas para probar estados de cuenta y suscripción
        $this->scenarioUsers();

        // 3) Expedientes, agenda, consultas, pagos y planes
        $this->clinicalData($base['Esencial'], DemoPatients::esencial(), withDietPlans: false);
        $this->clinicalData($base['Pro'], DemoPatients::pro(), withDietPlans: true);
        $this->clinicalData($base['Consultorio'], DemoPatients::consultorio(), withDietPlans: true);

        $trialUser = User::where('email', 'prueba@nutrilife.test')->first();
        if ($trialUser) {
            $this->clinicalData($trialUser, DemoPatients::trial(), withDietPlans: true);
        }

        $this->command?->info('Datos de prueba listos. Contraseña de las cuentas @nutrilife.test: '.self::PASSWORD);
    }

    /**
     * Restricciones por patología DE EJEMPLO (food_pathology_restrictions),
     * solo para ver los avisos del constructor de planes en local. No son
     * recomendaciones clínicas.
     */
    private function demoFoodRestrictions(): void
    {
        $pathologies = Pathology::pluck('id', 'name');
        $groups = \App\Models\FoodGroup::pluck('id', 'name');
        $foods = Food::pluck('id', 'name');

        $rules = [
            // [patología, 'grupo'|'alimento', nombre, tipo, máx. porciones/día]
            ['Diabetes mellitus tipo 2', 'grupo', 'Azúcares', 'limitado', 1],
            ['Diabetes mellitus tipo 2', 'grupo', 'Frutas', 'limitado', 3],
            ['Prediabetes / resistencia a la insulina', 'grupo', 'Azúcares', 'limitado', 1],
            ['Dislipidemia', 'alimento', 'Mantequilla con sal', 'limitado', 1],
            ['Hipertensión arterial', 'alimento', 'Sardina en aceite (escurrida)', 'limitado', 1],
            ['Enfermedad celíaca', 'alimento', 'Pan integral', 'prohibido', null],
        ];

        foreach ($rules as [$pathology, $kind, $target, $type, $max]) {
            $targetId = $kind === 'grupo' ? ($groups[$target] ?? null) : ($foods[$target] ?? null);

            if (! isset($pathologies[$pathology]) || ! $targetId) {
                continue;
            }

            \App\Models\FoodPathologyRestriction::firstOrCreate(
                [
                    'pathology_id' => $pathologies[$pathology],
                    'food_group_id' => $kind === 'grupo' ? $targetId : null,
                    'food_id' => $kind === 'alimento' ? $targetId : null,
                ],
                ['restriction_type' => $type, 'max_portions_per_day' => $max, 'notes' => 'Dato de prueba (DemoDataSeeder).']
            );
        }
    }

    /* ==================================================================
     | Usuarios y suscripciones
     * ================================================================== */

    private function plan(string $name): SubscriptionPlan
    {
        return SubscriptionPlan::where('name', $name)->firstOrFail();
    }

    private function newUser(string $email, array $attributes, Carbon $createdAt): User
    {
        $user = new User;
        $user->forceFill([
            'name' => $attributes['name'],
            'email' => $email,
            'email_verified_at' => $createdAt,
            'password' => Hash::make(self::PASSWORD),
            'role_id' => Role::NUTRITIONIST,
            'access_code' => $attributes['code'] ?? User::generateAccessCode(),
            'professional_license' => $attributes['license'] ?? null,
            'degree_number' => $attributes['degree'] ?? null,
            'account_status' => $attributes['status'] ?? User::ACCOUNT_ACTIVE,
            'activates_at' => $attributes['activates_at'] ?? null,
            'activated_at' => $attributes['activated_at'] ?? $createdAt,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->save();

        return $user;
    }

    private function subscription(User $user, string $plan, string $status, Carbon $start, Carbon $end, ?Carbon $createdAt = null, ?Carbon $cancelledAt = null): Subscription
    {
        $subscription = new Subscription;
        $subscription->forceFill([
            'user_id' => $user->id,
            'subscription_plan_id' => $this->plan($plan)->id,
            'status' => $status,
            'starts_at' => $start->toDateString(),
            'current_period_ends_at' => $end->toDateString(),
            'cancelled_at' => $cancelledAt?->toDateString(),
            'created_at' => $createdAt ?? $start,
            'updated_at' => $createdAt ?? $start,
        ])->save();

        return $subscription;
    }

    private function charge(Subscription $subscription, Carbon $paidAt, string $reference): void
    {
        SubscriptionPayment::firstOrCreate(
            ['reference' => $reference],
            [
                'subscription_id' => $subscription->id,
                'amount' => $subscription->plan->price,
                'paid_at' => $paidAt,
                'payment_method' => 'Simulado',
                'status' => 'pagado',
            ]
        );
    }

    private function baseUser(string $email, array $data): User
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            $created = now()->subDays(80)->setTime(10, 0);
            $user = $this->newUser($email, $data, $created);

            // Prueba de 7 días ya terminada + paquete pagado mes a mes
            $this->subscription($user, 'Esencial', Subscription::STATUS_EXPIRED, $created, $created->copy()->addDays(7), $created);
        } elseif ($user->professional_license === null && ! User::where('professional_license', $data['license'])->exists()) {
            // Usuario base cargado por SQL: se le agregan cédula y título ficticios
            $user->forceFill(['professional_license' => $data['license'], 'degree_number' => $data['degree']])->save();
        }

        $latest = $user->subscriptions()->latest('id')->first();

        if (! $latest || ! $latest->hasPaidAccess()) {
            // Periodo vigente: empezó hace 12 días y vence en ~18
            $start = now()->subDays(12)->startOfDay();
            $subscription = $this->subscription($user, $data['plan'], Subscription::STATUS_ACTIVE, $start, $start->copy()->addMonthNoOverflow());
            $subscription->load('plan');
            $this->charge($subscription, $start->copy()->setTime(10, 15), 'SEED-'.$data['code'].'-'.$start->format('Ym'));
        }

        return $user->fresh();
    }

    private function scenarioUsers(): void
    {
        $delay = (int) config('nutrilife.account_activation.delay_minutes', 5);

        $scenarios = [
            'prueba@nutrilife.test' => function (string $email) {
                $created = now()->subDays(2)->setTime(9, 30);
                $user = $this->newUser($email, ['name' => 'Mtra. Sofía Arenas (prueba vigente)', 'license' => '10000004', 'degree' => 'LN-2018-00310'], $created);
                $this->subscription($user, 'Esencial', Subscription::STATUS_TRIAL, $created, $created->copy()->addDays(Subscription::trialDays()), $created);
            },
            'prueba-vencida@nutrilife.test' => function (string $email) {
                $created = now()->subDays(Subscription::trialDays() + 3)->setTime(16, 0);
                $user = $this->newUser($email, ['name' => 'Lic. Andrés Pineda (prueba vencida)', 'license' => '10000005', 'degree' => 'LN-2019-00555'], $created);
                // Queda en_prueba con fecha pasada: el middleware la marca como vencida
                $this->subscription($user, 'Esencial', Subscription::STATUS_TRIAL, $created, $created->copy()->addDays(Subscription::trialDays()), $created);
            },
            'pendiente@nutrilife.test' => function (string $email) use ($delay) {
                $this->newUser($email, [
                    'name' => 'Lic. Daniela Rosas (cuenta pendiente)',
                    'license' => '10000006',
                    'degree' => 'LN-2024-00102',
                    'status' => User::ACCOUNT_PENDING,
                    'activates_at' => now()->addMinutes(max($delay, 1)),
                    'activated_at' => null,
                ], now());
            },
            'cancelada@nutrilife.test' => function (string $email) {
                $created = now()->subDays(50)->setTime(11, 0);
                $user = $this->newUser($email, ['name' => 'Dr. Emilio Vargas (Pro cancelado)', 'license' => '10000007', 'degree' => 'LN-2015-00733'], $created);
                $this->subscription($user, 'Esencial', Subscription::STATUS_EXPIRED, $created, $created->copy()->addDays(7), $created);
                $start = now()->subDays(20)->startOfDay();
                $sub = $this->subscription($user, 'Pro', Subscription::STATUS_CANCELLED, $start, $start->copy()->addMonthNoOverflow(), null, now()->subDays(2));
                $sub->load('plan');
                $this->charge($sub, $start->copy()->setTime(12, 0), 'SEED-CANC-'.$start->format('Ym'));
            },
            'vencida@nutrilife.test' => function (string $email) {
                $created = now()->subDays(70)->setTime(8, 0);
                $user = $this->newUser($email, ['name' => 'Lic. Irene Castañeda (paquete vencido)', 'license' => '10000008', 'degree' => 'LN-2016-00981'], $created);
                $this->subscription($user, 'Esencial', Subscription::STATUS_EXPIRED, $created, $created->copy()->addDays(7), $created);
                $start = now()->subMonthNoOverflow()->subDays(3)->startOfDay();
                $sub = $this->subscription($user, 'Esencial', Subscription::STATUS_EXPIRED, $start, $start->copy()->addMonthNoOverflow());
                $sub->load('plan');
                $this->charge($sub, $start->copy()->setTime(9, 0), 'SEED-VENC-'.$start->format('Ym'));
            },
        ];

        foreach ($scenarios as $email => $create) {
            if (! User::where('email', $email)->exists()) {
                DB::transaction(fn () => $create($email));
            }
        }
    }

    /* ==================================================================
     | Datos clínicos
     * ================================================================== */

    private function clinicalData(User $user, array $patients, bool $withDietPlans): void
    {
        $this->usedSlots = Appointment::where('user_id', $user->id)
            ->pluck('appointment_at')
            ->map(fn ($at) => Carbon::parse($at)->format('Y-m-d H:i'))
            ->flip()
            ->all();

        $pathologies = Pathology::pluck('id', 'name');
        $allergens = Allergen::pluck('id', 'name');
        $accountStart = $user->created_at->copy()->startOfDay();

        foreach ($patients as $index => $def) {
            if (Patient::withTrashed()->where('user_id', $user->id)->where('email', $def['email'])->exists()) {
                continue; // ya existe: no se duplica nada
            }

            DB::transaction(function () use ($user, $def, $index, $pathologies, $allergens, $accountStart, $withDietPlans) {
                $firstVisit = $this->workday(now()->subDays($def['start'])->startOfDay());
                if ($firstVisit->lte($accountStart)) {
                    $firstVisit = $this->workday($accountStart->copy()->addDays(1 + $index));
                }

                $createdAt = $firstVisit->copy()->subDays(1)->setTime(18, 0);
                if ($createdAt->isFuture()) {
                    $createdAt = now()->subHour();
                }
                if ($createdAt->lt($user->created_at)) {
                    $createdAt = $user->created_at->copy()->addHours(2);
                }

                $patient = new Patient;
                $patient->forceFill([
                    'user_id' => $user->id,
                    'first_name' => $def['first'],
                    'last_name' => $def['last'],
                    'birth_date' => $def['birth'],
                    'sex' => $def['sex'],
                    'phone' => $def['phone'],
                    'email' => $def['email'],
                    'occupation' => $def['occupation'],
                    'goal' => $def['goal'],
                    'active' => $def['active'],
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ])->save();

                foreach ($def['pathologies'] as [$name, $diagnosed, $notes]) {
                    if (isset($pathologies[$name])) {
                        $patient->pathologies()->attach($pathologies[$name], ['diagnosed_at' => $diagnosed, 'notes' => $notes, 'created_at' => $createdAt, 'updated_at' => $createdAt]);
                    }
                }

                foreach ($def['allergens'] as [$name, $severity]) {
                    if (isset($allergens[$name])) {
                        $patient->allergens()->attach($allergens[$name], ['severity' => $severity, 'created_at' => $createdAt, 'updated_at' => $createdAt]);
                    }
                }

                $this->agenda($user, $patient, $def, $firstVisit, $index);

                if ($withDietPlans && ! empty($def['plan'])) {
                    $this->dietPlans($user, $patient, $def, $firstVisit);
                }
            });
        }
    }

    /** Siguiente día hábil (lunes a sábado). */
    private function workday(Carbon $date): Carbon
    {
        return $date->isSunday() ? $date->copy()->addDay() : $date->copy();
    }

    /** Hora libre en la agenda del nutriólogo para ese día. */
    private function slot(Carbon $day, int $preferredHour): Carbon
    {
        $hours = [$preferredHour, 9, 10, 11, 12, 13, 16, 17, 18, 19];

        foreach ($hours as $hour) {
            foreach ([0, 30] as $minute) {
                $at = $day->copy()->setTime($hour, $minute);
                $key = $at->format('Y-m-d H:i');

                if (! isset($this->usedSlots[$key])) {
                    $this->usedSlots[$key] = true;

                    return $at;
                }
            }
        }

        return $day->copy()->setTime(20, 0);
    }

    /**
     * Citas cada 14 días desde la primera consulta: las pasadas quedan
     * Completadas (con consulta, evaluación y pago) o Canceladas; hay una
     * siguiente cita a futuro (Pendiente o Confirmada) si el paciente sigue
     * activo.
     */
    private function agenda(User $user, Patient $patient, array $def, Carbon $firstVisit, int $index): void
    {
        $hour = [9, 10, 11, 12, 16, 17, 18, 13][$index % 8];
        $visits = [];
        $date = $firstVisit->copy();

        // Visitas pasadas
        while ($date->lt(now()->startOfDay()) && count($visits) < $def['max_visits']) {
            $visits[] = $this->workday($date);
            $date->addDays(14);
        }

        $completed = 0;
        $previous = null;
        $lastCompletedAt = null;

        foreach ($visits as $i => $day) {
            $at = $this->slot($day, $hour);
            $isCancelled = in_array($i, $def['cancel'] ?? [], true);
            $reason = $completed === 0 ? 'Consulta inicial' : 'Consulta de seguimiento';

            $appointment = new Appointment;
            $appointment->forceFill([
                'user_id' => $user->id,
                'patient_id' => $patient->id,
                'appointment_at' => $at,
                'reason' => $reason,
                'status' => $isCancelled ? 'Cancelada' : 'Completada',
                'notes' => $isCancelled ? 'El paciente pidió cambiar la fecha.' : null,
                'created_at' => $previous ?? $patient->created_at,
                'updated_at' => $isCancelled ? $at->copy()->subDay() : $at->copy()->addMinutes(50),
            ])->save();

            if ($isCancelled) {
                continue;
            }

            $nextDay = isset($visits[$i + 1]) ? $visits[$i + 1] : null;
            $this->completedVisit($user, $patient, $def, $appointment, $completed, $nextDay, $i === count($visits) - 1);
            $completed++;
            $previous = $at->copy()->addMinutes(50);
            $lastCompletedAt = $at;
        }

        // Próxima cita (solo pacientes activos)
        if ($def['active'] && ($def['future'] ?? true)) {
            $next = $lastCompletedAt
                ? $this->workday($lastCompletedAt->copy()->startOfDay()->addDays(14))
                : $firstVisit->copy();

            if ($next->lte(now())) {
                $next = $this->workday(now()->startOfDay()->addDays(1 + ($index % 6)));
            }

            $at = $this->slot($next, $hour);

            $appointment = new Appointment;
            $appointment->forceFill([
                'user_id' => $user->id,
                'patient_id' => $patient->id,
                'appointment_at' => $at,
                'reason' => $completed === 0 ? 'Consulta inicial' : 'Consulta de seguimiento',
                'status' => $at->diffInDays(now(), true) <= 3 ? 'Confirmada' : 'Pendiente',
                'notes' => $at->diffInDays(now(), true) <= 3 ? 'Confirmó asistencia por WhatsApp.' : null,
                'created_at' => $previous ?? $patient->created_at,
                'updated_at' => $previous ?? $patient->created_at,
            ])->save();

            // La última consulta apunta a esta cita como "próxima consulta"
            Consultation::where('patient_id', $patient->id)
                ->latest('consultation_at')
                ->first()
                ?->forceFill(['next_visit' => $at->toDateString()])
                ->save();
        }
    }

    private function completedVisit(User $user, Patient $patient, array $def, Appointment $appointment, int $n, ?Carbon $nextDay, bool $isLast): void
    {
        $at = Carbon::parse($appointment->appointment_at);
        $weight = round($def['weight'] + $def['weight_step'] * $n, 1);
        $height = (float) $def['height'];
        $bmi = round($weight / (($height / 100) ** 2), 2);
        $fat = round($def['fat'] + $def['fat_step'] * $n, 1);
        $muscle = round($def['muscle'] + $def['muscle_step'] * $n, 1);
        $waist = round($def['waist'] + $def['waist_step'] * $n, 1);

        $observation = $n === 0
            ? 'Primera consulta. Objetivo del paciente: '.mb_strtolower($def['goal'])
            : 'Seguimiento '.$n.'. Cambio de peso desde la primera consulta: '
                .sprintf('%+.1f', $weight - $def['weight']).' kg.';

        $consultation = new Consultation;
        $consultation->forceFill([
            'user_id' => $user->id,
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
            'consultation_at' => $at->copy()->addMinutes(5),
            'reason' => $appointment->reason,
            'observations' => $observation.' Peso '.number_format($weight, 1).' kg, IMC '.number_format($bmi, 2).'.',
            'recommendations' => $def['recommendations'][$n % count($def['recommendations'])],
            'next_visit' => $nextDay?->toDateString() ?? ($def['active'] ? $this->workday($at->copy()->startOfDay()->addDays(14))->toDateString() : null),
            'created_at' => $at->copy()->addMinutes(50),
            'updated_at' => $at->copy()->addMinutes(50),
        ])->save();

        $evaluation = new Evaluation;
        $evaluation->forceFill([
            'patient_id' => $patient->id,
            'evaluation_date' => $at->toDateString(),
            'weight' => $weight,
            'height' => $height,
            'bmi' => $bmi,
            'body_fat' => $fat,
            'muscle_mass' => $muscle,
            'waist' => $waist,
            'hip' => round($waist * $def['hip_ratio'], 1),
            'notes' => $n === 0 ? 'Medición inicial (bioimpedancia, en ayuno).' : 'Misma báscula y mismas condiciones que la anterior.',
            'created_at' => $at->copy()->addMinutes(20),
            'updated_at' => $at->copy()->addMinutes(20),
        ])->save();

        $pending = $isLast && ($def['last_payment_pending'] ?? false);
        $methods = ['Efectivo', 'Transferencia', 'Tarjeta'];
        $method = $methods[($n + strlen($def['email'])) % 3];

        $payment = new Payment;
        $payment->forceFill([
            'user_id' => $user->id,
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
            'amount' => $n === 0 ? $def['price'][0] : $def['price'][1],
            'paid_at' => $at->copy()->addMinutes(55),
            'concept' => $appointment->reason,
            'payment_method' => $method,
            'status' => $pending ? 'Pendiente' : 'Pagado',
            'reference' => $method === 'Efectivo' ? null : strtoupper(substr($method, 0, 3)).'-'.$at->format('md').'-'.str_pad((string) ($appointment->id % 10000), 4, '0', STR_PAD_LEFT),
            'notes' => $pending ? 'Quedó en enviar la transferencia.' : null,
            'created_at' => $at->copy()->addMinutes(55),
            'updated_at' => $at->copy()->addMinutes(55),
        ])->save();
    }

    /**
     * Plan alimenticio del paciente (y uno anterior finalizado si lleva más
     * de un mes de seguimiento). Las comidas usan el catálogo de prueba y
     * evitan los alimentos con alérgenos del paciente.
     */
    private function dietPlans(User $user, Patient $patient, array $def, Carbon $firstVisit): void
    {
        $patient->load('allergens');
        $patientAllergens = $patient->allergens->pluck('id')->all();
        $config = $def['plan'];

        $plans = [];

        if ($firstVisit->diffInDays(now(), true) > 35) {
            $plans[] = [
                'name' => 'Plan de adaptación '.number_format($config['kcal'] + 200).' kcal',
                'kcal' => $config['kcal'] + 200,
                'macros' => DemoPatients::macros($config['kcal'] + 200, $config['split']),
                'starts' => $firstVisit,
                'ends' => $firstVisit->copy()->addDays(28),
                'active' => false,
            ];
        }

        $plans[] = [
            'name' => $config['name'],
            'kcal' => $config['kcal'],
            'macros' => DemoPatients::macros($config['kcal'], $config['split']),
            'starts' => $plans ? $firstVisit->copy()->addDays(28) : $firstVisit,
            'ends' => null,
            'active' => (bool) $def['active'],
        ];

        foreach ($plans as $data) {
            $plan = new DietPlan;
            $plan->forceFill([
                'user_id' => $user->id,
                'patient_id' => $patient->id,
                'name' => $data['name'],
                'goal' => $config['goal'],
                'instructions' => $config['instructions'],
                'target_kcal' => $data['kcal'],
                'target_protein_g' => $data['macros'][0],
                'target_carbs_g' => $data['macros'][1],
                'target_lipids_g' => $data['macros'][2],
                'starts_at' => $data['starts']->toDateString(),
                'ends_at' => $data['ends']?->toDateString(),
                'active' => $data['active'],
                'created_at' => $data['starts']->copy()->setTime(14, 0),
                'updated_at' => $data['starts']->copy()->setTime(14, 0),
            ])->save();

            $scale = $data['kcal'] / 1600;

            foreach (DemoPatients::MEAL_TEMPLATE as $order => [$mealName, $time, $notes, $items]) {
                $meal = $plan->meals()->create([
                    'meal_name' => $mealName,
                    'meal_order' => $order + 1,
                    'meal_time' => $time,
                    'notes' => $notes,
                ]);

                foreach ($items as [$foodName, $grams, $scales]) {
                    $food = $this->foods[$foodName] ?? null;

                    if (! $food || array_intersect($food->allergens->pluck('id')->all(), $patientAllergens)) {
                        continue;
                    }

                    $finalGrams = round($scales ? $grams * $scale : $grams, 0);

                    $meal->items()->create([
                        'food_id' => $food->id,
                        'grams' => $finalGrams,
                        'equivalents' => min(round($finalGrams / (float) $food->portion_grams, 2), 99.99),
                    ]);
                }
            }
        }
    }
}

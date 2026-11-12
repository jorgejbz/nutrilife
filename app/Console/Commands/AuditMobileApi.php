<?php

namespace App\Console\Commands;

use App\Models\Patient;
use App\Models\Role;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Laravel\Sanctum\Sanctum;
use Throwable;

/**
 * Auditoría de SOLO LECTURA de la API móvil con los datos reales.
 *
 * Llama cada endpoint GET de la app como cada paciente activo y revisa:
 *  - que no haya errores de servidor (5xx),
 *  - que los campos que la app Android declara NO nulos vengan con valor,
 *  - que las fechas tengan el formato que la app sabe leer.
 *
 * Solo imprime ids y conteos, nunca nombres, correos ni datos clínicos.
 *
 *   php artisan nutrilife:audit-mobile-api
 *   php artisan nutrilife:audit-mobile-api --patient=12
 */
class AuditMobileApi extends Command
{
    protected $signature = 'nutrilife:audit-mobile-api {--patient= : Solo este paciente (id)}';

    protected $description = 'Revisa (solo lectura) que la API de la app móvil responda bien para cada paciente';

    private const DATETIME = '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}(:\d{2})?$/';

    private const DATE = '/^\d{4}-\d{2}-\d{2}$/';

    /** @var array<string, list<string>> problema => ids de paciente */
    private array $issues = [];

    public function handle(Kernel $kernel): int
    {
        $patients = Patient::query()
            ->with('user')
            ->when($this->option('patient'), fn ($q, $id) => $q->whereKey($id))
            ->orderBy('id')
            ->get();

        $this->line("Pacientes a revisar: {$patients->count()}");

        $loginable = 0;

        foreach ($patients as $p) {
            $this->checkLoginData($p) && $loginable++;

            // La app solo entra con pacientes que pasan el middleware; aun así se
            // revisan todos para encontrar datos rotos antes de que entren.
            app('auth')->forgetGuards();
            Sanctum::actingAs($p, ['*'], 'sanctum');

            $from = now()->toDateString();
            $to = now()->addDays(30)->toDateString();

            foreach ([
                'me' => [],
                'diet' => [],
                'restrictions' => [],
                'appointments' => [],
                'consultations' => [],
                'evaluations' => [],
                'allergies' => [],
                'pathologies' => [],
                'availability' => ['from' => $from, 'to' => $to],
            ] as $path => $query) {
                $this->checkEndpoint($kernel, $p, $path, $query);
            }
        }

        $this->newLine();
        $this->line("Pueden iniciar sesión en la app: {$loginable} de {$patients->count()}");

        if ($this->issues === []) {
            $this->info('Sin problemas: todas las respuestas cumplen lo que espera la app.');

            return self::SUCCESS;
        }

        $this->warn('Problemas encontrados:');
        foreach ($this->issues as $issue => $ids) {
            $ids = array_values(array_unique($ids));
            $this->line(sprintf('  - %s → %d paciente(s): %s', $issue, count($ids), implode(', ', array_slice($ids, 0, 25))));
        }

        return self::FAILURE;
    }

    private function checkLoginData(Patient $p): bool
    {
        $nutri = $p->user;
        $ok = true;

        if (! $p->active || $p->deleted_at) {
            return false;
        }
        if (blank($p->email)) {
            $this->issue('Paciente activo sin correo (no puede entrar a la app)', $p);
            $ok = false;
        }
        if (! $nutri || (int) $nutri->role_id !== Role::NUTRITIONIST || blank($nutri->access_code)) {
            $this->issue('Nutriólogo sin código de acceso o con rol distinto (no puede entrar)', $p);
            $ok = false;
        } elseif ($nutri->isDisabled() || $nutri->isPendingActivation()) {
            $this->issue('Nutriólogo desactivado o pendiente (la app le niega el acceso)', $p);
            $ok = false;
        }

        return $ok;
    }

    private function checkEndpoint(Kernel $kernel, Patient $p, string $path, array $query): void
    {
        try {
            $request = Request::create("/api/{$path}", 'GET', $query, [], [], ['HTTP_ACCEPT' => 'application/json']);
            $response = $kernel->handle($request);
        } catch (Throwable $e) {
            $this->issue("/{$path}: excepción ".class_basename($e).' en '.basename($e->getFile()).':'.$e->getLine(), $p);

            return;
        }

        $status = $response->getStatusCode();
        $json = json_decode($response->getContent(), true);

        if ($status >= 500) {
            $this->issue("/{$path}: error {$status}", $p);

            return;
        }
        if ($status === 403 || ($path === 'diet' && $status === 404)) {
            return; // acceso negado con motivo, o paciente sin plan: casos esperados
        }
        if ($status !== 200 || ! is_array($json)) {
            $this->issue("/{$path}: respuesta inesperada {$status}", $p);

            return;
        }

        match ($path) {
            'me' => $this->required($p, $path, $json, ['id', 'first_name', 'last_name'], ['birth_date' => self::DATE]),
            'diet' => $this->checkDiet($p, $json),
            'restrictions' => $this->checkRestrictions($p, $json),
            'appointments' => $this->each($p, $path, $json, ['id', 'appointment_at', 'status', 'puede_modificar'], ['appointment_at' => self::DATETIME]),
            'consultations' => $this->each($p, $path, $json, ['id', 'consultation_at'], ['consultation_at' => self::DATETIME, 'next_visit' => self::DATE]),
            'evaluations' => $this->each($p, $path, $json, ['id', 'evaluation_date'], ['evaluation_date' => self::DATE]),
            'allergies' => $this->each($p, $path, $json, ['id', 'alergia']),
            'pathologies' => $this->each($p, $path, $json, ['id', 'patologia'], ['diagnosticada' => self::DATE]),
            'availability' => $this->required($p, $path, $json, ['desde', 'hasta', 'dias']),
        };
    }

    private function checkDiet(Patient $p, array $plan): void
    {
        $this->required($p, 'diet', $plan, ['id', 'name', 'target.kcal', 'target.protein_g', 'target.carbs_g', 'target.lipids_g', 'totals.kcal', 'meals']);

        foreach ($plan['meals'] ?? [] as $meal) {
            $this->required($p, 'diet.meals', $meal, ['id', 'name', 'order', 'kcal', 'items_count', 'entries']);
            foreach ($meal['entries'] ?? [] as $entry) {
                $this->required($p, 'diet.entries', $entry, ['type', 'id', 'name', 'kcal', 'items']);
                $this->each($p, 'diet.items', $entry['items'] ?? [], ['id', 'name', 'grams', 'kcal', 'protein_g', 'carbs_g', 'lipids_g', 'allergy_alert']);
            }
        }
    }

    private function checkRestrictions(Patient $p, array $r): void
    {
        $this->required($p, 'restrictions', $r, ['prohibidos', 'limitados', 'por_alergia']);
        $this->each($p, 'restrictions.reglas', array_merge($r['prohibidos'] ?? [], $r['limitados'] ?? []), ['patologia', 'tipo', 'aplica_a']);
        $this->each($p, 'restrictions.por_alergia', $r['por_alergia'] ?? [], ['alimento', 'alergenos']);
    }

    private function each(Patient $p, string $path, array $rows, array $fields, array $formats = []): void
    {
        foreach ($rows as $row) {
            $this->required($p, $path, (array) $row, $fields, $formats);
        }
    }

    private function required(Patient $p, string $path, array $row, array $fields, array $formats = []): void
    {
        foreach ($fields as $field) {
            if (Arr::get($row, $field) === null) {
                $this->issue("/{$path}: '{$field}' viene null y la app lo espera con valor", $p);
            }
        }
        foreach ($formats as $field => $regex) {
            $value = Arr::get($row, $field);
            if ($value !== null && ! preg_match($regex, (string) $value)) {
                $this->issue("/{$path}: '{$field}' con formato que la app no lee", $p);
            }
        }
    }

    private function issue(string $what, Patient $p): void
    {
        $this->issues[$what][] = (string) $p->id;
    }
}

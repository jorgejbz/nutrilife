<?php

namespace App\Http\Controllers;

use App\Models\Allergen;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\DietPlan;
use App\Models\Pathology;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PatientController extends Controller
{
    public function index()
    {
        $patients = Patient::where('user_id', Auth::id())
            ->latest()
            ->get();

        return view('patients.index', compact('patients'));
    }

    public function create()
    {
        return view('patients.create', $this->clinicalCatalogs());
    }

    public function store(Request $request)
    {

        // patients.email es la credencial de la app móvil (UNIQUE por nutriólogo):
        // se guarda siempre sin espacios y en minúsculas.
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date', 'after_or_equal:1900-01-01', 'before_or_equal:today'],
            'sex' => ['nullable', 'in:Masculino,Femenino,Otro'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => $this->emailRules(),
            'occupation' => ['nullable', 'string', 'max:150'],
            'goal' => ['nullable', 'string', 'max:1000'],
        ] + $this->clinicalRules(), $this->messages());

        [$pathologies, $allergens] = $this->clinicalSyncData($validated);
        unset($validated['pathologies'], $validated['allergens']);

        $validated['user_id'] = Auth::id();
        $validated['active'] = true;

        // Paciente + historial clínico (patient_pathologies, patient_allergens)
        DB::transaction(function () use ($validated, $pathologies, $allergens) {
            $patient = Patient::create($validated);
            $patient->pathologies()->sync($pathologies);
            $patient->allergens()->sync($allergens);
        });

        return redirect()
            ->route('pacientes.index')
            ->with('success', 'Paciente registrado correctamente.');
    }

 public function show(Patient $paciente)
{
    $this->authorizeManage($paciente);

    // Historial clínico (patient_pathologies, patient_allergens)
    $paciente->load(['pathologies' => fn ($query) => $query->orderBy('name'), 'allergens' => fn ($query) => $query->orderBy('name')]);

    // Solo tomamos evaluaciones válidas que tengan peso registrado
    $evaluations = $paciente->evaluations()
        ->whereNotNull('weight')
        ->orderBy('evaluation_date', 'desc')
        ->orderBy('id', 'desc')
        ->get();

    // Evaluación más reciente
    $latestEvaluation = $evaluations->first();

    // Evaluaciones ordenadas cronológicamente para la gráfica
    $chartEvaluations = $paciente->evaluations()
        ->whereNotNull('weight')
        ->orderBy('evaluation_date', 'asc')
        ->orderBy('id', 'asc')
        ->get();

    $firstEvaluation = $chartEvaluations->first();
$currentEvaluation = $chartEvaluations->last();

$weightChange = null;
$fatChange = null;

if ($firstEvaluation && $currentEvaluation) {

    if (
        !is_null($firstEvaluation->weight) &&
        !is_null($currentEvaluation->weight)
    ) {
        $weightChange = round(
            $currentEvaluation->weight - $firstEvaluation->weight,
            2
        );
    }

    if (
        !is_null($firstEvaluation->body_fat) &&
        !is_null($currentEvaluation->body_fat)
    ) {
        $fatChange = round(
            $currentEvaluation->body_fat - $firstEvaluation->body_fat,
            2
        );
    }
}


    $weightLabels = $chartEvaluations
        ->map(function ($evaluation) {
            return $evaluation->evaluation_date->format('d/m/Y');
        })
        ->values();


    $weightData = $chartEvaluations
        ->map(function ($evaluation) {
            return (float) $evaluation->weight;
        })
        ->values();

    // La gráfica necesita mínimo dos mediciones
    $showWeightChart = $chartEvaluations->count() >= 2;

    // Planes alimenticios del paciente: solo si el plan contratado incluye
    // la función (Pro, Consultorio o prueba). Esencial no los consulta.
    $dietPlans = Gate::allows('diet-plans')
        ? $paciente->dietPlans()
            // El dueño del plan es el nutriólogo responsable del paciente
            // (también cuando la cuenta principal de la clínica lo consulta).
            ->where('user_id', $paciente->user_id)
            ->with('meals.items.food')
            ->orderByDesc('active')
            ->orderByDesc('starts_at')
            ->get()
        : collect();

    return view('patients.show', compact(
    'paciente',
    'evaluations',
    'latestEvaluation',
    'weightLabels',
    'weightData',
    'showWeightChart',
    'firstEvaluation',
    'currentEvaluation',
    'weightChange',
    'fatChange',
    'dietPlans'
) + $this->responsibleData($paciente));
}
    public function edit(Patient $paciente)
    {
        $this->authorizeManage($paciente);

        $paciente->load(['pathologies', 'allergens']);

        return view('patients.edit', ['paciente' => $paciente]
            + $this->clinicalCatalogs()
            + $this->responsibleData($paciente)
            + ['teamOptions' => $this->assignableNutritionists($paciente)]);
    }


    public function update(Request $request, Patient $paciente)
    {
        $this->authorizeManage($paciente);

        // Nutriólogo responsable: solo la cuenta principal de la clínica lo
        // cambia, y solo entre su equipo activo (assignableNutritionists).
        $team = $this->assignableNutritionists($paciente);
        $ownerId = $team->isNotEmpty() && $team->contains('id', $request->integer('nutritionist_id'))
            ? $request->integer('nutritionist_id')
            : $paciente->user_id;

        // patients.email es la credencial de la app móvil (UNIQUE por nutriólogo):
        // se guarda siempre sin espacios y en minúsculas.
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date', 'after_or_equal:1900-01-01', 'before_or_equal:today'],
            'sex' => ['nullable', 'in:Masculino,Femenino,Otro'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => $this->emailRules($paciente, $ownerId),
            'occupation' => ['nullable', 'string', 'max:150'],
            'goal' => ['nullable', 'string', 'max:1000'],
            'active' => ['required', 'boolean'],
            'nutritionist_id' => $team->isNotEmpty()
                ? ['required', 'integer', Rule::in($team->pluck('id')->all())]
                : ['prohibited'],
        ] + $this->clinicalRules(), [
            'nutritionist_id.in' => 'Elige un nutriólogo activo de tu clínica.',
            'nutritionist_id.prohibited' => 'Solo la cuenta principal de la clínica puede cambiar al nutriólogo responsable.',
            'email.unique' => $ownerId !== $paciente->user_id
                ? 'Ese nutriólogo ya tiene un paciente (activo o eliminado) con este correo.'
                : 'Ya tienes un paciente (activo o eliminado) registrado con este correo.',
        ] + $this->messages());
        unset($validated['nutritionist_id']);

        $previousOwnerId = $paciente->user_id;
        $moved = ['appointments' => 0, 'diet_plans' => 0];

        [$pathologies, $allergens] = $this->clinicalSyncData($validated);
        unset($validated['pathologies'], $validated['allergens']);

        // sync(): agrega lo marcado, actualiza fechas/notas/severidad y quita
        // lo desmarcado (solo en las tablas pivote, nunca en los catálogos).
        DB::transaction(function () use ($paciente, $validated, $pathologies, $allergens, $ownerId, $previousOwnerId, &$moved) {
            $paciente->update($validated);
            $paciente->pathologies()->sync($pathologies);
            $paciente->allergens()->sync($allergens);

            if ($ownerId !== $previousOwnerId) {
                $moved = $this->reassign($paciente, $previousOwnerId, $ownerId);
            }
        });

        $message = 'Paciente actualizado correctamente.';

        if ($ownerId !== $previousOwnerId) {
            $message = 'Paciente actualizado. Ahora lo atiende '.User::find($ownerId)?->name
                .($moved['appointments'] ? ' · '.$moved['appointments'].' cita(s) próximas pasaron a su agenda' : '')
                .($moved['diet_plans'] ? ' · '.$moved['diet_plans'].' plan(es) alimenticio(s) pasaron a su cuenta' : '')
                .'.';
        }

        return redirect()
            ->route('pacientes.show', $paciente)
            ->with('success', $message);
    }

    public function destroy(Patient $paciente)
    {
        abort_if($paciente->user_id !== Auth::id(), 403);

        $paciente->delete();

        return redirect()
            ->route('pacientes.index')
            ->with('success', 'Paciente eliminado correctamente.');
    }

    /**
     * Nueva BD: patients.email es NOT NULL y único por nutriólogo
     * (UNIQUE user_id + email). La regla incluye pacientes eliminados
     * (soft delete) porque el índice de la BD también los cuenta.
     */
    private function emailRules(?Patient $patient = null, ?int $ownerId = null): array
    {
        $unique = Rule::unique('patients', 'email')
            ->where('user_id', $ownerId ?? Auth::id());

        if ($patient) {
            $unique->ignore($patient->id);
        }

        return ['required', 'email', 'max:255', $unique];
    }

    /* ------------------------------------------------------------------
     | Nutriólogo responsable (patients.user_id)
     * ------------------------------------------------------------------ */

    /**
     * Ver y editar: el nutriólogo responsable o la cuenta principal de su
     * clínica. Los subperfiles solo ven sus propios pacientes.
     */
    private function authorizeManage(Patient $patient): void
    {
        $user = Auth::user();

        abort_unless(
            $patient->user_id === $user->id
                || ($user->isClinic() && in_array($patient->user_id, $user->clinicNutritionistIds(), true)),
            403
        );
    }

    /** Datos para mostrar al responsable en la ficha y el formulario. */
    private function responsibleData(Patient $patient): array
    {
        $user = Auth::user();
        $responsible = $patient->user_id === $user->id ? $user : User::find($patient->user_id);

        return [
            'responsible' => $responsible,
            // ¿La cuenta autenticada atiende al paciente? Evaluaciones, citas y
            // planes los registra solo su nutriólogo responsable.
            'canTreat' => $patient->user_id === $user->id,
            'inClinic' => $user->isClinic() || $user->isClinicMember(),
            'responsibleIsMain' => $responsible && ! $responsible->isClinicMember(),
        ];
    }

    /**
     * Equipo al que la cuenta principal puede asignar el paciente: ella
     * misma y sus subperfiles activos (el responsable actual siempre se
     * incluye). Vacío para cualquier otra cuenta.
     */
    private function assignableNutritionists(Patient $patient)
    {
        $user = Auth::user();

        if (! $user->isClinic()) {
            return collect();
        }

        return User::whereIn('id', $user->clinicNutritionistIds())
            ->orderByRaw('id = ? desc', [$user->id])
            ->orderBy('name')
            ->get()
            ->filter(fn (User $member) => $member->id === $patient->user_id || ! $member->isDisabled())
            ->values();
    }

    /**
     * Cambia al nutriólogo responsable. Pasan con el paciente sus citas
     * abiertas a futuro (Pendiente / Confirmada) y sus planes alimenticios
     * (el dueño del plan es siempre el del paciente). Consultas, pagos,
     * evaluaciones y citas pasadas se quedan con quien las registró.
     *
     * @return array{appointments: int, diet_plans: int}
     */
    private function reassign(Patient $patient, int $from, int $to): array
    {
        $patient->forceFill(['user_id' => $to])->save();

        $appointments = Appointment::where('patient_id', $patient->id)
            ->where('user_id', $from)
            ->whereIn('status', ['Pendiente', 'Confirmada'])
            ->where('appointment_at', '>=', now())
            ->update(['user_id' => $to]);

        $dietPlans = DietPlan::withTrashed()->where('patient_id', $patient->id)
            ->where('user_id', $from)
            ->update(['user_id' => $to]);

        AuditLog::record(Auth::user(), 'patient.reassigned', $patient, [
            'from_user_id' => $from,
            'to_user_id' => $to,
            'appointments_moved' => $appointments,
            'diet_plans_moved' => $dietPlans,
        ]);

        return ['appointments' => $appointments, 'diet_plans' => $dietPlans];
    }

    private function messages(): array
    {
        return [
            'birth_date.date' => 'Escribe una fecha de nacimiento válida.',
            'birth_date.before_or_equal' => 'La fecha de nacimiento no puede ser futura.',
            'birth_date.after_or_equal' => 'Revisa la fecha de nacimiento: debe ser posterior a 1900.',
            'pathologies.*.diagnosed_at.date' => 'Escribe una fecha de diagnóstico válida.',
            'pathologies.*.diagnosed_at.before_or_equal' => 'La fecha de diagnóstico no puede ser futura.',
            'pathologies.*.notes.max' => 'Las notas de la patología no pueden superar 1000 caracteres.',
            'allergens.*.severity.in' => 'Selecciona una severidad válida (leve, moderada o severa).',
            'email.required' => 'El correo del paciente es obligatorio: es su acceso a la app móvil.',
            'email.unique' => 'Ya tienes un paciente (activo o eliminado) registrado con este correo.',
        ];
    }

    /* ------------------------------------------------------------------
     | Historial clínico (patient_pathologies, patient_allergens)
     * ------------------------------------------------------------------ */

    /** Catálogos globales para el formulario. */
    private function clinicalCatalogs(): array
    {
        return [
            'pathologies' => Pathology::orderBy('name')->get(),
            'allergens' => Allergen::orderBy('name')->get(),
        ];
    }

    /**
     * El formulario envía un arreglo por cada opción del catálogo, con la
     * clave = id del catálogo:
     *   pathologies[ID][selected|diagnosed_at|notes]
     *   allergens[ID][selected|severity]
     */
    private function clinicalRules(): array
    {
        return [
            'pathologies' => ['nullable', 'array'],
            'pathologies.*.selected' => ['nullable', 'boolean'],
            'pathologies.*.diagnosed_at' => ['nullable', 'date', 'after_or_equal:1900-01-01', 'before_or_equal:today'],
            'pathologies.*.notes' => ['nullable', 'string', 'max:1000'],
            'allergens' => ['nullable', 'array'],
            'allergens.*.selected' => ['nullable', 'boolean'],
            'allergens.*.severity' => ['nullable', Rule::in(Patient::ALLERGY_SEVERITIES)],
        ];
    }

    /**
     * Convierte lo marcado en el formulario al formato de sync() y verifica
     * que cada id exista en su catálogo (FK pathology_id / allergen_id).
     *
     * @return array{0: array<int, array>, 1: array<int, array>}
     */
    private function clinicalSyncData(array $validated): array
    {
        $selected = fn (array $items) => collect($items)
            ->filter(fn ($item) => ! empty($item['selected']))
            ->mapWithKeys(fn ($item, $id) => [(int) $id => $item]);

        $pathologies = $selected($validated['pathologies'] ?? []);
        $allergens = $selected($validated['allergens'] ?? []);

        $missing = [];

        if (Pathology::whereIn('id', $pathologies->keys())->count() !== $pathologies->count()) {
            $missing['pathologies'] = 'Una de las patologías seleccionadas ya no existe en el catálogo.';
        }

        if (Allergen::whereIn('id', $allergens->keys())->count() !== $allergens->count()) {
            $missing['allergens'] = 'Uno de los alérgenos seleccionados ya no existe en el catálogo.';
        }

        if ($missing) {
            throw ValidationException::withMessages($missing);
        }

        return [
            $pathologies->map(fn ($item) => [
                'diagnosed_at' => $item['diagnosed_at'] ?? null,
                'notes' => filled($item['notes'] ?? null) ? trim($item['notes']) : null,
            ])->all(),
            $allergens->map(fn ($item) => [
                'severity' => $item['severity'] ?? null,
            ])->all(),
        ];
    }
}

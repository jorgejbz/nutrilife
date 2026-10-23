<?php

namespace App\Http\Controllers;

use App\Models\DietPlan;
use App\Models\DietPlanDish;
use App\Models\DietPlanMeal;
use App\Models\Food;
use App\Models\Patient;
use App\Services\DietPlanFoodWarnings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Planes alimenticios (tabla diet_plans).
 *
 * Acceso: rutas con middleware feature:diet_plans (Pro, Consultorio, prueba)
 * y DietPlanPolicy (el plan debe ser del nutriólogo). Cada consulta además
 * filtra por user_id, así que una cuenta nunca lista datos de otra.
 */
class DietPlanController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', DietPlan::class);

        $patients = Patient::where('user_id', Auth::id())
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name']);

        // Filtros opcionales (?paciente=ID&estado=activos|finalizados)
        $patientId = $request->integer('paciente') ?: null;

        if ($patientId && ! $patients->contains('id', $patientId)) {
            $patientId = null;
        }

        $status = in_array($request->query('estado'), ['activos', 'finalizados'], true)
            ? $request->query('estado')
            : null;

        // Búsqueda por nombre del plan, objetivo o nombre del paciente
        $search = trim((string) $request->query('q', ''));

        $allPlans = DietPlan::ownedBy(Auth::id());

        $plans = DietPlan::ownedBy(Auth::id())
            ->with(['patient', 'meals.items.food'])
            ->when($patientId, fn ($query) => $query->where('patient_id', $patientId))
            ->when($status === 'activos', fn ($query) => $query->where('active', true))
            ->when($status === 'finalizados', fn ($query) => $query->where('active', false))
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $search).'%';

                $query->where(function ($query) use ($like) {
                    $query->where('name', 'like', $like)
                        ->orWhere('goal', 'like', $like)
                        ->orWhereHas('patient', fn ($patient) => $patient
                            ->where('first_name', 'like', $like)
                            ->orWhere('last_name', 'like', $like));
                });
            })
            ->orderByDesc('active')
            ->orderByDesc('starts_at')
            ->orderByDesc('id')
            ->get();

        return view('diet-plans.index', [
            'plans' => $plans,
            'patients' => $patients,
            'patientId' => $patientId,
            'status' => $status,
            'search' => $search,
            'totalPlans' => (clone $allPlans)->count(),
            'activePlans' => (clone $allPlans)->where('active', true)->count(),
            'patientsWithPlan' => (clone $allPlans)->where('active', true)->distinct()->count('patient_id'),
        ]);
    }

    public function create(Request $request)
    {
        Gate::authorize('create', DietPlan::class);

        $patients = $this->selectablePatients();

        return view('diet-plans.create', [
            'patients' => $patients,
            // ?paciente=ID (desde la ficha del paciente) preselecciona al paciente
            'selectedPatientId' => $patients->contains('id', $request->integer('paciente'))
                ? $request->integer('paciente')
                : null,
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('create', DietPlan::class);

        $validated = $request->validate(
            $this->rules(includePatient: true),
            $this->messages(),
            $this->attributes()
        );

        $plan = DietPlan::create([
            ...$validated,
            'user_id' => Auth::id(),
            'ends_at' => $validated['ends_at'] ?? null,
            'active' => true,
        ]);

        return redirect()
            ->route('diet-plans.show', $plan)
            ->with('success', 'Plan creado. Ahora agrega los tiempos de comida y sus alimentos.');
    }

    public function show(DietPlan $plan)
    {
        Gate::authorize('view', $plan);

        // dish.items: receta original, para avisar si la copia del plan se personalizó
        $plan->load(['patient.allergens', 'patient.pathologies', 'meals.items.food.foodGroup', 'meals.items.dish.items']);

        // Contexto clínico del paciente (solo se muestra; no se calcula nada)
        $latestEvaluation = $plan->patient?->evaluations()
            ->orderByDesc('evaluation_date')->orderByDesc('id')->first();
        $lastConsultation = $plan->patient?->consultations()
            ->where('user_id', Auth::id())
            ->orderByDesc('consultation_at')->first();

        $foods = Food::where('active', true)
            ->with('foodGroup')
            ->orderBy('name')
            ->get();

        $usedFoods = $plan->meals->flatMap->items->pluck('food')->filter();

        // Platillos del sistema y propios (actualización 3.2). Primero los que
        // tienen ingredientes; los vacíos se muestran pero no se pueden elegir.
        $dishes = DietPlanDish::visibleTo(Auth::id())
            ->with('items.food')
            ->orderBy('name')
            ->get()
            ->sortBy(fn (DietPlanDish $dish) => $dish->items->isEmpty())
            ->values();

        $dishFoods = $dishes->flatMap->items->pluck('food')->filter();

        return view('diet-plans.show', [
            'plan' => $plan,
            'foods' => $foods,
            'foodGroups' => $foods->pluck('foodGroup')->filter()->unique('id')->sortBy('name')->values(),
            'warnings' => DietPlanFoodWarnings::forPatient(
                $plan->patient,
                $foods->concat($usedFoods)->concat($dishFoods)->unique('id')
            ),
            'dishes' => $dishes,
            'mealSuggestions' => DietPlanMeal::SUGGESTED_NAMES,
            'latestEvaluation' => $latestEvaluation,
            'lastConsultation' => $lastConsultation,
            // Para "Duplicar / asignar a otro paciente"
            'patients' => $this->selectablePatients(),
        ]);
    }

    public function edit(DietPlan $plan)
    {
        Gate::authorize('update', $plan);

        $plan->load('patient');

        return view('diet-plans.edit', compact('plan'));
    }

    public function update(Request $request, DietPlan $plan)
    {
        Gate::authorize('update', $plan);

        // El paciente no se cambia: las comidas se diseñaron para él.
        $validated = $request->validate(
            $this->rules(includePatient: false),
            $this->messages(),
            $this->attributes()
        );

        $plan->update([
            ...$validated,
            'ends_at' => $validated['ends_at'] ?? null,
            'active' => $request->boolean('active'),
        ]);

        return redirect()
            ->route('diet-plans.show', $plan)
            ->with('success', 'Plan actualizado correctamente.');
    }

    public function destroy(DietPlan $plan)
    {
        Gate::authorize('delete', $plan);

        // diet_plans tiene deleted_at: se archiva (soft delete) y conserva
        // sus comidas, igual que pacientes, consultas y pagos.
        $plan->delete();

        return redirect()
            ->route('diet-plans.index')
            ->with('success', 'El plan "'.$plan->name.'" se eliminó.');
    }

    /**
     * Duplica el plan (datos, comidas y alimentos) para el mismo paciente o
     * para otro paciente propio y activo. Sirve para reutilizar una
     * estructura o asignarla a otro paciente. El plan original no cambia.
     */
    public function duplicate(Request $request, DietPlan $plan)
    {
        Gate::authorize('view', $plan);
        Gate::authorize('create', DietPlan::class);

        $validated = $request->validate([
            'patient_id' => [
                'required',
                'integer',
                Rule::exists('patients', 'id')
                    ->where('user_id', Auth::id())
                    ->where('active', true)
                    ->whereNull('deleted_at'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date', 'after_or_equal:2000-01-01', 'before_or_equal:2100-12-31'],
            'finish_original' => ['nullable', 'boolean'],
        ], $this->messages(), $this->attributes());

        $plan->load('meals.items');

        $copy = DB::transaction(function () use ($plan, $validated, $request) {
            $copy = $plan->replicate(['created_at', 'updated_at', 'deleted_at']);
            $copy->fill([
                'patient_id' => (int) $validated['patient_id'],
                'name' => $validated['name'],
                'starts_at' => $validated['starts_at'],
                'ends_at' => null,
                'active' => true,
            ]);
            $copy->user_id = Auth::id();
            $copy->save();

            foreach ($plan->meals as $meal) {
                $newMeal = $copy->meals()->create($meal->only(['meal_name', 'meal_order', 'meal_time', 'notes']));

                // replicate(): copia todas las columnas del renglón (catálogo,
                // personalizado y platillo de origen); save() lo liga a la comida nueva.
                foreach ($meal->items as $item) {
                    $newMeal->items()->save($item->replicate());
                }
            }

            // Mismo paciente: opcionalmente se finaliza el plan anterior
            if ($request->boolean('finish_original') && (int) $plan->patient_id === (int) $copy->patient_id) {
                $plan->update(['active' => false, 'ends_at' => $plan->ends_at ?? now()->toDateString()]);
            }

            return $copy;
        });

        return redirect()
            ->route('diet-plans.show', $copy)
            ->with('success', 'Se creó «'.$copy->name.'» a partir de «'.$plan->name.'». Ajusta lo que necesites.');
    }

    /* ------------------------------------------------------------------
     | Validación
     * ------------------------------------------------------------------ */

    /** Pacientes propios, vigentes y activos (para elegir en el formulario). */
    private function selectablePatients()
    {
        return Patient::where('user_id', Auth::id())
            ->where('active', true)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    private function rules(bool $includePatient): array
    {
        $decimal = fn (float $max) => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:'.$max];

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            // diet_plans.goal / instructions (actualización 3.1, opcionales)
            'goal' => ['nullable', 'string', 'max:255'],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'starts_at' => ['required', 'date', 'after_or_equal:2000-01-01', 'before_or_equal:2100-12-31'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at', 'before_or_equal:2100-12-31'],
            // diet_plans.target_kcal DECIMAL(7,2); macros DECIMAL(6,2)
            'target_kcal' => [...$decimal(DietPlan::MAX_KCAL), 'gt:0'],
            'target_protein_g' => $decimal(DietPlan::MAX_MACRO_G),
            'target_carbs_g' => $decimal(DietPlan::MAX_MACRO_G),
            'target_lipids_g' => $decimal(DietPlan::MAX_MACRO_G),
        ];

        if ($includePatient) {
            // FK a patients: paciente propio, vigente y activo
            $rules['patient_id'] = [
                'required',
                'integer',
                Rule::exists('patients', 'id')
                    ->where('user_id', Auth::id())
                    ->where('active', true)
                    ->whereNull('deleted_at'),
            ];
        }

        return $rules;
    }

    private function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser texto.',
            'integer' => 'El campo :attribute no es válido.',
            'numeric' => 'El campo :attribute debe ser un número.',
            'decimal' => 'El campo :attribute admite como máximo 2 decimales.',
            'date' => 'El campo :attribute no es una fecha válida.',
            'name.max' => 'El nombre del plan admite como máximo 255 caracteres.',
            'goal.max' => 'El objetivo admite como máximo 255 caracteres.',
            'instructions.max' => 'Las indicaciones admiten como máximo 5000 caracteres.',
            'starts_at.after_or_equal' => 'Revisa la fecha de inicio.',
            'starts_at.before_or_equal' => 'Revisa la fecha de inicio.',
            'min' => 'El campo :attribute no puede ser negativo.',
            'max' => 'El campo :attribute no puede ser mayor a :max.',
            'target_kcal.gt' => 'Las calorías objetivo deben ser mayores a 0.',
            'ends_at.after_or_equal' => 'La fecha de fin no puede ser anterior a la de inicio.',
            'patient_id.exists' => 'Selecciona un paciente activo de tu consultorio.',
        ];
    }

    private function attributes(): array
    {
        return [
            'patient_id' => 'paciente',
            'name' => 'nombre del plan',
            'goal' => 'objetivo',
            'instructions' => 'indicaciones',
            'starts_at' => 'fecha de inicio',
            'ends_at' => 'fecha de fin',
            'target_kcal' => 'calorías',
            'target_protein_g' => 'proteínas',
            'target_carbs_g' => 'carbohidratos',
            'target_lipids_g' => 'grasas',
        ];
    }
}

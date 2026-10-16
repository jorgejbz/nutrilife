<?php

namespace App\Http\Controllers;

use App\Models\DietPlan;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Food;
use App\Models\DietPlanDish;

class DietPlanController extends Controller
{
    public function index()
    {
        $plans = DietPlan::where('user_id', Auth::id())
            ->with('patient')
            ->orderByDesc('created_at')
            ->get();

        $activePlans = $plans
            ->where('active', true)
            ->count();

        return view('diet-plans.index', compact(
            'plans',
            'activePlans'
        ));
    }


    public function show(DietPlan $plan) // o public function show($id)
{
    // Cargar el plan con sus relaciones
    $plan->load('meals.items.food');

    // Cargar catálogo de alimentos
    $foods = Food::orderBy('name')->get();

    // ⚠️ AQUÍ FALTABA ESTA LÍNEA: Obtener los platillos de la BD
    $dishes = DietPlanDish::all();

    // Asegúrate de enviar 'dishes' en el compact
    return view('diet-plans.show', compact('plan', 'foods', 'dishes'));
}
    public function create()
    {
        $patients = Patient::where('user_id', Auth::id())
            ->where('active', true)
            ->orderBy('first_name')
            ->get();

        return view('diet-plans.create', compact('patients'));
    }


   

public function destroy($id)
{
    $plan = DietPlan::where('user_id', Auth::id())
        ->findOrFail($id);

    try {
        $plan->forceDelete();

        return redirect()
            ->route('diet-plans.index')
            ->with('success', 'El plan alimenticio se eliminó definitivamente.');
    } catch (\Illuminate\Database\QueryException $e) {
        report($e);

        return redirect()
            ->route('diet-plans.index')
            ->with('error', 'No se pudo eliminar el plan porque puede tener registros relacionados.');
    }
}



    public function store(Request $request)
{
    $validated = $request->validate([
        'patient_id' => [
            'required',
            'exists:patients,id',
        ],

        'name' => [
            'required',
            'string',
            'max:255',
        ],

        'target_kcal' => [
            'required',
            'numeric',
            'min:0',
        ],

        'target_protein_g' => [
            'required',
            'numeric',
            'min:0',
        ],

        'target_lipids_g' => [
            'required',
            'numeric',
            'min:0',
        ],

        'target_carbs_g' => [
            'required',
            'numeric',
            'min:0',
        ],

        'starts_at' => [
            'required',
            'date',
        ],

        'ends_at' => [
            'nullable',
            'date',
            'after_or_equal:starts_at',
        ],
    ]);


    $patient = Patient::where(
        'id',
        $validated['patient_id']
    )
    ->where(
        'user_id',
        Auth::id()
    )
    ->firstOrFail();


    DietPlan::create([

        'patient_id' => $patient->id,

        'user_id' => Auth::id(),

        'name' => $validated['name'],

        'target_kcal' =>
            $validated['target_kcal'],

        'target_protein_g' =>
            $validated['target_protein_g'],

        'target_lipids_g' =>
            $validated['target_lipids_g'],

        'target_carbs_g' =>
            $validated['target_carbs_g'],

        'starts_at' =>
            $validated['starts_at'],

        'ends_at' =>
            $validated['ends_at'] ?? null,

        'active' => true,

    ]);


    return redirect()
        ->route('diet-plans.index')
        ->with(
            'success',
            'Plan alimenticio creado correctamente.'
        );
}
}
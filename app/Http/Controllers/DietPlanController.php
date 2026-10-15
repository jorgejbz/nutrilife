<?php

namespace App\Http\Controllers;

use App\Models\DietPlan;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Food;

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


    public function show(DietPlan $plan)
{
    abort_if($plan->user_id !== Auth::id(), 403);

    $plan->load([
        'patient',
        'meals.items.food',
    ]);

    $foods = Food::where('active', true)
        ->orderBy('name')
        ->get();

    return view(
        'diet-plans.show',
        compact(
            'plan',
            'foods'
        )
    );
}
    public function create()
    {
        $patients = Patient::where('user_id', Auth::id())
            ->where('active', true)
            ->orderBy('first_name')
            ->get();

        return view('diet-plans.create', compact('patients'));
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
<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

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
        return view('patients.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'sex' => ['nullable', 'in:Masculino,Femenino,Otro'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => $this->emailRules(),
            'occupation' => ['nullable', 'string', 'max:150'],
            'goal' => ['nullable', 'string', 'max:1000'],
        ], $this->messages());

        $validated['user_id'] = Auth::id();
        $validated['active'] = true;

        Patient::create($validated);

        return redirect()
            ->route('pacientes.index')
            ->with('success', 'Paciente registrado correctamente.');
    }

 public function show(Patient $paciente)
{
    abort_if($paciente->user_id !== Auth::id(), 403);

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
    'fatChange'
));
}
    public function edit(Patient $paciente)
    {
        abort_if($paciente->user_id !== Auth::id(), 403);

        return view('patients.edit', compact('paciente'));
    }


    public function update(Request $request, Patient $paciente)
    {
        abort_if($paciente->user_id !== Auth::id(), 403);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'sex' => ['nullable', 'in:Masculino,Femenino,Otro'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => $this->emailRules($paciente),
            'occupation' => ['nullable', 'string', 'max:150'],
            'goal' => ['nullable', 'string', 'max:1000'],
            'active' => ['required', 'boolean'],
        ], $this->messages());

        $paciente->update($validated);

        return redirect()
            ->route('pacientes.show', $paciente)
            ->with('success', 'Paciente actualizado correctamente.');
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
    private function emailRules(?Patient $patient = null): array
    {
        $unique = Rule::unique('patients', 'email')
            ->where('user_id', Auth::id());

        if ($patient) {
            $unique->ignore($patient->id);
        }

        return ['required', 'email', 'max:255', $unique];
    }

    private function messages(): array
    {
        return [
            'email.required' => 'El correo del paciente es obligatorio: es su acceso a la app móvil.',
            'email.unique' => 'Ya tienes un paciente (activo o eliminado) registrado con este correo.',
        ];
    }
}

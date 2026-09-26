<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Evaluation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EvaluationController extends Controller
{
    public function create(Patient $paciente)
    {
        abort_if($paciente->user_id !== Auth::id(), 403);

        return view('evaluations.create', compact('paciente'));
    }

    public function store(Request $request, Patient $paciente)
{
    abort_if($paciente->user_id !== Auth::id(), 403);

    $validated = $request->validate([
        'evaluation_date' => ['required', 'date'],

        'weight' => [
            'required',
            'numeric',
            'min:20',
            'max:400'
        ],

        'height' => [
            'required',
            'numeric',
            'min:50',
            'max:250'
        ],

        'body_fat' => [
            'nullable',
            'numeric',
            'min:0',
            'max:100'
        ],

        'muscle_mass' => [
            'nullable',
            'numeric',
            'min:0',
            'max:300'
        ],

        'waist' => [
            'nullable',
            'numeric',
            'min:20',
            'max:300'
        ],

        'hip' => [
            'nullable',
            'numeric',
            'min:20',
            'max:300'
        ],

        'chest' => [
            'nullable',
            'numeric',
            'min:20',
            'max:300'
        ],

        'arm' => [
            'nullable',
            'numeric',
            'min:10',
            'max:100'
        ],

        'thigh' => [
            'nullable',
            'numeric',
            'min:10',
            'max:150'
        ],

        'notes' => [
            'nullable',
            'string',
            'max:2000'
        ],
    ]);

    $validated['patient_id'] = $paciente->id;

    // Calcular IMC automáticamente
    $heightMeters = $validated['height'] / 100;

    $validated['bmi'] = round(
        $validated['weight'] / ($heightMeters * $heightMeters),
        2
    );

    Evaluation::create($validated);

    return redirect()
        ->route('pacientes.show', $paciente)
        ->with('success', 'Evaluación registrada correctamente.');
}
}
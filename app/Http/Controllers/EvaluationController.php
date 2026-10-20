<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Evaluation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class EvaluationController extends Controller
{
    /** Límite clínico razonable y dentro de evaluations.bmi DECIMAL(5,2). */
    private const MAX_BMI = 150;

    public function create(Patient $paciente)
    {
        abort_if($paciente->user_id !== Auth::id(), 403);

        return view('evaluations.create', compact('paciente'));
    }

    public function store(Request $request, Patient $paciente)
{
    abort_if($paciente->user_id !== Auth::id(), 403);

    $validated = $request->validate([
        // No se registran evaluaciones con fecha futura.
        'evaluation_date' => ['required', 'date', 'after_or_equal:2000-01-01', 'before_or_equal:today'],
        // Todas las medidas son DECIMAL(x,2) en la BD.

        'weight' => [
            'required',
            'numeric',
            'decimal:0,2',
            'min:20',
            'max:400'
        ],

        'height' => [
            'required',
            'numeric',
            'decimal:0,2',
            'min:50',
            'max:250'
        ],

        'body_fat' => [
            'nullable',
            'numeric',
            'decimal:0,2',
            'min:0',
            'max:100'
        ],

        'muscle_mass' => [
            'nullable',
            'numeric',
            'decimal:0,2',
            'min:0',
            'max:300'
        ],

        'waist' => [
            'nullable',
            'numeric',
            'decimal:0,2',
            'min:20',
            'max:300'
        ],

        'hip' => [
            'nullable',
            'numeric',
            'decimal:0,2',
            'min:20',
            'max:300'
        ],

        'chest' => [
            'nullable',
            'numeric',
            'decimal:0,2',
            'min:20',
            'max:300'
        ],

        'arm' => [
            'nullable',
            'numeric',
            'decimal:0,2',
            'min:10',
            'max:100'
        ],

        'thigh' => [
            'nullable',
            'numeric',
            'decimal:0,2',
            'min:10',
            'max:150'
        ],

        'notes' => [
            'nullable',
            'string',
            'max:2000'
        ],
    ], [
        'evaluation_date.required' => 'Indica la fecha de la evaluación.',
        'evaluation_date.date' => 'Escribe una fecha válida.',
        'evaluation_date.before_or_equal' => 'La fecha de la evaluación no puede ser futura.',
        'evaluation_date.after_or_equal' => 'Revisa la fecha de la evaluación.',
    ]);

    $validated['patient_id'] = $paciente->id;

    // Calcular IMC automáticamente
    $heightMeters = $validated['height'] / 100;

    $validated['bmi'] = round(
        $validated['weight'] / ($heightMeters * $heightMeters),
        2
    );

    // evaluations.bmi es DECIMAL(5,2) (máx. 999.99). Con los rangos
    // permitidos (p. ej. 400 kg y 50 cm) el cálculo podía llegar a 1600 y
    // MySQL rechazaba el INSERT. Un IMC así indica un error de captura.
    if ($validated['bmi'] > self::MAX_BMI) {
        throw ValidationException::withMessages([
            'height' => 'La combinación de peso y estatura da un IMC fuera de rango. Revisa ambos valores.',
        ]);
    }

    Evaluation::create($validated);

    return redirect()
        ->route('pacientes.show', $paciente)
        ->with('success', 'Evaluación registrada correctamente.');
}
}
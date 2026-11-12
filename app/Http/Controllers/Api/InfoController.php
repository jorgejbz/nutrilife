<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Expediente del paciente (solo lectura) para la app móvil. */
class InfoController extends Controller
{
    // GET /api/consultations
    public function consultations(Request $request): JsonResponse
    {
        return response()->json(
            $request->user()->consultations()
                ->orderByDesc('consultation_at')
                ->get(['id', 'consultation_at', 'reason', 'observations', 'recommendations', 'next_visit'])
        );
    }

    // GET /api/evaluations (de la más vieja a la más nueva, listo para graficar)
    public function evaluations(Request $request): JsonResponse
    {
        return response()->json(
            $request->user()->evaluations()
                ->orderBy('evaluation_date')
                ->get(['id', 'evaluation_date', 'weight', 'height', 'bmi', 'body_fat',
                    'muscle_mass', 'waist', 'hip', 'chest', 'arm', 'thigh', 'notes'])
        );
    }

    // GET /api/allergies
    public function allergies(Request $request): JsonResponse
    {
        return response()->json(
            $request->user()->allergens->map(fn ($a) => [
                'id' => $a->id,
                'alergia' => $a->name,
                'severidad' => $a->pivot->severity,
            ])->values()
        );
    }

    // GET /api/pathologies
    public function pathologies(Request $request): JsonResponse
    {
        return response()->json(
            $request->user()->pathologies->map(fn ($p) => [
                'id' => $p->id,
                'patologia' => $p->name,
                'descripcion' => $p->description,
                'diagnosticada' => $p->pivot->diagnosed_at,
                'notas' => $p->pivot->notes,
            ])->values()
        );
    }
}

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
        // Fechas en hora local y sin zona ('Y-m-d H:i:s'): la app las lee con LocalDateTime
        return response()->json(
            $request->user()->consultations()
                ->orderByDesc('consultation_at')
                ->get(['id', 'consultation_at', 'reason', 'observations', 'recommendations', 'next_visit'])
                ->map(fn ($c) => [
                    'id' => $c->id,
                    'consultation_at' => $c->consultation_at?->format('Y-m-d H:i:s'),
                    'reason' => $c->reason,
                    'observations' => $c->observations,
                    'recommendations' => $c->recommendations,
                    'next_visit' => $c->next_visit?->format('Y-m-d'),
                ])
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
                ->map(fn ($e) => array_merge($e->toArray(), [
                    'evaluation_date' => $e->evaluation_date?->format('Y-m-d'),
                ]))
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

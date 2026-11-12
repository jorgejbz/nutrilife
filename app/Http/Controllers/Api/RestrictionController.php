<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Food;
use App\Models\FoodPathologyRestriction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RestrictionController extends Controller
{
    // GET /api/restrictions -> prohibidos, limitados (por patología) y alimentos por alergia
    public function index(Request $request): JsonResponse
    {
        $patient = $request->user();

        $reglas = FoodPathologyRestriction::with(['pathology', 'food', 'foodGroup'])
            ->whereIn('pathology_id', $patient->pathologies()->pluck('pathologies.id'))
            ->get()
            ->map(fn ($r) => [
                'patologia' => $r->pathology?->name,
                'tipo' => $r->restriction_type,
                'aplica_a' => $r->food_id ? 'alimento' : 'grupo',
                'nombre' => $r->food?->name ?? $r->foodGroup?->name,
                'max_porciones_dia' => $r->max_portions_per_day,
                'notas' => $r->notes,
            ]);

        $alergiasIds = $patient->allergens()->pluck('allergens.id')->all();

        $porAlergia = $alergiasIds === []
            ? collect()
            : Food::whereHas('allergens', fn ($q) => $q->whereIn('allergens.id', $alergiasIds))
                ->with('allergens')
                ->orderBy('name')
                ->get()
                ->map(fn ($f) => [
                    'alimento' => $f->name,
                    'alergenos' => $f->allergens->whereIn('id', $alergiasIds)->pluck('name')->values(),
                ])
                ->values();

        return response()->json([
            'prohibidos' => $reglas->where('tipo', 'prohibido')->values(),
            'limitados' => $reglas->where('tipo', 'limitado')->values(),
            'por_alergia' => $porAlergia,
        ]);
    }
}

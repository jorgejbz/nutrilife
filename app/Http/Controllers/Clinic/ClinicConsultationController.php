<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Consultas de todo el consultorio (/clinica/consultas), solo lectura.
 * Junta las consultas de la cuenta principal y de sus subperfiles con su
 * nutriólogo responsable (consultations.user_id). Cada nutriólogo sigue
 * registrando sus consultas desde /consultas; aquí no se duplica ese flujo.
 * Filtros: nutriólogo, paciente y rango de fechas (el indicador
 * "Consultas este mes" del panel llega con ?desde=<inicio del mes>).
 */
class ClinicConsultationController extends Controller
{
    public function index(Request $request)
    {
        $clinic = $request->user();
        $ids = $clinic->clinicNutritionistIds();
        $nutritionists = User::whereIn('id', $ids)->orderBy('name')->get(['id', 'name', 'clinic_id']);

        $nutritionistId = in_array($request->integer('nutriologo'), $ids, true) ? $request->integer('nutriologo') : null;
        $search = trim((string) $request->query('paciente'));
        $from = $this->date($request->query('desde'));
        $to = $this->date($request->query('hasta'));

        if ($from && $to && $to->lt($from)) {
            [$from, $to] = [$to, $from];
        }

        $consultations = Consultation::whereIn('user_id', $nutritionistId ? [$nutritionistId] : $ids)
            ->with(['patient', 'user:id,name,clinic_id'])
            ->when($from, fn ($query) => $query->where('consultation_at', '>=', $from->copy()->startOfDay()))
            ->when($to, fn ($query) => $query->where('consultation_at', '<=', $to->copy()->endOfDay()))
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('patient', function ($patient) use ($search) {
                    foreach (preg_split('/\s+/', $search) as $word) {
                        $patient->where(fn ($q) => $q
                            ->where('first_name', 'like', '%'.$word.'%')
                            ->orWhere('last_name', 'like', '%'.$word.'%'));
                    }
                });
            })
            ->orderByDesc('consultation_at')
            ->paginate(25)
            ->withQueryString();

        return view('clinic.consultations', [
            'clinic' => $clinic,
            'consultations' => $consultations,
            'nutritionists' => $nutritionists,
            'filters' => [
                'nutriologo' => $nutritionistId,
                'paciente' => $search,
                'desde' => $from?->toDateString(),
                'hasta' => $to?->toDateString(),
            ],
            'hasFilters' => $nutritionistId || $search !== '' || $from || $to,
        ]);
    }

    private function date(mixed $value): ?Carbon
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}

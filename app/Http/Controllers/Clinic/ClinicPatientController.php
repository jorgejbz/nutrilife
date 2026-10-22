<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Vista general de pacientes del consultorio (/clinica/pacientes), solo
 * lectura. Junta los expedientes de la cuenta principal y de sus
 * subperfiles; cada fila muestra su nutriólogo responsable
 * (patients.user_id). No se duplican pacientes: el expediente sigue siendo
 * del nutriólogo que lo registró y solo él lo edita desde /pacientes.
 */
class ClinicPatientController extends Controller
{
    public function index(Request $request)
    {
        $clinic = $request->user();
        $ids = $clinic->clinicNutritionistIds();
        $nutritionists = User::whereIn('id', $ids)->orderBy('name')->get(['id', 'name', 'clinic_id']);

        $nutritionistId = in_array($request->integer('nutriologo'), $ids, true) ? $request->integer('nutriologo') : null;
        $status = in_array($request->query('estado'), ['activo', 'inactivo'], true) ? $request->query('estado') : null;
        $search = trim((string) $request->query('q'));

        $patients = Patient::whereIn('user_id', $nutritionistId ? [$nutritionistId] : $ids)
            ->with('user:id,name,clinic_id')
            ->withCount([
                'appointments',
                'dietPlans as active_diet_plans_count' => fn ($query) => $query->where('active', true),
            ])
            ->withMin(['appointments as next_appointment_at' => fn ($query) => $query
                ->where('appointment_at', '>=', now())
                ->whereIn('status', ['Pendiente', 'Confirmada'])], 'appointment_at')
            ->when($status, fn ($query) => $query->where('active', $status === 'activo'))
            ->when($search !== '', function ($query) use ($search) {
                foreach (preg_split('/\s+/', $search) as $word) {
                    $query->where(fn ($q) => $q
                        ->where('first_name', 'like', '%'.$word.'%')
                        ->orWhere('last_name', 'like', '%'.$word.'%')
                        ->orWhere('email', 'like', '%'.$word.'%'));
                }
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->paginate(20)
            ->withQueryString();

        // Pacientes por nutriólogo (accesos rápidos del filtro).
        $patientCounts = Patient::whereIn('user_id', $ids)
            ->selectRaw('user_id, count(*) as total')
            ->groupBy('user_id')
            ->pluck('total', 'user_id')
            ->map(fn ($total) => (int) $total);

        return view('clinic.patients', [
            'patientCounts' => $patientCounts,
            'clinic' => $clinic,
            'patients' => $patients,
            'nutritionists' => $nutritionists,
            'filters' => ['nutriologo' => $nutritionistId, 'estado' => $status, 'q' => $search],
        ]);
    }
}

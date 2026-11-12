<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsurePatientAccess;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Inicio de sesión de la app móvil: el paciente entra con el código de
 * acceso de 6 dígitos de su nutriólogo + su correo, y recibe un token
 * Bearer de Sanctum.
 */
class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'access_code' => 'required|digits:6',
            'email' => 'required|email|max:255',
        ]);

        // Mismo mensaje para código o correo incorrecto (no revela cuál falló)
        $error = response()->json(['message' => 'Código o correo incorrecto'], 401);

        $nutriologo = User::where('access_code', $data['access_code'])
            ->where('role_id', Role::NUTRITIONIST)
            ->first();

        if (! $nutriologo) {
            return $error;
        }

        // SoftDeletes ya excluye a los pacientes eliminados
        $patient = Patient::where('user_id', $nutriologo->id)
            ->where('email', $data['email'])
            ->where('active', true)
            ->first();

        if (! $patient) {
            return $error;
        }

        if ($reason = EnsurePatientAccess::denyReason($patient)) {
            return response()->json(['message' => $reason], 403);
        }

        $token = $patient->createToken('app-movil')->plainTextToken;

        return response()->json([
            'token' => $token,
            'patient' => $this->patientData($patient),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($this->patientData($request->user()));
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada']);
    }

    private function patientData(Patient $patient): array
    {
        $nutri = $patient->user;

        return [
            'id' => $patient->id,
            'first_name' => $patient->first_name,
            'last_name' => $patient->last_name,
            'email' => $patient->email,
            'phone' => $patient->phone,
            'sex' => $patient->sex,
            'birth_date' => $patient->birth_date?->format('Y-m-d'),
            'age' => $patient->birth_date?->age,
            'occupation' => $patient->occupation,
            'goal' => $patient->goal,
            'nutriologo' => $nutri?->name,
            'nutriologo_license' => $nutri?->professional_license,
            // Si el nutriólogo es subperfil de un consultorio, nombre del consultorio
            'clinic' => $nutri?->clinic?->name,
        ];
    }
}

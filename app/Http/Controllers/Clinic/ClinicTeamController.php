<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Services\AccountActivation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

/**
 * Equipo del consultorio: subperfiles de nutriólogo (/clinica/equipo).
 *
 * - Cada subperfil es un `users` propio (su correo y contraseña), con
 *   role_id = nutriólogo y clinic_id = la clínica. No tiene suscripción ni
 *   cobro propio: usa la licencia de la clínica (User::hasFeature).
 * - {member} se busca SOLO entre los subperfiles de la clínica
 *   autenticada: el de otra clínica responde 404.
 * - role_id y clinic_id se fijan en el servidor (forceFill); no salen de la
 *   petición. account_status solo cambia con updateStatus().
 * - No se eliminan subperfiles: se desactivan y su información (pacientes,
 *   citas, planes) se conserva.
 */
class ClinicTeamController extends Controller
{
    public function index(Request $request)
    {
        $clinic = $request->user();

        $members = $clinic->clinicMembers()
            ->withCount([
                'patients',
                'patients as active_patients_count' => fn ($query) => $query->where('active', true),
                'appointments as upcoming_appointments_count' => fn ($query) => $query
                    ->where('appointment_at', '>=', now())
                    ->whereIn('status', ['Pendiente', 'Confirmada']),
            ])
            ->orderBy('name')
            ->get();

        return view('clinic.team.index', [
            'clinic' => $clinic,
            'members' => $members,
            'memberLimit' => User::clinicMemberLimit(),
            'canAdd' => $clinic->canAddClinicMember(),
            'licenseActive' => $clinic->clinicLicenseActive(),
        ]);
    }

    public function create(Request $request)
    {
        $this->ensureCanAdd($request->user());

        return view('clinic.team.form', ['member' => null, 'clinic' => $request->user()]);
    }

    public function store(Request $request)
    {
        $clinic = $request->user();
        $this->ensureCanAdd($clinic);

        $this->normalize($request);
        $validated = $request->validate($this->rules(), $this->messages(), $this->attributes());

        $member = DB::transaction(function () use ($clinic, $validated) {
            $member = new User([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'professional_license' => $validated['professional_license'] ?: null,
                'degree_number' => $validated['degree_number'] ?: null,
            ]);

            // La clínica da de alta a su profesional: queda activo de inmediato
            // (no pasa por la espera simulada del registro público). El correo
            // NO se marca como verificado.
            $member->forceFill([
                'role_id' => Role::NUTRITIONIST,
                'clinic_id' => $clinic->id,
                'account_status' => User::ACCOUNT_ACTIVE,
                'activates_at' => null,
                'activated_at' => now(),
            ])->save();

            AuditLog::record($clinic, 'clinic.member_created', $member, ['email' => $member->email]);

            return $member;
        });

        return redirect()
            ->route('clinic.team.show', $member->id)
            ->with('success', 'Subperfil creado. '.$member->name.' ya puede iniciar sesión con su correo y la contraseña que definiste.');
    }

    public function show(Request $request, string $member)
    {
        $member = $this->member($request->user(), $member);

        $member->loadCount([
            'patients',
            'patients as active_patients_count' => fn ($query) => $query->where('active', true),
            'dietPlans as active_diet_plans_count' => fn ($query) => $query->where('active', true),
            'consultations as consultations_month_count' => fn ($query) => $query->where('consultation_at', '>=', now()->startOfMonth()),
        ]);

        $patients = $member->patients()
            ->withCount(['appointments', 'dietPlans as active_diet_plans_count' => fn ($query) => $query->where('active', true)])
            ->orderBy('first_name')
            ->get();

        $upcoming = $member->appointments()
            ->where('appointment_at', '>=', now())
            ->whereIn('status', ['Pendiente', 'Confirmada'])
            ->with('patient')
            ->orderBy('appointment_at')
            ->limit(10)
            ->get();

        $statusCounts = Appointment::where('user_id', $member->id)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('clinic.team.show', [
            'clinic' => $request->user(),
            'member' => $member,
            'patients' => $patients,
            'upcoming' => $upcoming,
            'statusCounts' => collect(AppointmentController::STATUSES)->mapWithKeys(fn ($status) => [$status => (int) ($statusCounts[$status] ?? 0)]),
            'history' => AuditLog::where('subject_type', 'User')->where('subject_id', $member->id)->with('user')->latest('id')->limit(10)->get(),
        ]);
    }

    public function edit(Request $request, string $member)
    {
        return view('clinic.team.form', [
            'member' => $this->member($request->user(), $member),
            'clinic' => $request->user(),
        ]);
    }

    public function update(Request $request, string $member)
    {
        $clinic = $request->user();
        $member = $this->member($clinic, $member);

        $this->normalize($request);
        $validated = $request->validate($this->rules($member), $this->messages(), $this->attributes());

        $member->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'professional_license' => $validated['professional_license'] ?: null,
            'degree_number' => $validated['degree_number'] ?: null,
        ]);

        if ($member->isDirty('email')) {
            $member->email_verified_at = null;
        }

        if (! empty($validated['password'])) {
            $member->password = Hash::make($validated['password']);
        }

        $changes = array_values(array_diff(array_keys($member->getDirty()), ['password', 'email_verified_at']));
        $passwordChanged = $member->isDirty('password');

        $member->save();

        if ($changes || $passwordChanged) {
            AuditLog::record($clinic, 'clinic.member_updated', $member, [
                'fields' => $changes,
                'password_changed' => $passwordChanged,
            ]);
        }

        return redirect()
            ->route('clinic.team.show', $member->id)
            ->with('success', 'Datos del subperfil actualizados.');
    }

    /** Activar / desactivar el acceso del subperfil. */
    public function updateStatus(Request $request, string $member)
    {
        $clinic = $request->user();
        $member = $this->member($clinic, $member);

        $validated = $request->validate([
            'action' => ['required', Rule::in(['activar', 'desactivar'])],
        ]);

        if ($validated['action'] === 'desactivar') {
            AccountActivation::deactivate($member);
            AuditLog::record($clinic, 'clinic.member_deactivated', $member);
            $message = $member->name.' ya no puede iniciar sesión. Sus pacientes y su historial se conservan.';
        } else {
            if (! $clinic->clinicLicenseActive()) {
                return back()->with('error', 'La licencia de la clínica no está vigente: renueva el plan para reactivar subperfiles.');
            }

            AccountActivation::reactivate($member);
            AuditLog::record($clinic, 'clinic.member_reactivated', $member);
            $message = $member->name.' puede volver a iniciar sesión.';
        }

        return back()->with('success', $message);
    }

    /** Subperfil de ESTA clínica (404 para cualquier otro id). */
    private function member(User $clinic, string $id): User
    {
        return $clinic->clinicMembers()->whereKey((int) $id)->firstOrFail();
    }

    private function ensureCanAdd(User $clinic): void
    {
        if ($clinic->canAddClinicMember()) {
            return;
        }

        $limit = User::clinicMemberLimit();

        abort(403, ! $clinic->clinicLicenseActive()
            ? 'La licencia Consultorio de la clínica no está vigente: renueva el plan para agregar nutriólogos.'
            : 'La clínica ya tiene el máximo de '.$limit.' subperfiles.');
    }

    private function normalize(Request $request): void
    {
        $request->merge([
            'name' => trim((string) $request->input('name')),
            'email' => mb_strtolower(trim((string) $request->input('email'))),
            'professional_license' => preg_replace('/\s+/', '', (string) $request->input('professional_license')),
            'degree_number' => mb_strtoupper(trim((string) $request->input('degree_number'))),
        ]);
    }

    /** Mismas reglas de formato que el registro público (RegisteredUserController). */
    private function rules(?User $member = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($member?->id)],
            'password' => [$member ? 'nullable' : 'required', 'confirmed', Rules\Password::defaults()],
            'professional_license' => ['nullable', 'regex:/^\d{7,8}$/', Rule::unique('users', 'professional_license')->ignore($member?->id)],
            'degree_number' => ['nullable', 'string', 'min:3', 'max:50', 'regex:/^[A-Z0-9][A-Z0-9\-\/ ]*$/'],
        ];
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Escribe el nombre completo del nutriólogo.',
            'email.required' => 'Escribe el correo con el que iniciará sesión.',
            'email.email' => 'Escribe un correo válido, por ejemplo nombre@dominio.com.',
            'email.unique' => 'Ya existe una cuenta de NutriLife con este correo.',
            'password.required' => 'Define una contraseña para el subperfil.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos :min caracteres.',
            'professional_license.regex' => 'La cédula profesional debe tener 7 u 8 dígitos, sin letras ni guiones.',
            'professional_license.unique' => 'Ya existe una cuenta registrada con esta cédula profesional.',
            'degree_number.min' => 'El número de título debe tener al menos 3 caracteres.',
            'degree_number.regex' => 'El número de título solo admite letras, números, guiones, diagonales y espacios.',
        ];
    }

    private function attributes(): array
    {
        return [
            'name' => 'nombre',
            'email' => 'correo electrónico',
            'password' => 'contraseña',
            'professional_license' => 'cédula profesional',
            'degree_number' => 'número de título',
        ];
    }
}

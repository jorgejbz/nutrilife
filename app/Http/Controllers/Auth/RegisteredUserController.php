<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Services\AccountActivation;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /** Pantalla "¡Gracias por registrarte!" después de crear la cuenta. */
    public function thanks(): View
    {
        return view('auth.register-thanks', [
            'name' => session('registered_name'),
            'email' => session('registered_email'),
            'delayMinutes' => AccountActivation::delayMinutes(),
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * Nueva BD:
     *  - users.role_id → siempre nutriólogo en el registro público.
     *  - users.access_code → lo genera el modelo User (6 dígitos únicos).
     *  - users.professional_license / degree_number: cédula y título
     *    (solo formato; no se verifican contra la SEP).
     *  - La cuenta queda 'pendiente' (AccountActivation) y la prueba gratuita
     *    de `subscriptions` se crea cuando se activa.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // users.email es UNIQUE y la regla 'lowercase' rechazaba correos con
        // mayúsculas; se normaliza antes de validar.
        $request->merge([
            'name' => trim((string) $request->input('name')),
            'email' => mb_strtolower(trim((string) $request->input('email'))),
            // Cédula: solo dígitos (se quitan espacios). Título: mayúsculas.
            'professional_license' => preg_replace('/\s+/', '', (string) $request->input('professional_license')),
            'degree_number' => mb_strtoupper(trim((string) $request->input('degree_number'))),
        ]);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            // Cédula profesional (SEP): 7 u 8 dígitos. Única por cuenta.
            // Solo se valida el formato: NO se consulta el Registro Nacional
            // de Profesionistas (la verificación real queda pendiente).
            'professional_license' => ['required', 'regex:/^\d{7,8}$/', 'unique:'.User::class.',professional_license'],
            'degree_number' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[A-Z0-9][A-Z0-9\-\/ ]*$/'],
        ], [
            // La app usa APP_LOCALE=en; estos mensajes evitan que el registro
            // muestre errores en inglés (mismas reglas, solo el texto).
            'name.required' => 'Escribe tu nombre completo.',
            'name.max' => 'El nombre no puede superar 255 caracteres.',
            'email.required' => 'Escribe tu correo electrónico.',
            'email.email' => 'Escribe un correo válido, por ejemplo nombre@dominio.com.',
            'email.max' => 'El correo no puede superar 255 caracteres.',
            'email.unique' => 'Ya existe una cuenta con este correo. Inicia sesión o usa otro correo.',
            'email.lowercase' => 'Escribe el correo en minúsculas.',
            'password.required' => 'Escribe una contraseña.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos :min caracteres.',
            'professional_license.required' => 'Escribe tu número de cédula profesional.',
            'professional_license.regex' => 'La cédula profesional debe tener 7 u 8 dígitos, sin letras ni guiones.',
            'professional_license.unique' => 'Ya existe una cuenta registrada con esta cédula profesional.',
            'degree_number.required' => 'Escribe el número de tu título profesional.',
            'degree_number.min' => 'El número de título debe tener al menos 3 caracteres.',
            'degree_number.max' => 'El número de título no puede superar 50 caracteres.',
            'degree_number.regex' => 'El número de título solo admite letras, números, guiones, diagonales y espacios.',
        ], [
            'name' => 'nombre',
            'email' => 'correo electrónico',
            'password' => 'contraseña',
            'professional_license' => 'cédula profesional',
            'degree_number' => 'número de título',
        ]);

        $user = DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role_id' => Role::NUTRITIONIST,
                'professional_license' => $request->professional_license,
                'degree_number' => $request->degree_number,
            ]);

            // La cuenta queda pendiente; se habilita sola cuando pasa el
            // tiempo configurado (AccountActivation). La prueba gratuita
            // empieza al activarse, no al registrarse.
            AccountActivation::schedule($user);

            return $user;
        });

        event(new Registered($user));

        // Ya no se inicia sesión ni se entra al dashboard: pantalla de gracias.
        return redirect()
            ->route('register.thanks')
            ->with('registered_name', $user->name)
            ->with('registered_email', $user->email);
    }
}

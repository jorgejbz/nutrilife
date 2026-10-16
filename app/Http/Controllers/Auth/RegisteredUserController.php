<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    /**
     * Handle an incoming registration request.
     *
     * Nueva BD:
     *  - users.role_id → siempre nutriólogo en el registro público.
     *  - users.access_code → lo genera el modelo User (6 dígitos únicos).
     *  - La prueba gratuita ya no vive en users: se crea un registro en
     *    `subscriptions` con status "en_prueba" (ver Subscription::startTrialFor).
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
        ]);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
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
        ], [
            'name' => 'nombre',
            'email' => 'correo electrónico',
            'password' => 'contraseña',
        ]);

        $user = DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role_id' => Role::NUTRITIONIST,
            ]);

            Subscription::startTrialFor($user);

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard.index', absolute: false));
    }
}

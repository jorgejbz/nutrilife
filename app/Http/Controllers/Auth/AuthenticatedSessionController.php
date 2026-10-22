<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AccountActivation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        // Cuenta registrada que aún no se habilita: se revisa el tiempo de
        // espera en el servidor. Si todavía no llega, no se deja la sesión
        // abierta y se muestra la pantalla de "cuenta en preparación".
        $user = $request->user();

        // Cuenta desactivada (administrador o clínica): no se abre la sesión.
        if ($user && $user->isDisabled()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withInput($request->only('email'))
                ->withErrors(['email' => AccountActivation::DISABLED_MESSAGE]);
        }

        if ($user && ! AccountActivation::refresh($user)) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('account.pending')
                ->with('pending_until', $user->activates_at?->toIso8601String());
        }

        $request->session()->regenerate();

        // El administrador global entra directo a su panel.
        if ($user?->isAdmin()) {
            return redirect()->intended(route('admin.dashboard', absolute: false));
        }

        return redirect()->intended(route('dashboard.index', absolute: false));
    }

    /**
     * Pantalla "Tu cuenta todavía no está habilitada" (pública).
     */
    public function pending(Request $request): View
    {
        $until = session('pending_until');

        return view('auth.account-pending', [
            'activatesAt' => $until ? \Illuminate\Support\Carbon::parse($until)->timezone(config('app.timezone')) : null,
        ]);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        // A la pantalla de inicio de sesión con aviso. Las páginas privadas
        // se envían con Cache-Control: no-store (middleware PreventBackHistory),
        // así que "Atrás" no vuelve a mostrar información protegida.
        return redirect()
            ->route('login')
            ->with('status', 'Cerraste sesión correctamente.');
    }
}

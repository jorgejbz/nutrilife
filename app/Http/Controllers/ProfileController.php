<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // Nueva BD: patients, appointments, consultations, payments y
        // diet_plans referencian a users SIN borrado en cascada. Para no
        // perder expedientes clínicos (y evitar un error de FK) la cuenta
        // solo se elimina si no tiene información asociada.
        $hasClinicalData = $user->patients()->withTrashed()->exists()
            || $user->appointments()->exists()
            || $user->consultations()->withTrashed()->exists()
            || $user->payments()->withTrashed()->exists()
            || $user->dietPlans()->withTrashed()->exists();

        if ($hasClinicalData) {
            return Redirect::route('profile.edit')->withErrors([
                'password' => 'No es posible eliminar la cuenta porque tiene pacientes, citas, consultas o pagos registrados.',
            ], 'userDeletion');
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

/**
 * Crea (o restablece la contraseña de) un administrador global.
 *
 * Es el ÚNICO camino para tener un administrador: no existe formulario
 * público, el registro siempre crea nutriólogos y role_id no es asignable
 * en masa. Requiere acceso a la terminal del servidor.
 *
 *   php artisan nutrilife:create-admin admin@nutrilife.test
 *   php artisan nutrilife:create-admin admin@nutrilife.test --name="Administración NutriLife"
 *   php artisan nutrilife:create-admin admin@nutrilife.test --reset-password
 *
 * Sin --password pide la contraseña en la terminal (sin mostrarla) o, si no
 * hay terminal interactiva, genera una segura y la muestra una sola vez.
 */
class CreateAdminCommand extends Command
{
    protected $signature = 'nutrilife:create-admin
        {email : Correo del administrador}
        {--name=Administrador NutriLife : Nombre para mostrar}
        {--password= : Contraseña (si se omite, se pide o se genera)}
        {--reset-password : Solo cambia la contraseña de un administrador existente}';

    protected $description = 'Crea el administrador global de NutriLife (o restablece su contraseña)';

    public function handle(): int
    {
        $email = mb_strtolower(trim((string) $this->argument('email')));
        $existing = User::where('email', $email)->first();

        if ($existing && ! $existing->isAdmin()) {
            $this->error("{$email} ya pertenece a una cuenta de nutriólogo; no se convierte en administrador. Usa otro correo.");

            return self::FAILURE;
        }

        if ($existing && ! $this->option('reset-password')) {
            $this->warn("{$email} ya es administrador. Usa --reset-password para cambiar su contraseña.");

            return self::SUCCESS;
        }

        [$password, $generated] = $this->resolvePassword();

        $validator = Validator::make(
            ['email' => $email, 'password' => $password],
            ['email' => ['required', 'email', 'max:255'], 'password' => ['required', Password::min(10)->letters()->mixedCase()->numbers()]]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        if ($existing) {
            $existing->forceFill(['password' => Hash::make($password)])->save();
            $this->info("Contraseña de {$email} actualizada.");
        } else {
            $admin = new User([
                'name' => (string) $this->option('name'),
                'email' => $email,
                'password' => Hash::make($password),
            ]);
            $admin->forceFill([
                'role_id' => Role::ADMIN,
                'clinic_id' => null,
                'account_status' => User::ACCOUNT_ACTIVE,
                'activated_at' => now(),
            ])->save();

            $this->info("Administrador {$email} creado. Entra en /login y llegarás a /admin.");
        }

        if ($generated) {
            $this->line('Contraseña generada (guárdala, no se vuelve a mostrar): '.$password);
        }

        return self::SUCCESS;
    }

    /** @return array{0: string, 1: bool} */
    private function resolvePassword(): array
    {
        if (filled($this->option('password'))) {
            return [(string) $this->option('password'), false];
        }

        if ($this->input->isInteractive()) {
            return [(string) $this->secret('Contraseña del administrador (mín. 10, mayúsculas, minúsculas y números)'), false];
        }

        return ['Nl-'.Str::password(14, symbols: false).'9a', true];
    }
}

<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * DATOS DE PRUEBA DE LA CLÍNICA consultorio@nutrilife.test (entorno local).
 *
 *   php artisan db:seed --class=ClinicDemoSeeder
 *
 * Crea dos subperfiles de nutriólogo para la clínica (si no existen) y les
 * carga pacientes, citas, consultas, pagos y planes con DemoAccountsSeeder
 * (los mismos datos de prueba que el resto de cuentas). Contraseña:
 * DemoDataSeeder::PASSWORD. No borra nada, no toca otras cuentas, se puede
 * repetir sin duplicar y se niega a correr en producción.
 */
class ClinicDemoSeeder extends Seeder
{
    public const CLINIC = 'consultorio@nutrilife.test';

    /** correo => [nombre, cédula, número de título] */
    public const MEMBERS = [
        'nutriologa.andrea@vidasana.test' => ['Lic. Andrea Solís Rivera', '10203041', 'LN-2018-0451'],
        'nutriologo.diego@vidasana.test' => ['Lic. Diego Navarro Peña', '10203042', 'LN-2020-0782'],
    ];

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->error('ClinicDemoSeeder no se ejecuta en producción.');

            return;
        }

        $clinic = User::where('email', self::CLINIC)->first();

        if (! $clinic) {
            $this->command?->warn(self::CLINIC.' no existe; se omite.');

            return;
        }

        $accounts = $this->container->make(DemoAccountsSeeder::class)
            ->setContainer($this->container)
            ->setCommand($this->command);

        foreach (self::MEMBERS as $email => [$name, $license, $degree]) {
            $member = User::where('email', $email)->first();

            if ($member && (int) $member->clinic_id !== (int) $clinic->id) {
                $this->command?->warn("{$email} ya existe y no pertenece a la clínica; se omite.");

                continue;
            }

            if (! $member) {
                $member = new User([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make(DemoDataSeeder::PASSWORD),
                    'professional_license' => User::where('professional_license', $license)->exists() ? null : $license,
                    'degree_number' => $degree,
                ]);
                $member->forceFill([
                    'role_id' => Role::NUTRITIONIST,
                    'clinic_id' => $clinic->id,
                    'account_status' => User::ACCOUNT_ACTIVE,
                    'activated_at' => now(),
                    'email_verified_at' => now(),
                ])->save();

                AuditLog::record($clinic, 'clinic.member_created', $member, ['email' => $email, 'source' => 'ClinicDemoSeeder']);
                $this->command?->info("{$email}: subperfil creado en ".$clinic->name.'.');
            }

            $accounts->seedAccount($email);
        }
    }
}

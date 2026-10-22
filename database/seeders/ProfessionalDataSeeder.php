<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * CÉDULA Y TÍTULO DE PRUEBA para cuentas de nutriólogo que no los tienen
 * (entorno local).
 *
 *   php artisan db:seed --class=ProfessionalDataSeeder
 *
 * - Solo llena users.professional_license / degree_number cuando están
 *   vacíos; nunca sobrescribe datos capturados.
 * - Cédula: 7 dígitos del rango 9000000+ (formato SEP válido, única en la
 *   tabla). Título: LN-PRUEBA-<id>, para que se note que es un dato de prueba.
 * - NO marca la verificación profesional (professional_verified_at sigue
 *   vacío): son datos ficticios, no cédulas reales.
 * - No toca administradores. Se niega a correr en producción.
 */
class ProfessionalDataSeeder extends Seeder
{
    public const LICENSE_BASE = 9000000;

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->error('ProfessionalDataSeeder no se ejecuta en producción.');

            return;
        }

        $users = User::where('role_id', Role::NUTRITIONIST)
            ->where(fn ($query) => $query
                ->whereNull('professional_license')->orWhere('professional_license', '')
                ->orWhereNull('degree_number')->orWhere('degree_number', ''))
            ->orderBy('id')
            ->get();

        foreach ($users as $user) {
            $changes = [];

            if (blank($user->professional_license)) {
                $license = self::LICENSE_BASE + $user->id;

                while (User::where('professional_license', (string) $license)->exists()) {
                    $license++;
                }

                $changes['professional_license'] = (string) $license;
            }

            if (blank($user->degree_number)) {
                $changes['degree_number'] = 'LN-PRUEBA-'.str_pad((string) $user->id, 4, '0', STR_PAD_LEFT);
            }

            $user->forceFill($changes)->save();

            $this->command?->info($user->email.': '.collect($changes)->map(fn ($v, $k) => ($k === 'professional_license' ? 'cédula ' : 'título ').$v)->join(', '));
        }

        if ($users->isEmpty()) {
            $this->command?->info('Todas las cuentas de nutriólogo ya tienen cédula y título.');
        }
    }
}

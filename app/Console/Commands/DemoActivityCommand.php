<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\DemoPatients;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Actividad de demostración para las cuentas pro@ y esencial@ (también en
 * producción, para presentar el sistema con datos del mes actual):
 *  - Pacientes nuevos este mes (con primera consulta, evaluación, pago y,
 *    en Pro, plan alimenticio) y pacientes nuevos con primera cita en las
 *    próximas semanas.
 *  - Completa las citas de demostración que ya pasaron (consulta,
 *    evaluación y pago) y agenda las siguientes cada 14 días hasta el fin
 *    del mes siguiente.
 *
 * Solo toca pacientes de demostración (correo .test); es repetible y no
 * duplica nada. Uso:
 *   php artisan nutrilife:demo-activity
 *   php artisan nutrilife:demo-activity --hasta=2026-12-31
 */
class DemoActivityCommand extends Command
{
    protected $signature = 'nutrilife:demo-activity
        {--hasta= : Último día para agendar citas (AAAA-MM-DD); por defecto, fin del mes siguiente}
        {--force : No pedir confirmación en producción}';

    protected $description = 'Agrega pacientes nuevos, citas, consultas y pagos de demostración a las cuentas pro@ y esencial@';

    /** Cuenta => [pacientes nuevos, ¿con planes alimenticios?] */
    private const ACCOUNTS = [
        'pro@nutrilife.test' => ['newPro', true],
        'esencial@nutrilife.test' => ['newEsencial', false],
    ];

    public function handle(DemoDataSeeder $seeder): int
    {
        $until = $this->option('hasta')
            ? Carbon::createFromFormat('Y-m-d', (string) $this->option('hasta'))
            : now()->addMonthNoOverflow()->endOfMonth();

        if (app()->environment('production') && ! $this->option('force')
            && ! $this->confirm('Esto agrega datos de demostración en PRODUCCIÓN a pro@ y esencial@. ¿Continuar?')) {
            return self::FAILURE;
        }

        $this->info('Agendando hasta el '.$until->format('d/m/Y').'.');

        foreach (self::ACCOUNTS as $email => [$patientsMethod, $withPlans]) {
            $user = User::where('email', $email)->first();

            if (! $user) {
                $this->warn("No existe la cuenta {$email}: se omite.");

                continue;
            }

            $before = $this->counts($user);

            $seeder->seedPatientsFor($user, DemoPatients::$patientsMethod(), $withPlans);
            $agenda = $seeder->extendAgendaFor($user, $until);

            $after = $this->counts($user);

            $this->newLine();
            $this->line("<options=bold>{$email}</> ({$user->name})");
            $this->table(['', 'Antes', 'Después', 'Agregados'], collect($after)->map(fn ($v, $k) => [
                $k, $before[$k], $v, $v - $before[$k],
            ])->values()->all());
            $this->line("Citas completadas: {$agenda['completed']} · nuevas: {$agenda['scheduled']} · canceladas: {$agenda['cancelled']}");
        }

        return self::SUCCESS;
    }

    private function counts(User $user): array
    {
        $month = [now()->startOfMonth(), now()->endOfMonth()];
        $next = [now()->addMonthNoOverflow()->startOfMonth(), now()->addMonthNoOverflow()->endOfMonth()];

        return [
            'Pacientes' => Patient::where('user_id', $user->id)->count(),
            'Pacientes nuevos este mes' => Patient::where('user_id', $user->id)->whereBetween('created_at', $month)->count(),
            'Citas este mes' => Appointment::where('user_id', $user->id)->whereBetween('appointment_at', $month)->count(),
            'Citas mes siguiente' => Appointment::where('user_id', $user->id)->whereBetween('appointment_at', $next)->count(),
            'Consultas' => Consultation::where('user_id', $user->id)->count(),
            'Pagos' => Payment::where('user_id', $user->id)->count(),
        ];
    }
}

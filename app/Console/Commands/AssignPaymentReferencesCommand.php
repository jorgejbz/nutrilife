<?php

namespace App\Console\Commands;

use App\Services\PaymentReferenceService;
use Illuminate\Console\Command;

/**
 * Asigna la referencia interna a los pagos que todavía no tienen
 * (pagos de pacientes y cobros de planes). Idempotente: se puede correr las
 * veces que sea; los pagos con referencia no se tocan.
 *
 *   php artisan nutrilife:payment-references --dry-run   (solo cuenta)
 *   php artisan nutrilife:payment-references
 */
class AssignPaymentReferencesCommand extends Command
{
    protected $signature = 'nutrilife:payment-references {--dry-run : Solo muestra cuántos pagos recibirían referencia}';

    protected $description = 'Genera la referencia interna de NutriLife para los pagos históricos que no la tienen';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $result = PaymentReferenceService::backfill($dryRun);

        if ($result['total'] === 0) {
            $this->info('Todos los pagos ya tienen referencia interna.');

            return self::SUCCESS;
        }

        $this->table(
            ['Prefijo', 'Método', 'Pagos'],
            collect($result['by_prefix'])->map(fn ($count, $prefix) => [$prefix, PaymentReferenceService::labelFor($prefix), $count])->values()->all()
        );

        $this->line('payments: '.$result['by_table']['payments'].' · subscription_payments: '.$result['by_table']['subscription_payments']);

        $dryRun
            ? $this->warn('Simulación: no se guardó nada. Quita --dry-run para asignarlas.')
            : $this->info($result['total'].' referencias asignadas.');

        return self::SUCCESS;
    }
}

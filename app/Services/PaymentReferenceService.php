<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * REFERENCIA INTERNA DE NUTRILIFE para todos los pagos.
 *
 * Formato: PRE-AAAAMMDD-NNNNNN
 *   PRE       método de pago (ver PREFIXES; OTR = otro / no especificado)
 *   AAAAMMDD  fecha de registro del pago (zona horaria de config/app.php)
 *   NNNNNN    número consecutivo GLOBAL (mínimo 6 dígitos), compartido por
 *             payments y subscription_payments: no se repite nunca, aunque
 *             coincidan método y fecha, y no se reutiliza si un pago se
 *             cancela o se elimina.
 *
 * Solo la genera el servidor: el campo no es asignable en masa y los
 * modelos (App\Models\Concerns\HasInternalReference) la crean al guardar,
 * desde cualquier módulo (pagos de pacientes, contratación de planes,
 * asignación del administrador, seeders…). La columna `reference` de cada
 * tabla sigue siendo la referencia EXTERNA (banco, terminal, pasarela).
 */
class PaymentReferenceService
{
    /** Fila de reference_counters que numera todos los pagos. */
    public const COUNTER = 'payments';

    public const UNKNOWN_PREFIX = 'OTR';

    /** Método (normalizado: minúsculas, sin acentos) → prefijo. */
    public const PREFIXES = [
        'efectivo' => 'EFE',
        'tarjeta' => 'TAR',
        'tarjeta de credito' => 'TAR',
        'tarjeta de debito' => 'TAR',
        'transferencia' => 'TRA',
        'transferencia bancaria' => 'TRA',
        'spei' => 'TRA',
        // Contratación de planes sin cobro real (config nutrilife.checkout).
        'simulado' => 'SIM',
        // Pasarela de pago en línea (el id de la transacción va en `reference`).
        'pasarela' => 'PAS',
        'en linea' => 'PAS',
    ];

    /** Prefijo → etiqueta visible y clase del distintivo (partials/payment-reference). */
    public const LABELS = [
        'EFE' => 'Efectivo',
        'TAR' => 'Tarjeta',
        'TRA' => 'Transferencia',
        'SIM' => 'Pago simulado',
        'PAS' => 'Pasarela en línea',
        'OTR' => 'Otro / no especificado',
    ];

    public const PATTERN = '/^(EFE|TAR|TRA|SIM|PAS|OTR)-\d{8}-\d{6,}$/';

    private const TABLES = ['payments', 'subscription_payments'];

    private const MAX_ATTEMPTS = 5;

    /**
     * Prefijo del método. "Otro", "Manual" (asignación del administrador sin
     * método), vacío o cualquier valor desconocido → OTR: nunca se adivina
     * si fue efectivo, tarjeta o transferencia.
     */
    public static function prefixFor(?string $method): string
    {
        $key = Str::of((string) $method)->ascii()->lower()->squish()->value();

        return self::PREFIXES[$key] ?? self::UNKNOWN_PREFIX;
    }

    public static function labelFor(string $prefix): string
    {
        return self::LABELS[$prefix] ?? self::LABELS[self::UNKNOWN_PREFIX];
    }

    /** Prefijo de una referencia ya generada (OTR si no tiene el formato). */
    public static function prefixOf(?string $reference): string
    {
        $prefix = strtoupper(substr((string) $reference, 0, 3));

        return array_key_exists($prefix, self::LABELS) ? $prefix : self::UNKNOWN_PREFIX;
    }

    /**
     * Nueva referencia única. Si (por datos importados a mano) el número ya
     * existiera en alguna tabla, se toma el siguiente; el índice UNIQUE de la
     * BD es la última barrera.
     */
    public static function generate(?string $method, ?CarbonInterface $date = null): string
    {
        $prefix = self::prefixFor($method);
        $day = ($date ?? now())->copy()->timezone(config('app.timezone'))->format('Ymd');

        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; $attempt++) {
            $reference = sprintf('%s-%s-%06d', $prefix, $day, self::nextNumber());

            if (! self::exists($reference)) {
                return $reference;
            }
        }

        throw new RuntimeException('No se pudo generar una referencia de pago única.');
    }

    public static function exists(string $reference): bool
    {
        foreach (self::TABLES as $table) {
            if (DB::table($table)->where('internal_reference', $reference)->exists()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Siguiente número del contador persistente. La fila se lee con bloqueo
     * (SELECT … FOR UPDATE) dentro de una transacción: dos registros
     * simultáneos esperan su turno y nunca reciben el mismo número.
     */
    private static function nextNumber(): int
    {
        return DB::transaction(function () {
            // Si la fila no existe (instalación nueva o contador borrado), se
            // crea partiendo del número más alto ya usado.
            if (! DB::table('reference_counters')->where('name', self::COUNTER)->exists()) {
                DB::table('reference_counters')->insertOrIgnore([
                    'name' => self::COUNTER,
                    'value' => self::highestUsedNumber(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $current = (int) DB::table('reference_counters')
                ->where('name', self::COUNTER)
                ->lockForUpdate()
                ->value('value');

            DB::table('reference_counters')
                ->where('name', self::COUNTER)
                ->update(['value' => $current + 1, 'updated_at' => now()]);

            return $current + 1;
        }, 3);
    }

    private static function highestUsedNumber(): int
    {
        $max = 0;

        foreach (self::TABLES as $table) {
            DB::table($table)->whereNotNull('internal_reference')->pluck('internal_reference')
                ->each(function ($reference) use (&$max) {
                    $max = max($max, (int) substr(strrchr((string) $reference, '-'), 1));
                });
        }

        return $max;
    }

    /**
     * Referencias para los pagos históricos que no tienen (idempotente: los
     * que ya tienen no se tocan). Solo escribe internal_reference, sin
     * cambiar updated_at ni ningún otro campo; incluye pagos cancelados y
     * eliminados (soft delete). La fecha es la de registro (created_at).
     *
     * @return array{total: int, by_prefix: array<string, int>, by_table: array<string, int>}
     */
    public static function backfill(bool $dryRun = false): array
    {
        $result = ['total' => 0, 'by_prefix' => [], 'by_table' => []];

        foreach (self::TABLES as $table) {
            $result['by_table'][$table] = 0;

            $pending = DB::table($table)
                ->whereNull('internal_reference')
                ->orderBy('created_at')
                ->orderBy('id')
                ->get(['id', 'payment_method', 'created_at', 'paid_at']);

            foreach ($pending as $row) {
                $prefix = self::prefixFor($row->payment_method);
                $result['by_prefix'][$prefix] = ($result['by_prefix'][$prefix] ?? 0) + 1;
                $result['by_table'][$table]++;
                $result['total']++;

                if ($dryRun) {
                    continue;
                }

                $date = Carbon::parse($row->created_at ?? $row->paid_at ?? now());

                DB::table($table)
                    ->where('id', $row->id)
                    ->whereNull('internal_reference')
                    ->update(['internal_reference' => self::generate($row->payment_method, $date)]);
            }
        }

        return $result;
    }
}

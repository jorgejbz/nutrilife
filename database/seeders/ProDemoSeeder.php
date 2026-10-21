<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * DATOS DE PRUEBA SOLO PARA pro@nutrilife.test (entorno local).
 *
 *   php artisan db:seed --class=ProDemoSeeder
 *
 * Atajo de DemoAccountsSeeder para una sola cuenta; ahí está el detalle de
 * lo que se carga. No borra nada y se puede repetir sin duplicar.
 */
class ProDemoSeeder extends Seeder
{
    public const EMAIL = 'pro@nutrilife.test';

    public function run(): void
    {
        $this->container->make(DemoAccountsSeeder::class)
            ->setContainer($this->container)
            ->setCommand($this->command)
            ->seedAccount(self::EMAIL);
    }
}

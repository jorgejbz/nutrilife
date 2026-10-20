<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Planes primero: la prueba gratuita de 7 días se asocia a uno.
        $this->call(SubscriptionPlanSeeder::class);

        // Catálogos de patologías y alérgenos (historial clínico de pacientes)
        $this->call(ClinicalCatalogSeeder::class);

        // Catálogo de alimentos (después de los alérgenos para ligarlos).
        // Seguro de ejecutar varias veces: php artisan db:seed --class=FoodSeeder
        $this->call(FoodSeeder::class);

        // User::factory(10)->create();

        // Solo si no existe: así `php artisan db:seed` se puede repetir sin
        // fallar por el correo único.
        if (! User::where('email', 'test@example.com')->exists()) {
            User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
        }
    }
}

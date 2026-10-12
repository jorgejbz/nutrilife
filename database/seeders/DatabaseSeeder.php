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

        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}

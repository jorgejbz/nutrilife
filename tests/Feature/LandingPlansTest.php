<?php

namespace Tests\Feature;

use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPlansTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_hides_consultorio_and_shows_new_patient_limits(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('ESENCIAL')->assertSee('Hasta 25 pacientes')
            ->assertSee('Hasta 100 pacientes')
            ->assertDontSee('CONSULTORIO')
            ->assertDontSee('599');
    }

    public function test_consultorio_card_can_be_shown_again_by_config(): void
    {
        config(['nutrilife.landing.show_consultorio' => true]);

        $this->get('/')->assertOk()->assertSee('CONSULTORIO')->assertSee('599');
    }

    public function test_seeded_plans_use_new_patient_limits_and_keep_consultorio(): void
    {
        $this->seed(SubscriptionPlanSeeder::class);

        $this->assertDatabaseHas('subscription_plans', ['name' => 'Esencial', 'max_patients' => 25]);
        $this->assertDatabaseHas('subscription_plans', ['name' => 'Pro', 'max_patients' => 100]);
        $this->assertDatabaseHas('subscription_plans', ['name' => 'Consultorio', 'max_patients' => null]);
    }
}

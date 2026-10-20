<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'professional_license' => '12345678',
            'degree_number' => 'LN-2020-001',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        // NutriLife: la cuenta queda pendiente de activación y se muestra la
        // pantalla de gracias (ya no se entra directo al dashboard).
        $this->assertGuest();
        $response->assertRedirect(route('register.thanks', absolute: false));
    }
}

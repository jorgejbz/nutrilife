<?php

namespace Tests\Feature;

use Tests\TestCase;

/** Auditoría 2026-11-12: producción no enviaba cabeceras de seguridad. */
class SecurityHeadersTest extends TestCase
{
    public function test_web_and_api_send_security_headers(): void
    {
        foreach (['/login', '/api/me'] as $url) {
            $this->get($url, ['Accept' => 'application/json'])
                ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
                ->assertHeader('X-Content-Type-Options', 'nosniff')
                ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        }
    }

    public function test_hsts_only_over_https(): void
    {
        $this->get('/login')->assertHeaderMissing('Strict-Transport-Security');
        $this->get('https://localhost/login')->assertHeader('Strict-Transport-Security');
    }
}

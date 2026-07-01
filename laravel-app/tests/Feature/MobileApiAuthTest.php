<?php

namespace Tests\Feature;

use Tests\TestCase;

class MobileApiAuthTest extends TestCase
{
    public function test_register_requires_mobile_api_key_as_json_without_accept_header(): void
    {
        config(['deployment.mobile_api_key' => '12345678']);

        $response = $this->post('/api/auth/register', [
            'firstName' => 'Test',
            'lastName' => 'User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => 'TENANT',
        ]);

        $response
            ->assertStatus(401)
            ->assertHeader('content-type', 'application/json')
            ->assertJson(['message' => 'Invalid mobile API key.']);
    }

    public function test_register_validation_errors_are_json_without_accept_header(): void
    {
        config(['deployment.mobile_api_key' => '12345678']);

        $response = $this->withHeader('X-Mobile-App-Key', '12345678')
            ->post('/api/auth/register', [
                'firstName' => 'Test',
                'lastName' => 'User',
                'email' => 'not-an-email',
                'password' => 'short',
                'role' => null,
            ]);

        $response
            ->assertStatus(422)
            ->assertHeader('content-type', 'application/json')
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_protected_api_routes_do_not_redirect_to_admin_login_html(): void
    {
        config(['deployment.mobile_api_key' => '12345678']);

        $response = $this->withHeader('X-Mobile-App-Key', '12345678')
            ->get('/api/auth/me');

        $response
            ->assertUnauthorized()
            ->assertHeader('content-type', 'application/json')
            ->assertJson(['message' => 'Unauthenticated.']);
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
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

    public function test_every_android_api_action_has_a_matching_laravel_route(): void
    {
        $expected = [
            ['POST', 'api/auth/login'], ['POST', 'api/auth/register'],
            ['POST', 'api/auth/otp/request'], ['POST', 'api/auth/otp/verify'],
            ['POST', 'api/auth/email-otp/request'], ['POST', 'api/auth/email-otp/verify'],
            ['POST', 'api/auth/forgot-password'], ['POST', 'api/auth/reset-password'],
            ['GET', 'api/auth/me'], ['GET', 'api/users/me/profile'],
            ['PATCH', 'api/users/me/profile'], ['POST', 'api/users/me/profile-image'],
            ['POST', 'api/invitations/accept'], ['POST', 'api/users/device-token'],
            ['GET', 'api/invoices'], ['POST', 'api/payments/pay'],
            ['GET', 'api/payments/invoice/{}'], ['GET', 'api/notifications'],
            ['PATCH', 'api/notifications/{}/read'], ['POST', 'api/notifications/mark-all-read'],
            ['GET', 'api/support/messages'], ['POST', 'api/support/messages'],
            ['POST', 'api/support/upload'], ['GET', 'api/maintenance'],
            ['POST', 'api/maintenance'],
        ];

        $registered = collect(Route::getRoutes()->getRoutes())->flatMap(function ($route) {
            $uri = preg_replace('/\{[^}]+\}/', '{}', $route->uri());
            return collect($route->methods())->map(fn($method) => [$method, $uri]);
        });

        foreach ($expected as [$method, $uri]) {
            $this->assertTrue(
                $registered->contains(fn($route) => $route[0] === $method && $route[1] === $uri),
                "{$method} {$uri} is required by the Android app but is not registered in Laravel."
            );
        }
    }
}

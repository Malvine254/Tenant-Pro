<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AuthRecoveryTest extends TestCase
{
    private string $apiKey = '12345678';

    protected function setUp(): void
    {
        parent::setUp();
        if (!in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('PDO SQLite is required for auth recovery database tests.');
        }
        Artisan::call('migrate:fresh', ['--force' => true]);
        config(['deployment.mobile_api_key' => $this->apiKey]);
    }

    public function test_unverified_user_is_blocked_and_can_request_verification_and_password_reset_codes(): void
    {
        Mail::fake();
        $user = $this->makeUnverifiedUser();

        $this->apiPost('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertForbidden()->assertJsonFragment(['email' => $user->email]);

        $this->apiPost('/api/auth/email-otp/request', ['email' => $user->email])
            ->assertOk();

        $this->apiPost('/api/auth/forgot-password', ['email' => $user->email])
            ->assertOk();

        Mail::assertSentCount(2);
    }

    public function test_password_reset_also_verifies_an_unverified_email(): void
    {
        $user = $this->makeUnverifiedUser();
        $code = '654321';
        $key = 'password-reset-otp:'.hash('sha256', strtolower($user->email));
        Cache::put($key, ['hash' => Hash::make($code)], now()->addMinutes(10));

        $this->apiPost('/api/auth/reset-password', [
            'email' => $user->email,
            'code' => $code,
            'newPassword' => 'new-password-123',
        ])->assertOk()->assertJsonFragment([
            'message' => 'Password reset successfully. You can now sign in.',
        ]);

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    private function makeUnverifiedUser(): User
    {
        $role = Role::create(['name' => 'TENANT', 'description' => 'Tenant']);

        return User::create([
            'name' => 'Recovery User',
            'email' => 'recovery@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);
    }

    private function apiPost(string $uri, array $data)
    {
        return $this->withHeader('X-Mobile-App-Key', $this->apiKey)->postJson($uri, $data);
    }
}

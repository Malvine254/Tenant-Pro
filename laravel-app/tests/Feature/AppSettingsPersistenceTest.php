<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AppSettingsPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_preferences_are_persisted_without_removing_other_account_settings(): void
    {
        config(['deployment.mobile_api_key' => 'test-mobile-key']);

        $user = User::factory()->create([
            'is_active' => true,
            'app_settings' => [
                'notificationsEnabled' => true,
                'emailNotificationsEnabled' => true,
                'biometricLockEnabled' => false,
                'paymentSettings' => [
                    'payment_type' => 'PAYBILL',
                    'paybill_number' => '123456',
                ],
            ],
        ]);
        Sanctum::actingAs($user);

        $response = $this
            ->withHeader('X-Mobile-App-Key', 'test-mobile-key')
            ->patchJson('/api/users/me/profile', [
                'appSettings' => [
                    'notificationsEnabled' => false,
                    'emailNotificationsEnabled' => false,
                    'biometricLockEnabled' => true,
                ],
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('appSettings.notificationsEnabled', false)
            ->assertJsonPath('appSettings.emailNotificationsEnabled', false)
            ->assertJsonPath('appSettings.biometricLockEnabled', true);

        $settings = $user->fresh()->app_settings;
        $this->assertFalse($settings['notificationsEnabled']);
        $this->assertFalse($settings['emailNotificationsEnabled']);
        $this->assertTrue($settings['biometricLockEnabled']);
        $this->assertSame('123456', $settings['paymentSettings']['paybill_number']);
    }

    public function test_profile_contact_fields_are_persisted_with_settings(): void
    {
        config(['deployment.mobile_api_key' => 'test-mobile-key']);

        $user = User::factory()->create(['is_active' => true]);
        Sanctum::actingAs($user);

        $this
            ->withHeader('X-Mobile-App-Key', 'test-mobile-key')
            ->patchJson('/api/users/me/profile', [
                'email' => 'updated@example.com',
                'emergencyContactPhone' => '+254700111222',
                'bio' => 'Updated tenant profile.',
            ])
            ->assertOk()
            ->assertJsonPath('email', 'updated@example.com')
            ->assertJsonPath('emergencyContactPhone', '+254700111222')
            ->assertJsonPath('bio', 'Updated tenant profile.');

        $user->refresh();
        $this->assertSame('updated@example.com', $user->email);
        $this->assertSame('+254700111222', $user->emergency_contact_phone);
        $this->assertSame('Updated tenant profile.', $user->bio);
    }
}

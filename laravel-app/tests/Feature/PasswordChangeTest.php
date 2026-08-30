<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_change_password_without_losing_current_session(): void
    {
        $role = Role::create(['name' => 'TENANT']);
        $user = User::factory()->create([
            'role_id' => $role->id,
            'password' => Hash::make('OldPassword1!'),
            'is_active' => true,
        ]);
        $currentToken = $user->createToken('current')->plainTextToken;
        $user->createToken('another-device');
        config(['deployment.mobile_api_key' => 'test-mobile-key']);

        $this->withHeaders([
            'X-Mobile-App-Key' => 'test-mobile-key',
            'Authorization' => 'Bearer '.$currentToken,
        ])->postJson('/api/users/me/password', [
            'currentPassword' => 'OldPassword1!',
            'password' => 'NewPassword2!',
            'passwordConfirmation' => 'NewPassword2!',
        ])->assertOk()
            ->assertJson(['message' => 'Password changed successfully.']);

        $this->assertTrue(Hash::check('NewPassword2!', $user->fresh()->password));
        $this->assertSame(1, $user->tokens()->count());
    }

    public function test_current_password_must_be_correct(): void
    {
        $role = Role::create(['name' => 'TENANT']);
        $user = User::factory()->create([
            'role_id' => $role->id,
            'password' => Hash::make('OldPassword1!'),
            'is_active' => true,
        ]);
        $token = $user->createToken('current')->plainTextToken;
        config(['deployment.mobile_api_key' => 'test-mobile-key']);

        $this->withHeaders([
            'X-Mobile-App-Key' => 'test-mobile-key',
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/users/me/password', [
            'currentPassword' => 'WrongPassword!',
            'password' => 'NewPassword2!',
            'passwordConfirmation' => 'NewPassword2!',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('current_password');

        $this->assertTrue(Hash::check('OldPassword1!', $user->fresh()->password));
    }
}

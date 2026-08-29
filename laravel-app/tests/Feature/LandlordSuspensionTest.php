<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandlordSuspensionTest extends TestCase
{
    use RefreshDatabase;

    public function test_suspending_landlord_revokes_only_landlord_sessions(): void
    {
        [$admin, $landlord, $tenant] = $this->users();
        $this->assignTenantToLandlord($tenant, $landlord);

        $landlord->createToken('landlord-session');
        $tenant->createToken('tenant-session');

        $this->actingAs($admin)
            ->patch(route('admin.landlords.status', $landlord), ['is_active' => false])
            ->assertRedirect();

        $this->assertFalse((bool) $landlord->fresh()->is_active);
        $this->assertSame(0, $landlord->tokens()->count());
        $this->assertSame(1, $tenant->tokens()->count());
    }

    public function test_tenant_can_login_but_landlord_dependent_operations_are_restricted(): void
    {
        [, $landlord, $tenant] = $this->users();
        $this->assignTenantToLandlord($tenant, $landlord);
        $landlord->update(['is_active' => false]);
        config(['deployment.mobile_api_key' => 'test-mobile-key']);

        $login = $this->withHeader('X-Mobile-App-Key', 'test-mobile-key')
            ->postJson('/api/auth/login', [
                'email' => $tenant->email,
                'password' => 'password',
            ])
            ->assertOk()
            ->assertJsonPath('user.subscriptionAllowed', false)
            ->assertJsonCount(0, 'user.tenantProfiles');

        $token = $login->json('token');
        $headers = [
            'X-Mobile-App-Key' => 'test-mobile-key',
            'Authorization' => 'Bearer '.$token,
        ];

        $this->withHeaders($headers)->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('subscriptionAllowed', false)
            ->assertJsonCount(0, 'tenantProfiles');

        $this->withHeaders($headers)->getJson('/api/invoices')
            ->assertForbidden()
            ->assertJson([
                'code' => 'LANDLORD_ACCESS_SUSPENDED',
            ]);
    }

    private function users(): array
    {
        $adminRole = Role::create(['name' => 'ADMIN']);
        $landlordRole = Role::create(['name' => 'LANDLORD']);
        $tenantRole = Role::create(['name' => 'TENANT']);

        return [
            User::factory()->create(['role_id' => $adminRole->id, 'is_active' => true]),
            User::factory()->create([
                'role_id' => $landlordRole->id,
                'is_active' => true,
                'requires_subscription' => false,
            ]),
            User::factory()->create(['role_id' => $tenantRole->id, 'is_active' => true]),
        ];
    }

    private function assignTenantToLandlord(User $tenantUser, User $landlord): void
    {
        $property = Property::create([
            'landlord_id' => $landlord->id,
            'name' => 'Test Property',
            'address_line' => 'Test Road',
            'city' => 'Nairobi',
            'country' => 'Kenya',
        ]);
        $unit = Unit::create([
            'property_id' => $property->id,
            'unit_number' => 'A1',
            'rent_amount' => 15000,
            'status' => 'OCCUPIED',
        ]);
        Tenant::create([
            'user_id' => $tenantUser->id,
            'unit_id' => $unit->id,
            'move_in_date' => now()->toDateString(),
            'is_active' => true,
        ]);
    }
}

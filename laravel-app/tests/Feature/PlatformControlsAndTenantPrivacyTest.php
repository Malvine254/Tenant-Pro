<?php

namespace Tests\Feature;

use App\Models\Invitation;
use App\Models\PlatformSetting;
use App\Models\Property;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Services\PlatformSettingsService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PlatformControlsAndTenantPrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_store_encrypted_daraja_credentials_and_regular_admin_cannot(): void
    {
        $superAdmin = $this->userWithRole('SUPER_ADMIN');
        $admin = $this->userWithRole('ADMIN');
        $payload = [
            'environment' => 'production',
            'shortcode' => '123456',
            'callback_url' => 'https://app.starmaxltd.com/api/payments/mpesa/callback',
            'consumer_key' => 'consumer-key-value',
            'consumer_secret' => 'consumer-secret-value',
            'passkey' => 'production-passkey-value',
            'current_password' => 'password',
        ];

        $this->actingAs($admin)
            ->put(route('admin.settings.daraja'), $payload)
            ->assertForbidden();

        $this->actingAs($superAdmin)
            ->put(route('admin.settings.daraja'), $payload)
            ->assertRedirect(route('admin.settings.index', ['tab' => 'daraja']));

        $this->assertSame('consumer-secret-value', PlatformSetting::findOrFail('daraja.consumer_secret')->value);
        $storedValue = DB::table('platform_settings')->where('key', 'daraja.consumer_secret')->value('value');
        $this->assertNotSame('consumer-secret-value', $storedValue);
        $this->assertStringNotContainsString('consumer-secret-value', (string) $storedValue);
    }

    public function test_customer_maintenance_mode_blocks_api_but_preserves_admin_health_and_mpesa_callback(): void
    {
        $superAdmin = $this->userWithRole('SUPER_ADMIN');
        app(PlatformSettingsService::class)->setMany([
            'maintenance.enabled' => '1',
            'maintenance.message' => 'Scheduled maintenance is in progress.',
        ], $superAdmin);

        $this->getJson('/api/properties')
            ->assertStatus(503)
            ->assertJsonPath('code', 'PLATFORM_MAINTENANCE');
        $this->getJson('/api/health')->assertOk();
        $this->get(route('admin.login'))->assertOk();
        $this->postJson('/api/payments/mpesa/callback', [])->assertStatus(400);
    }

    public function test_landlord_only_sees_unassigned_tenant_accounts_they_invited(): void
    {
        $landlord = $this->userWithRole('LANDLORD');
        $otherLandlord = $this->userWithRole('LANDLORD');
        $visibleTenant = $this->userWithRole('TENANT', ['email' => 'invited@example.test']);
        $hiddenTenant = $this->userWithRole('TENANT', ['email' => 'unrelated@example.test']);

        $property = Property::create([
            'landlord_id' => $landlord->id,
            'name' => 'Visible Property',
            'address_line' => '1 Test Road',
            'city' => 'Nairobi',
        ]);
        $unit = Unit::create([
            'property_id' => $property->id,
            'unit_number' => 'A1',
            'rent_amount' => 10000,
        ]);

        Invitation::create([
            'invite_type' => 'TENANT',
            'code' => 'VISIBLE-INVITE',
            'email' => $visibleTenant->email,
            'property_id' => $property->id,
            'unit_id' => $unit->id,
            'sent_by_id' => $landlord->id,
            'status' => 'PENDING',
            'expires_at' => now()->addWeek(),
        ]);
        Invitation::create([
            'invite_type' => 'TENANT',
            'code' => 'HIDDEN-INVITE',
            'email' => $hiddenTenant->email,
            'property_id' => $property->id,
            'unit_id' => $unit->id,
            'sent_by_id' => $otherLandlord->id,
            'status' => 'PENDING',
            'expires_at' => now()->addWeek(),
        ]);

        $this->actingAs($landlord)
            ->get(route('admin.tenants.index', ['tab' => 'unassigned']))
            ->assertOk()
            ->assertSee('invited@example.test')
            ->assertDontSee('unrelated@example.test');
    }

    public function test_landlord_owner_can_add_a_team_member_with_scoped_access(): void
    {
        Notification::fake();
        $owner = $this->userWithRole('LANDLORD');
        $otherOwner = $this->userWithRole('LANDLORD');
        Property::create([
            'landlord_id' => $owner->id,
            'name' => 'Owner Property',
            'address_line' => '1 Owner Road',
            'city' => 'Nairobi',
        ]);
        Property::create([
            'landlord_id' => $otherOwner->id,
            'name' => 'Hidden Property',
            'address_line' => '2 Other Road',
            'city' => 'Nairobi',
        ]);

        $this->actingAs($owner)->post(route('admin.team.store'), [
            'first_name' => 'Property',
            'last_name' => 'Manager',
            'email' => 'manager@example.test',
            'current_password' => 'password',
        ])->assertRedirect();

        $member = User::where('email', 'manager@example.test')->firstOrFail();
        $this->assertSame($owner->id, $member->managed_landlord_id);
        Notification::assertSentTo($member, ResetPassword::class);

        $this->actingAs($member)
            ->get(route('admin.properties.index'))
            ->assertOk()
            ->assertSee('Owner Property')
            ->assertDontSee('Hidden Property');
        $this->actingAs($member)->get(route('admin.team.index'))->assertForbidden();
        $this->actingAs($member)->put(route('admin.settings.payment'), [])->assertForbidden();
    }

    public function test_invitation_workspace_renders_without_javascript(): void
    {
        $owner = $this->userWithRole('LANDLORD');

        $this->actingAs($owner)
            ->get(route('admin.invitations.index', ['workspace' => 'create']))
            ->assertOk()
            ->assertSee('class="invitation-workspace-panel active"', false)
            ->assertSee('Invite Tenant to Vacant Unit');

        $this->actingAs($owner)
            ->get(route('admin.invitations.index', ['workspace' => 'history']))
            ->assertOk()
            ->assertSee('class="invitation-workspace-panel active"', false)
            ->assertSee('Invitation history');
    }

    private function userWithRole(string $roleName, array $attributes = []): User
    {
        $role = Role::firstOrCreate(['name' => $roleName]);

        return User::factory()->create(array_merge([
            'role_id' => $role->id,
            'is_active' => true,
        ], $attributes));
    }
}

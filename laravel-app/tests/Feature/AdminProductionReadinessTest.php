<?php

namespace Tests\Feature;

use App\Models\AdminAuditLog;
use App\Models\Notification;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductionReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_pages_send_private_security_headers(): void
    {
        $this->get(route('admin.login'))
            ->assertOk()
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertDontSee('Rental operations');
    }

    public function test_regular_admin_cannot_access_super_admin_system_tools(): void
    {
        $admin = $this->userWithRole('ADMIN');

        $this->actingAs($admin)->get(route('admin.audit-logs.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.deployment-tools.index'))->assertForbidden();
    }

    public function test_successful_admin_change_is_recorded_without_request_payload(): void
    {
        $superAdmin = $this->userWithRole('SUPER_ADMIN', [
            'first_name' => 'System',
            'last_name' => 'Admin',
            'phone_number' => '0712345678',
        ]);

        $this->actingAs($superAdmin)->put(route('admin.settings.account'), [
            'first_name' => 'Updated',
            'last_name' => 'Administrator',
            'email' => $superAdmin->email,
            'phone_number' => '0712345678',
        ])->assertRedirect();

        $event = AdminAuditLog::query()->firstOrFail();
        $this->assertSame($superAdmin->id, $event->actor_id);
        $this->assertSame('admin.settings.account', $event->action);
        $this->assertSame(302, $event->status_code);
        $this->assertArrayNotHasKey('password', $event->getAttributes());
    }

    public function test_incomplete_profile_is_warned_on_every_admin_page_and_blocks_operations(): void
    {
        $admin = $this->userWithRole('ADMIN', [
            'first_name' => null,
            'phone_number' => null,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('setup checkpoint needs attention')
            ->assertSee('Account profile');

        $this->actingAs($admin)
            ->post(route('admin.properties.store'), [])
            ->assertRedirect(route('admin.settings.index', ['tab' => 'account']))
            ->assertSessionHas('error', 'Complete your account profile before performing operational actions.');
    }

    public function test_landlord_payment_checkpoint_blocks_tenant_onboarding_until_configured(): void
    {
        $landlord = $this->userWithRole('LANDLORD', [
            'first_name' => 'Ready',
            'phone_number' => '254712345678',
            'app_settings' => [],
        ]);

        $this->actingAs($landlord)
            ->post(route('admin.invitations.tenants.store'), [])
            ->assertRedirect(route('admin.settings.index', ['tab' => 'payment']))
            ->assertSessionHas('error');
    }

    public function test_admin_notifications_are_private_and_can_be_marked_as_read(): void
    {
        $admin = $this->userWithRole('ADMIN', [
            'first_name' => 'Ready',
            'last_name' => 'Admin',
            'phone_number' => '254712345678',
        ]);
        $otherAdmin = $this->userWithRole('ADMIN');
        $ownNotification = Notification::query()->create([
            'user_id' => $admin->id,
            'type' => 'SYSTEM',
            'title' => 'Your notification',
            'body' => 'Visible only to the intended administrator.',
        ]);
        $otherNotification = Notification::query()->create([
            'user_id' => $otherAdmin->id,
            'type' => 'SYSTEM',
            'title' => 'Another administrator notification',
            'body' => 'This must remain private.',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.notifications.index'))
            ->assertOk()
            ->assertSee('Your notification')
            ->assertDontSee('Another administrator notification');

        $this->actingAs($admin)
            ->patch(route('admin.notifications.read', $ownNotification))
            ->assertRedirect();
        $this->assertTrue($ownNotification->fresh()->is_read);

        $this->actingAs($admin)
            ->patch(route('admin.notifications.read', $otherNotification))
            ->assertForbidden();
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

<?php

namespace Tests\Feature;

use App\Models\AdminAuditLog;
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

    private function userWithRole(string $roleName, array $attributes = []): User
    {
        $role = Role::firstOrCreate(['name' => $roleName]);

        return User::factory()->create(array_merge([
            'role_id' => $role->id,
            'is_active' => true,
        ], $attributes));
    }
}

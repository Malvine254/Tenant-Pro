<?php

namespace Tests\Feature;

use App\Mail\TenantProUpdateMail;
use App\Models\Notification;
use App\Models\Role;
use App\Models\User;
use App\Services\LandlordSubscriptionReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LandlordSubscriptionFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_missed_seven_day_run_still_sends_the_nearest_reminder_once(): void
    {
        Mail::fake();
        Carbon::setTestNow('2026-09-01 10:00:00');
        $landlord = $this->landlord([
            'billing_status' => 'active',
            'subscription_started_at' => now()->subMonth(),
            'service_paid_until' => now()->addDays(6),
        ]);

        $service = app(LandlordSubscriptionReminderService::class);
        $first = $service->runDailyReminders();
        $second = $service->runDailyReminders();

        $this->assertSame(1, $first['sent']);
        $this->assertSame(0, $second['sent']);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $landlord->id,
            'type' => 'SUBSCRIPTION_REMINDER',
        ]);
        $notification = Notification::where('user_id', $landlord->id)->firstOrFail();
        $this->assertSame(7, $notification->metadata['reminder_milestone']);
        $this->assertSame(6, $notification->metadata['days_until_due']);
        Mail::assertSent(TenantProUpdateMail::class, 1);
    }

    public function test_expiry_locks_operations_and_sends_only_one_lock_notice(): void
    {
        Mail::fake();
        Carbon::setTestNow('2026-09-01 10:00:00');
        $landlord = $this->landlord([
            'billing_status' => 'active',
            'subscription_started_at' => now()->subMonth(),
            'service_paid_until' => now(),
        ]);

        $service = app(LandlordSubscriptionReminderService::class);
        $first = $service->synchronizeExpirations();
        $second = $service->synchronizeExpirations();

        $this->assertSame(1, $first['locked']);
        $this->assertSame(0, $second['locked']);
        $this->assertSame('past_due', $landlord->fresh()->billing_status);
        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $landlord->id,
            'type' => 'SUBSCRIPTION_LOCKED',
            'title' => 'Tenant operations locked',
        ]);
        Mail::assertSent(TenantProUpdateMail::class, 1);
    }

    public function test_past_due_landlord_can_login_but_operational_routes_are_locked(): void
    {
        Carbon::setTestNow('2026-09-01 10:00:00');
        $landlord = $this->landlord([
            'billing_status' => 'past_due',
            'subscription_started_at' => now()->subMonths(2),
            'service_paid_until' => now()->subMinute(),
        ]);

        $this->post(route('admin.login.post'), [
            'email' => $landlord->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Tenant operations are locked');

        $this->get(route('admin.properties.index'))
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('error');
    }

    public function test_mobile_landlord_keeps_account_access_but_receives_structured_operation_lock(): void
    {
        Carbon::setTestNow('2026-09-01 10:00:00');
        config(['deployment.mobile_api_key' => 'test-mobile-key']);
        $landlord = $this->landlord([
            'billing_status' => 'past_due',
            'subscription_started_at' => now()->subMonths(2),
            'service_paid_until' => now()->subMinute(),
        ]);

        $login = $this->withHeader('X-Mobile-App-Key', 'test-mobile-key')
            ->postJson('/api/auth/login', [
                'email' => $landlord->email,
                'password' => 'password',
            ])
            ->assertOk()
            ->assertJsonPath('user.subscriptionAllowed', false);

        $headers = [
            'X-Mobile-App-Key' => 'test-mobile-key',
            'Authorization' => 'Bearer '.$login->json('token'),
        ];
        $this->withHeaders($headers)->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('subscriptionAllowed', false);
        $this->withHeaders($headers)->getJson('/api/properties')
            ->assertForbidden()
            ->assertJsonPath('code', 'SUBSCRIPTION_PAST_DUE');
    }

    private function landlord(array $overrides = []): User
    {
        $role = Role::firstOrCreate(['name' => 'LANDLORD']);

        return User::factory()->create(array_merge([
            'role_id' => $role->id,
            'is_active' => true,
            'requires_subscription' => true,
            'monthly_service_fee' => 2500,
            'trial_started_at' => now()->subMonths(2),
            'trial_ends_at' => now()->subMonth(),
        ], $overrides));
    }
}

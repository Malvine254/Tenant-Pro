<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Property;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Services\TenantAppNotificationService;
use App\Services\TenantBillingService;
use App\Services\TenantEmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Mockery;
use Tests\TestCase;

class TenantDepositBillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_move_in_deposit_equals_rent_and_is_only_created_once(): void
    {
        Carbon::setTestNow('2026-09-01 09:00:00');

        $landlordRole = Role::firstOrCreate(['name' => 'LANDLORD']);
        $tenantRole = Role::firstOrCreate(['name' => 'TENANT']);
        $landlord = User::factory()->create(['role_id' => $landlordRole->id]);
        $tenantUser = User::factory()->create(['role_id' => $tenantRole->id]);
        $property = Property::create([
            'landlord_id' => $landlord->id,
            'name' => 'Deposit Test Apartments',
            'address_line' => '1 Test Road',
            'city' => 'Nairobi',
        ]);
        $unit = Unit::create([
            'property_id' => $property->id,
            'unit_number' => 'A1',
            'rent_amount' => 15000,
            'status' => 'OCCUPIED',
        ]);
        $tenancy = Tenant::create([
            'user_id' => $tenantUser->id,
            'unit_id' => $unit->id,
            'move_in_date' => '2026-09-01',
            'is_active' => true,
        ]);

        $notifications = Mockery::mock(TenantAppNotificationService::class);
        $notifications->shouldReceive('invoiceCreated')->zeroOrMoreTimes();
        $emails = Mockery::mock(TenantEmailService::class);
        $emails->shouldReceive('invoiceCreated')->zeroOrMoreTimes();
        $billing = new TenantBillingService($notifications, $emails);

        $billing->createInitialRentInvoice($tenancy);
        $billing->createInitialRentInvoice($tenancy);

        $this->assertDatabaseHas('invoices', [
            'tenant_id' => $tenantUser->id,
            'unit_id' => $unit->id,
            'billing_type' => 'DEPOSIT',
            'amount' => 15000,
            'total_amount' => 15000,
            'period_month' => 9,
            'period_year' => 2026,
        ]);
        $this->assertSame(1, Invoice::where('tenant_id', $tenantUser->id)
            ->where('unit_id', $unit->id)
            ->where('billing_type', 'DEPOSIT')
            ->count());

        $billing->syncMonthlyRentForTenantUnit($tenantUser->id, $unit->id, Carbon::parse('2026-10-01'));

        $this->assertSame(1, Invoice::where('tenant_id', $tenantUser->id)
            ->where('unit_id', $unit->id)
            ->where('billing_type', 'DEPOSIT')
            ->count());
        $this->assertDatabaseHas('invoices', [
            'tenant_id' => $tenantUser->id,
            'unit_id' => $unit->id,
            'billing_type' => 'RENT',
            'period_month' => 10,
            'period_year' => 2026,
            'amount' => 15000,
            'total_amount' => 15000,
        ]);
    }
}

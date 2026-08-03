<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Services\LandlordSubscriptionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DemoAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $enabled = (bool) env('DEMO_ACCOUNTS_ENABLED', app()->environment('local', 'testing'));
        if (!$enabled) {
            return;
        }

        $tenantRole = Role::where('name', 'TENANT')->first();
        $landlordRole = Role::where('name', 'LANDLORD')->first();
        $adminRole = Role::where('name', 'ADMIN')->first();

        $password = env('DEMO_ACCOUNT_PASSWORD', 'Demo@1234');

        $tenant = User::updateOrCreate(
            ['email' => env('DEMO_TENANT_EMAIL', 'demo.tenant@starmaxltd.com')],
            [
                'name' => 'Demo Tenant',
                'first_name' => 'Demo',
                'last_name' => 'Tenant',
                'password' => Hash::make($password),
                'role_id' => $tenantRole?->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $landlord = User::updateOrCreate(
            ['email' => env('DEMO_LANDLORD_EMAIL', 'demo.landlord@starmaxltd.com')],
            [
                'name' => 'Demo Landlord',
                'first_name' => 'Demo',
                'last_name' => 'Landlord',
                'password' => Hash::make($password),
                'role_id' => $landlordRole?->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => env('DEMO_ADMIN_EMAIL', 'demo.admin@starmaxltd.com')],
            [
                'name' => 'Demo Admin',
                'first_name' => 'Demo',
                'last_name' => 'Admin',
                'password' => Hash::make($password),
                'role_id' => $adminRole?->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        if ($landlord->role?->name === 'LANDLORD') {
            app(LandlordSubscriptionService::class)->initializeTrial($landlord);
        }

        if (
            $tenant->role?->name === 'TENANT'
            && Schema::hasColumn('users', 'requires_subscription')
            && Schema::hasColumn('users', 'billing_status')
        ) {
            $tenant->update([
                'requires_subscription' => false,
                'billing_status' => 'not_required',
            ]);
        }
    }
}

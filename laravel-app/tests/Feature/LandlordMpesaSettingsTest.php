<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandlordMpesaSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_landlord_can_save_daraja_payment_settings(): void
    {
        $role = Role::firstOrCreate(['name' => 'LANDLORD'], ['description' => 'Property owner/manager']);

        $landlord = User::create([
            'name' => 'Jane Landlord',
            'first_name' => 'Jane',
            'last_name' => 'Landlord',
            'email' => 'jane@example.com',
            'phone_number' => '+254712345678',
            'password' => bcrypt('Secret123!'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($landlord)->put(route('admin.settings.payment'), [
            'payment_type' => 'PAYBILL',
            'paybill_number' => '123456',
            'account_reference' => 'STARMAX',
            'business_name' => 'Starmax Ltd',
            'short_code_note' => 'Primary rent collection account',
        ]);

        $response->assertRedirect(route('admin.settings.index'));

        $landlord->refresh();
        $settings = $landlord->app_settings ?? [];

        $this->assertSame('PAYBILL', $settings['paymentSettings']['payment_type'] ?? null);
        $this->assertSame('123456', $settings['paymentSettings']['paybill_number'] ?? null);
        $this->assertSame('STARMAX', $settings['paymentSettings']['account_reference'] ?? null);
    }
}

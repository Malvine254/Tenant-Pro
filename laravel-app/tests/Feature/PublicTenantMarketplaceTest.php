<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PublicTenantMarketplaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_publishable_available_properties_appear_publicly(): void
    {
        $activeLandlord = $this->landlord();
        $suspendedLandlord = $this->landlord(['is_active' => false]);

        $visible = $this->property($activeLandlord, 'Westlands Court', true, 'AVAILABLE');
        $this->property($activeLandlord, 'Private Court', false, 'AVAILABLE');
        $this->property($activeLandlord, 'Fully Occupied Court', true, 'OCCUPIED');
        $this->property($suspendedLandlord, 'Suspended Listing', true, 'AVAILABLE');

        $this->get(route('marketplace.index'))
            ->assertOk()
            ->assertSee('Westlands Court')
            ->assertDontSee('Private Court')
            ->assertDontSee('Fully Occupied Court')
            ->assertDontSee('Suspended Listing');

        $this->get(route('marketplace.show', $visible))->assertOk();
    }

    public function test_viewing_enquiry_is_stored_without_exposing_landlord_contact_details(): void
    {
        Mail::fake();
        $landlord = $this->landlord(['email' => 'manager@example.test']);
        $property = $this->property($landlord, 'Kilimani Homes', true, 'AVAILABLE');
        $unit = $property->units()->firstOrFail();

        $this->post(route('marketplace.enquiries.store', $property), [
            'name' => 'Amina Tenant',
            'phone_number' => '0712345678',
            'unit_id' => $unit->id,
            'message' => 'I would like to view this Saturday.',
        ])->assertRedirect()->assertSessionHas('marketplace_success');

        $this->assertDatabaseHas('property_enquiries', [
            'property_id' => $property->id,
            'unit_id' => $unit->id,
            'name' => 'Amina Tenant',
            'phone_number' => '0712345678',
            'status' => 'NEW',
        ]);

        $this->get(route('marketplace.show', $property))
            ->assertOk()
            ->assertDontSee('manager@example.test');
    }

    private function landlord(array $attributes = []): User
    {
        $role = Role::firstOrCreate(['name' => 'LANDLORD']);

        return User::factory()->create(array_merge([
            'role_id' => $role->id,
            'is_active' => true,
            'requires_subscription' => false,
        ], $attributes));
    }

    private function property(User $landlord, string $name, bool $public, string $unitStatus): Property
    {
        $property = Property::create([
            'landlord_id' => $landlord->id,
            'name' => $name,
            'description' => 'A well-managed home.',
            'cover_image_url' => 'https://example.test/home.jpg',
            'address_line' => 'Ring Road',
            'city' => 'Nairobi',
            'country' => 'Kenya',
            'is_publicly_listed' => $public,
            'published_at' => $public ? now() : null,
        ]);

        Unit::create([
            'property_id' => $property->id,
            'unit_number' => 'A1',
            'rent_amount' => 25000,
            'status' => $unitStatus,
        ]);

        return $property;
    }
}

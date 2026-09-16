<?php

namespace Tests\Feature;

use App\Models\{ListingReport, MaintenanceRequest, Property, Role, Tenant, Unit, User};
use App\Services\MarketplaceUnitDetails;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MarketplaceEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    private function manager(string $role = 'LANDLORD'): User
    {
        return User::factory()->create(['role_id' => Role::firstOrCreate(['name' => $role])->id, 'first_name' => 'Test', 'last_name' => 'Manager', 'phone_number' => fake()->unique()->numerify('07########'), 'is_active' => true, 'requires_subscription' => false]);
    }

    private function home(?User $manager = null): Unit
    {
        $property = Property::create(['landlord_id' => ($manager ?? $this->manager())->id, 'name' => 'Garden Court', 'address_line' => 'Test Road', 'city' => 'Nairobi', 'neighbourhood' => 'Kilimani', 'area_notes' => 'Ask the manager about the nearby bus stop and grocery shops.', 'is_publicly_listed' => true, 'published_at' => now(), 'billing_settings' => ['water_monthly_fee' => 500, 'garbage_monthly_fee' => 200]]);
        return $property->units()->create(['unit_number' => 'A1', 'rent_amount' => 25000, 'status' => 'AVAILABLE']);
    }

    public function test_costs_distinguish_unknown_fees_from_zero_and_use_unit_utility_overrides(): void
    {
        $unit = $this->home();
        $this->assertFalse($unit->moveInCosts()['complete']);
        $this->assertSame(25700, (int) $unit->moveInCosts()['total']);
        $unit->update(['deposit_amount' => 25000, 'service_charge' => 0, 'other_move_in_cost' => 0, 'billing_overrides' => ['water_monthly_fee' => 0]]);
        $costs = $unit->fresh()->moveInCosts();
        $this->assertTrue($costs['complete']);
        $this->assertEquals(50200, $costs['total']);
        $this->get(route('marketplace.show', $unit->property))->assertOk()->assertSee('Total listed move-in cost')->assertSee('50,200.00');
    }

    public function test_manager_can_edit_facts_confirm_availability_and_manage_photos(): void
    {
        Storage::fake('public');
        $manager = $this->manager();
        $unit = $this->home($manager);
        $payload = ['unit_number' => 'A1', 'rent_amount' => 25000, 'status' => 'AVAILABLE', 'bathrooms' => 2, 'deposit_amount' => 20000, 'service_charge' => 1000, 'other_move_in_cost' => 0, 'amenities' => ['Parking'], 'available_from' => '2026-10-01', 'confirm_availability' => 1,
            'photos' => [UploadedFile::fake()->createWithContent('room.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aD1cAAAAASUVORK5CYII='))],
            'interior_photos' => ['kitchen' => [UploadedFile::fake()->createWithContent('kitchen.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aD1cAAAAASUVORK5CYII='))]]];
        $url = route('admin.properties.units.update', [$unit->property, $unit]);
        $this->actingAs($manager)->put($url, $payload)->assertSessionHasNoErrors()->assertRedirect();
        $unit->refresh();
        $this->assertSame(2, $unit->bathrooms);
        $this->assertSame(['Parking'], $unit->amenities);
        $this->assertNotNull($unit->availability_confirmed_at);
        $this->assertCount(1, $unit->image_urls);
        $this->assertSame('Kitchen', $unit->interior_gallery[0]['label']);
        $unitPhotoPath = substr($unit->image_urls[0], strlen('/storage/'));
        $interiorPhotoPath = substr($unit->interior_gallery[0]['url'], strlen('/storage/'));
        Storage::disk('public')->assertExists($unitPhotoPath);
        Storage::disk('public')->assertExists($interiorPhotoPath);
        $this->actingAs($this->manager())->put($url, $payload)->assertForbidden();
        unset($payload['photos'], $payload['interior_photos'], $payload['confirm_availability']);
        $payload['remove_photos'] = [0];
        $payload['remove_interior_photos'] = [0];
        $payload['status'] = 'OCCUPIED';
        $this->actingAs($manager)->put($url, $payload)->assertSessionHasNoErrors();
        $this->assertNull($unit->fresh()->availability_confirmed_at);
        $this->assertSame([], $unit->fresh()->image_urls);
        $this->assertSame([], $unit->fresh()->interior_gallery);
        Storage::disk('public')->assertMissing($unitPhotoPath);
        Storage::disk('public')->assertMissing($interiorPhotoPath);
    }

    public function test_tenant_profile_includes_unit_cover_and_labeled_interior_gallery(): void
    {
        config(['deployment.mobile_api_key' => 'test-mobile-key']);
        $unit = $this->home();
        $unit->property->update(['cover_image_url' => '/storage/properties/garden-court.jpg']);
        $unit->update([
            'bedrooms' => 2,
            'image_urls' => ['/storage/unit-photos/a1.jpg'],
            'interior_gallery' => [[
                'area' => 'kitchen',
                'label' => 'Kitchen',
                'url' => '/storage/unit-interiors/kitchen.jpg',
            ]],
        ]);
        $tenant = User::factory()->create([
            'role_id' => Role::firstOrCreate(['name' => 'TENANT'])->id,
            'is_active' => true,
        ]);
        Tenant::create([
            'user_id' => $tenant->id,
            'unit_id' => $unit->id,
            'move_in_date' => now()->toDateString(),
            'is_active' => true,
        ]);
        Sanctum::actingAs($tenant);

        $this->withHeader('X-Mobile-App-Key', 'test-mobile-key')
            ->getJson('/api/users/me/profile')
            ->assertOk()
            ->assertJsonPath('tenantProfiles.0.unit.bedrooms', 2)
            ->assertJsonPath('tenantProfiles.0.unit.displayImageUrl', '/storage/unit-photos/a1.jpg')
            ->assertJsonPath('tenantProfiles.0.unit.property.coverImageUrl', '/storage/properties/garden-court.jpg')
            ->assertJsonPath('tenantProfiles.0.unit.interiorGallery.0.area', 'kitchen')
            ->assertJsonPath('tenantProfiles.0.unit.interiorGallery.0.label', 'Kitchen')
            ->assertJsonPath('tenantProfiles.0.unit.interiorGallery.0.url', '/storage/unit-interiors/kitchen.jpg');
    }

    public function test_offline_maintenance_replay_is_idempotent(): void
    {
        config(['deployment.mobile_api_key' => 'test-mobile-key']);
        $unit = $this->home();
        $tenant = User::factory()->create([
            'role_id' => Role::firstOrCreate(['name' => 'TENANT'])->id,
            'is_active' => true,
        ]);
        Tenant::create([
            'user_id' => $tenant->id,
            'unit_id' => $unit->id,
            'move_in_date' => now()->toDateString(),
            'is_active' => true,
        ]);
        Sanctum::actingAs($tenant);
        $payload = [
            'title' => 'Leaking kitchen tap',
            'description' => 'The kitchen tap has continued leaking overnight.',
            'priority' => 'MEDIUM',
            'clientRequestId' => fake()->uuid(),
        ];

        $this->withHeader('X-Mobile-App-Key', 'test-mobile-key')->postJson('/api/maintenance', $payload)->assertCreated();
        $this->withHeader('X-Mobile-App-Key', 'test-mobile-key')->postJson('/api/maintenance', $payload)->assertCreated();

        $this->assertSame(1, MaintenanceRequest::where('client_request_id', $payload['clientRequestId'])->count());
    }

    public function test_reports_are_private_reviewable_and_restricted_to_public_properties(): void
    {
        $unit = $this->home();
        $payload = ['reason' => 'price', 'details' => 'The requested deposit differs from the listing.', 'report_email' => 'private@example.test'];
        $this->post(route('marketplace.reports.store', $unit->property), $payload)->assertSessionHas('report_success');
        $report = ListingReport::firstOrFail();
        $this->get(route('marketplace.show', $unit->property))->assertDontSee('private@example.test');
        $this->actingAs($unit->property->landlord)->get(route('admin.listing-reports.index'))->assertForbidden();
        $this->actingAs($this->manager('ADMIN'))->get(route('admin.listing-reports.index'))->assertOk()->assertSee('private@example.test');
        $this->patch(route('admin.listing-reports.resolve', $report), ['status' => 'RESOLVED'])->assertSessionHasNoErrors();
        $this->assertNotNull($report->fresh()->resolved_at);
        $unit->property->update(['is_publicly_listed' => false]);
        $this->post(route('marketplace.reports.store', $unit->property), $payload)->assertNotFound();
    }

    public function test_saved_comparisons_never_expose_private_or_unavailable_units(): void
    {
        $visible = $this->home();
        $hidden = $this->home();
        $hidden->property->update(['name' => 'Secret Court', 'is_publicly_listed' => false]);
        $occupied = $this->home();
        $occupied->update(['status' => 'OCCUPIED']);
        $this->get(route('marketplace.saved', ['units' => [$visible->id, $hidden->id, $occupied->id]]))->assertOk()->assertSee('Garden Court')->assertDontSee('Secret Court')->assertSee('2 selected homes')->assertSee('noindex,follow');
        $this->getJson(route('marketplace.saved', ['units' => array_fill(0, 13, $visible->id)]))->assertUnprocessable();
    }

    public function test_neighbourhood_pages_and_sitemap_use_only_published_local_notes(): void
    {
        $unit = $this->home();
        $this->get(route('marketplace.neighbourhood', 'nairobi--kilimani'))->assertOk()->assertSee('nearby bus stop')->assertSee('Garden Court');
        $this->assertStringContainsString('/neighbourhoods/nairobi--kilimani', $this->get('/sitemap.xml')->streamedContent());
        $unit->property->update(['area_notes' => null]);
        $this->get(route('marketplace.neighbourhood', 'nairobi--kilimani'))->assertOk()->assertSee('noindex,follow');
        $unit->property->update(['is_publicly_listed' => false]);
        $this->get(route('marketplace.neighbourhood', 'nairobi--kilimani'))->assertNotFound();
    }
}

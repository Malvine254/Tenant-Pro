<?php

namespace Tests\Feature;

use App\Models\{ListingReport, Property, Role, Unit, User};
use App\Services\MarketplaceUnitDetails;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
            'photos' => [UploadedFile::fake()->createWithContent('room.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aD1cAAAAASUVORK5CYII='))]];
        $url = route('admin.properties.units.update', [$unit->property, $unit]);
        $this->actingAs($manager)->put($url, $payload)->assertSessionHasNoErrors()->assertRedirect();
        $unit->refresh();
        $this->assertSame(2, $unit->bathrooms);
        $this->assertSame(['Parking'], $unit->amenities);
        $this->assertNotNull($unit->availability_confirmed_at);
        $this->assertCount(1, $unit->image_urls);
        Storage::disk('public')->assertExists(substr($unit->image_urls[0], strlen('/storage/')));
        $this->actingAs($this->manager())->put($url, $payload)->assertForbidden();
        unset($payload['photos'], $payload['confirm_availability']);
        $payload['remove_photos'] = [0];
        $payload['status'] = 'OCCUPIED';
        $this->actingAs($manager)->put($url, $payload)->assertSessionHasNoErrors();
        $this->assertNull($unit->fresh()->availability_confirmed_at);
        $this->assertSame([], $unit->fresh()->image_urls);
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

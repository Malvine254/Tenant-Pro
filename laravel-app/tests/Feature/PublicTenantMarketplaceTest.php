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

    public function test_root_redirects_to_homes_with_search_preserved(): void
    {
        $this->get('/?location=Nairobi')->assertStatus(301)
            ->assertRedirect(route('marketplace.index', ['location' => 'Nairobi']));
    }

    public function test_rent_and_bedroom_filters_must_match_the_same_unit(): void
    {
        $property = $this->property($this->landlord(), 'Mixed Units Court', true, 'AVAILABLE');
        $property->units()->first()->update(['bedrooms' => 1]);
        Unit::create(['property_id' => $property->id, 'unit_number' => 'B2', 'rent_amount' => 50000, 'bedrooms' => 2, 'status' => 'AVAILABLE']);
        $this->get('/homes?bedrooms=2&max_price=30000')->assertOk()->assertDontSee('Mixed Units Court');
        $this->get('/homes?bedrooms=2&max_price=60000')->assertOk()->assertSee('Mixed Units Court');
    }

    public function test_sharing_metadata_contains_public_facts_and_escapes_markup(): void
    {
        $property = $this->property($this->landlord(), 'Court </script><script>alert(1)</script>', true, 'AVAILABLE');
        $response = $this->get(route('marketplace.show', $property))->assertOk();
        $response->assertSee('property="og:image" content="https://example.test/home.jpg"', false)
            ->assertSee('From KSh 25,000/month.')
            ->assertSee('1 available.')
            ->assertSee('name="twitter:card" content="summary_large_image"', false)
            ->assertSee('WhatsApp')
            ->assertDontSee('</script><script>alert(1)</script>', false);
    }

    public function test_sitemap_only_contains_public_available_properties(): void
    {
        $landlord = $this->landlord();
        $visible = $this->property($landlord, 'Public home', true, 'AVAILABLE');
        $private = $this->property($landlord, 'Private home', false, 'AVAILABLE');
        $occupied = $this->property($landlord, 'Occupied home', true, 'OCCUPIED');
        $xml = $this->get('/sitemap.xml')->assertOk()->streamedContent();
        $this->assertStringContainsString(route('marketplace.show', $visible), $xml);
        $this->assertStringNotContainsString($private->id, $xml);
        $this->assertStringNotContainsString($occupied->id, $xml);
    }

    public function test_filtered_pages_are_noindex_and_pagination_has_its_own_canonical(): void
    {
        $this->get('/homes?bedrooms=0')->assertOk()
            ->assertSee('content="noindex,follow"', false);
        $this->get('/homes?page=2')->assertOk()
            ->assertSee('rel="canonical" href="'.route('marketplace.index', ['page' => 2]).'"', false);
        $this->get('/cookies')->assertOk()->assertSee('Essential only')->assertSee('Cookie settings');
    }

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

    public function test_listing_setup_request_is_sent_to_starmax_recipients(): void
    {
        Mail::fake();

        $this->post(route('marketplace.contact.submit'), [
            'service' => 'tenant',
            'topic' => 'listing',
            'name' => 'Amina Owner',
            'email' => 'amina@example.test',
            'message' => 'I have three available units in Nairobi.',
        ])->assertRedirect(route('marketplace.advertise'));

        Mail::assertSent(function ($mail) {
            return $mail->to[0]['address'] === 'malvine.owuor@starmaxltd.com'
                && collect($mail->cc)->pluck('address')->contains('info@starmaxltd.com')
                && $mail->subject === 'New marketplace listing setup request';
        });
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

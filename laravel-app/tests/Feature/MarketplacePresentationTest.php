<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class MarketplacePresentationTest extends TestCase
{
    public function test_entry_routes_and_cookie_controls(): void
    {
        $this->get('/?location=Nairobi')->assertStatus(301)->assertRedirect('/homes?location=Nairobi');
        $this->get('/cookies')->assertOk()->assertSee('Essential only')->assertSee('Allow preferences')->assertSee('Cookie settings');
        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap: '.route('marketplace.sitemap'));
    }

    public function test_property_preview_renders_public_details_and_safe_json(): void
    {
        $property = new Property(['name' => 'Court </script><script>alert(1)</script>', 'city' => 'Nairobi', 'cover_image_url' => '/images/home.jpg']);
        $property->id = 'public-home';
        $unit = new Unit(['unit_number' => 'A1', 'rent_amount' => 25000, 'bedrooms' => 2, 'status' => 'AVAILABLE']);
        $unit->id = 'public-unit';
        $property->setRelation('units', new Collection([$unit]));
        $unit->setRelation('property', $property);
        $html = view('tenant-marketplace.show', ['property' => $property, 'errors' => new \Illuminate\Support\ViewErrorBag])->render();
        $this->assertStringContainsString('From KSh 25,000/month.', $html);
        $this->assertStringContainsString('property="og:image" content="'.asset('images/home.jpg').'"', $html);
        $this->assertStringContainsString('name="twitter:card" content="summary_large_image"', $html);
        $this->assertStringNotContainsString('</script><script>alert(1)</script>', $html);
        preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $matches);
        $data = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame($property->name, $data['itemListElement'][2]['name']);
    }
}

<?php

namespace App\Http\Controllers\TenantMarketplace;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;

class MarketplaceController extends Controller
{
    public function home(Request $request)
    {
        return redirect()->route('marketplace.index', $request->query(), 301);
    }

    public function sitemap()
    {
        return response()->stream(function () {
            echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
            foreach (['marketplace.index', 'marketplace.how-it-works', 'marketplace.safety', 'marketplace.advertise', 'marketplace.neighbourhoods'] as $route) {
                echo '<url><loc>'.htmlspecialchars(route($route), ENT_XML1, 'UTF-8').'</loc></url>';
            }
            foreach (Property::query()->publiclyAvailable()->select('properties.id')->lazyById(500) as $property) {
                echo '<url><loc>'.htmlspecialchars(route('marketplace.show', $property), ENT_XML1, 'UTF-8').'</loc></url>';
            }
            foreach (DiscoveryController::areas() as $area) {
                if (Property::query()->publiclyAvailable()->where('city', $area->city)->where('neighbourhood', $area->neighbourhood)->whereNotNull('area_notes')->where('area_notes', '!=', '')->exists()) {
                    echo '<url><loc>'.htmlspecialchars(route('marketplace.neighbourhood', $area->neighbourhood_slug), ENT_XML1, 'UTF-8').'</loc></url>';
                }
            }
            echo '</urlset>';
        }, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:100'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0', \Illuminate\Validation\Rule::when($request->filled('min_price'), 'gte:min_price')],
            'bedrooms' => ['nullable', 'integer', 'min:0', 'max:10'],
            'sort' => ['nullable', 'in:newest,price_low,price_high'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $properties = $this->baseListingQuery($filters)
            ->when(filled($filters['q'] ?? null), function (Builder $query) use ($filters) {
                $term = '%'.trim($filters['q']).'%';
                $query->where(fn (Builder $search) => $search
                    ->where('name', 'like', $term)
                    ->orWhere('description', 'like', $term)
                    ->orWhere('address_line', 'like', $term)
                    ->orWhere('city', 'like', $term)
                    ->orWhere('state', 'like', $term)
                    ->orWhere('neighbourhood', 'like', $term));
            })
            ->when(filled($filters['location'] ?? null), function (Builder $query) use ($filters) {
                $location = trim($filters['location']);
                $query->where(fn (Builder $where) => $where
                    ->where('city', $location)
                    ->orWhere('state', $location));
            })
            ->when(isset($filters['min_price']) || isset($filters['max_price']), function (Builder $query) use ($filters) {
                $query->whereHas('units', fn (Builder $units) => $units
                    ->where('status', 'AVAILABLE')
                    ->when(isset($filters['min_price']), fn (Builder $range) => $range->where('rent_amount', '>=', $filters['min_price']))
                    ->when(isset($filters['max_price']), fn (Builder $range) => $range->where('rent_amount', '<=', $filters['max_price'])));
            })
            ->when(isset($filters['bedrooms']), fn (Builder $query) => $query->whereHas('units', fn (Builder $units) => $units
                ->where('status', 'AVAILABLE')
                ->where('bedrooms', $filters['bedrooms'])))
            ->when(($filters['sort'] ?? 'newest') === 'price_low', fn (Builder $query) => $query->orderBy('minimum_rent'))
            ->when(($filters['sort'] ?? 'newest') === 'price_high', fn (Builder $query) => $query->orderByDesc('maximum_rent'))
            ->when(($filters['sort'] ?? 'newest') === 'newest', fn (Builder $query) => $query->orderByDesc('published_at')->latest())
            ->paginate(12)
            ->withQueryString();

        $locations = Property::query()
            ->publiclyAvailable()
            ->select('city')
            ->distinct()
            ->orderBy('city')
            ->pluck('city');

        $heroPhotos = Property::query()
            ->publiclyAvailable()
            ->whereNotNull('cover_image_url')
            ->latest('published_at')
            ->limit(5)
            ->pluck('cover_image_url');

        return view('tenant-marketplace.index', compact('properties', 'locations', 'filters', 'heroPhotos'));
    }

    private function baseListingQuery(array $unitFilters = []): Builder
    {
        $availableUnits = function ($query) use ($unitFilters) {
            $query->where('status', 'AVAILABLE')
                ->when(isset($unitFilters['min_price']), fn ($range) => $range->where('rent_amount', '>=', $unitFilters['min_price']))
                ->when(isset($unitFilters['max_price']), fn ($range) => $range->where('rent_amount', '<=', $unitFilters['max_price']))
                ->when(isset($unitFilters['bedrooms']), fn ($bedrooms) => $bedrooms->where('bedrooms', $unitFilters['bedrooms']));
        };

        return Property::query()
            ->publiclyAvailable()
            ->whereHas('units', $availableUnits)
            ->with([
                'landlord:id,name',
                'units' => fn (HasMany $query) => $availableUnits($query
                    ->orderBy('rent_amount')
                    ->orderBy('unit_number')),
            ])
            ->withMin(['units as minimum_rent' => $availableUnits], 'rent_amount')
            ->withMax(['units as maximum_rent' => $availableUnits], 'rent_amount')
            ->withMin(['units as minimum_bedrooms' => fn (Builder $query) => $availableUnits($query->whereNotNull('bedrooms'))], 'bedrooms')
            ->withMax(['units as maximum_bedrooms' => fn (Builder $query) => $availableUnits($query->whereNotNull('bedrooms'))], 'bedrooms')
            ->withCount(['units as available_units_count' => $availableUnits]);
    }

    public function show(Property $property)
    {
        abort_unless(Property::query()->publiclyAvailable()->whereKey($property->id)->exists(), 404);

        $property->load([
            'landlord:id,name',
            'units' => fn (HasMany $query) => $query
                ->where('status', 'AVAILABLE')
                ->orderBy('rent_amount')
                ->orderBy('unit_number'),
        ]);

        $property->units->each(fn ($unit) => $unit->setRelation('property', $property));
        return view('tenant-marketplace.show', compact('property'));
    }
}

<?php

namespace App\Http\Controllers\TenantMarketplace;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class MarketplaceController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:100'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0', 'gte:min_price'],
            'sort' => ['nullable', 'in:newest,price_low,price_high'],
        ]);

        $properties = Property::query()
            ->publiclyAvailable()
            ->with([
                'landlord:id,name',
                'units' => fn (Builder $query) => $query
                    ->where('status', 'AVAILABLE')
                    ->orderBy('rent_amount')
                    ->orderBy('unit_number'),
            ])
            ->withMin(['units as minimum_rent' => fn (Builder $query) => $query->where('status', 'AVAILABLE')], 'rent_amount')
            ->withMax(['units as maximum_rent' => fn (Builder $query) => $query->where('status', 'AVAILABLE')], 'rent_amount')
            ->withCount(['units as available_units_count' => fn (Builder $query) => $query->where('status', 'AVAILABLE')])
            ->when(filled($filters['q'] ?? null), function (Builder $query) use ($filters) {
                $term = '%'.trim($filters['q']).'%';
                $query->where(fn (Builder $search) => $search
                    ->where('name', 'like', $term)
                    ->orWhere('description', 'like', $term)
                    ->orWhere('address_line', 'like', $term)
                    ->orWhere('city', 'like', $term)
                    ->orWhere('state', 'like', $term));
            })
            ->when(filled($filters['location'] ?? null), function (Builder $query) use ($filters) {
                $location = trim($filters['location']);
                $query->where(fn (Builder $where) => $where
                    ->where('city', $location)
                    ->orWhere('state', $location));
            })
            ->when(isset($filters['min_price']), fn (Builder $query) => $query->whereHas(
                'units', fn (Builder $units) => $units->where('status', 'AVAILABLE')->where('rent_amount', '>=', $filters['min_price'])
            ))
            ->when(isset($filters['max_price']), fn (Builder $query) => $query->whereHas(
                'units', fn (Builder $units) => $units->where('status', 'AVAILABLE')->where('rent_amount', '<=', $filters['max_price'])
            ))
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

        $heroImageUrl = Property::query()
            ->publiclyAvailable()
            ->whereNotNull('cover_image_url')
            ->latest('published_at')
            ->value('cover_image_url');

        return view('tenant-marketplace.index', compact('properties', 'locations', 'filters', 'heroImageUrl'));
    }

    public function show(Property $property)
    {
        abort_unless(Property::query()->publiclyAvailable()->whereKey($property->id)->exists(), 404);

        $property->load([
            'landlord:id,name',
            'units' => fn (Builder $query) => $query
                ->where('status', 'AVAILABLE')
                ->orderBy('rent_amount')
                ->orderBy('unit_number'),
        ]);

        return view('tenant-marketplace.show', compact('property'));
    }
}

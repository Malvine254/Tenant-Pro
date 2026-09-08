<?php

namespace App\Http\Controllers\TenantMarketplace;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Unit;
use Illuminate\Http\Request;

class DiscoveryController extends Controller
{
    public static function areas()
    {
        return Property::query()->publiclyAvailable()->whereNotNull('neighbourhood')->where('neighbourhood', '!=', '')
            ->select('city', 'neighbourhood')->distinct()->orderBy('city')->orderBy('neighbourhood')->get()
            ->unique('neighbourhood_slug');
    }

    public function neighbourhoods()
    {
        return view('tenant-marketplace.neighbourhoods', ['areas' => self::areas()]);
    }

    public function neighbourhood(Request $request, string $slug)
    {
        $request->validate(['page' => 'nullable|integer|min:1']);
        $area = self::areas()->firstWhere('neighbourhood_slug', $slug);
        abort_unless($area, 404);
        $query = Property::query()->publiclyAvailable()->where('city', $area->city)->where('neighbourhood', $area->neighbourhood);
        $notes = (clone $query)->whereNotNull('area_notes')->where('area_notes', '!=', '')->latest('updated_at')->limit(6)->get(['id', 'name', 'area_notes', 'updated_at']);
        $properties = $query->with(['units' => fn ($q) => $q->where('status', 'AVAILABLE')->orderBy('rent_amount')])
            ->withCount(['units as available_units_count' => fn ($q) => $q->where('status', 'AVAILABLE')])
            ->withMin(['units as minimum_rent' => fn ($q) => $q->where('status', 'AVAILABLE')], 'rent_amount')
            ->latest('published_at')->paginate(12);
        return view('tenant-marketplace.neighbourhood', compact('area', 'properties', 'notes'));
    }

    public function saved(Request $request)
    {
        $data = $request->validate(['units' => 'nullable|array|max:12', 'units.*' => 'required|uuid|distinct', 'compare' => 'nullable|array|max:4', 'compare.*' => 'required|uuid|distinct']);
        $ids = $data['units'] ?? [];
        $units = Unit::query()->whereIn('id', $ids)->where('status', 'AVAILABLE')
            ->whereHas('property', fn ($q) => $q->publiclyAvailable())->with('property')->get()
            ->sortBy(fn ($unit) => array_search($unit->id, $ids, true))->values();
        $compared = isset($data['compare']) ? $units->whereIn('id', $data['compare'])->values() : $units->take(4);
        return view('tenant-marketplace.saved', ['units' => $units, 'compared' => $compared, 'missingCount' => count($ids) - $units->count()]);
    }
}

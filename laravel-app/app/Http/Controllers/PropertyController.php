<?php

namespace App\Http\Controllers;

use App\Models\Property;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    public function index(Request $request)
    {
        $query = Property::with(['landlord', 'units'])
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('city', 'like', "%{$request->search}%"));
        return response()->json($query->paginate(15));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'landlord_id' => 'required|uuid|exists:users,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cover_image_url' => 'nullable|url',
            'address_line' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
        ]);
        $property = Property::create($data);
        return response()->json($property->load('landlord'), 201);
    }

    public function show(Property $property)
    {
        return response()->json($property->load(['landlord', 'units']));
    }

    public function update(Request $request, Property $property)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'cover_image_url' => 'nullable|url',
            'address_line' => 'sometimes|string|max:255',
            'city' => 'sometimes|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
        ]);
        $property->update($data);
        return response()->json($property->load('landlord'));
    }

    public function destroy(Property $property)
    {
        $property->delete();
        return response()->json(null, 204);
    }
}

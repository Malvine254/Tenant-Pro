<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Unit;
use Illuminate\Http\Request;

class PropertyAdminController extends Controller
{
    public function index()
    {
        $properties = Property::with(['landlord', 'units'])->latest()->paginate(15);
        return view('admin.properties.index', compact('properties'));
    }

    public function create()
    {
        $landlords = \App\Models\User::whereHas('role', fn($q) => $q->where('name', 'LANDLORD'))->get();
        return view('admin.properties.create', compact('landlords'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'landlord_id' => 'required|uuid|exists:users,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'address_line' => 'required|string',
            'city' => 'required|string',
            'state' => 'nullable|string',
            'country' => 'nullable|string',
        ]);
        Property::create($data);
        return redirect()->route('admin.properties.index')->with('success', 'Property created.');
    }

    public function show(Property $property)
    {
        $property->load(['landlord', 'units.tenant.user']);
        return view('admin.properties.show', compact('property'));
    }

    public function edit(Property $property)
    {
        $landlords = \App\Models\User::whereHas('role', fn($q) => $q->where('name', 'LANDLORD'))->get();
        return view('admin.properties.edit', compact('property', 'landlords'));
    }

    public function update(Request $request, Property $property)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'address_line' => 'required|string',
            'city' => 'required|string',
            'state' => 'nullable|string',
            'country' => 'nullable|string',
        ]);
        $property->update($data);
        return redirect()->route('admin.properties.show', $property)->with('success', 'Property updated.');
    }

    public function destroy(Property $property)
    {
        $property->delete();
        return redirect()->route('admin.properties.index')->with('success', 'Property deleted.');
    }
}

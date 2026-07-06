<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\Request;

class PropertyAdminController extends Controller
{
    public function index()
    {
        $user = request()->user();
        $properties = Property::with(['landlord', 'units'])
            ->when($this->isLandlord($user), fn($q) => $q->where('landlord_id', $user->id))
            ->latest()
            ->paginate(15);
        return view('admin.properties.index', compact('properties'));
    }

    public function create()
    {
        $user = request()->user();
        $landlords = User::whereHas('role', fn($q) => $q->where('name', 'LANDLORD'))
            ->when($this->isLandlord($user), fn($q) => $q->where('id', $user->id))
            ->orderBy('name')
            ->get();
        return view('admin.properties.create', compact('landlords'));
    }

    public function store(Request $request)
    {
        if ($this->isLandlord($request->user())) {
            $request->merge(['landlord_id' => $request->user()->id]);
        }

        $data = $request->validate([
            'landlord_id' => 'required|uuid|exists:users,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'address_line' => 'required|string',
            'city' => 'required|string',
            'state' => 'nullable|string',
            'country' => 'nullable|string',
        ]);
        $property = Property::create($data);

        return redirect()
            ->route('admin.properties.units.create', $property)
            ->with('success', 'Property created. Add its first unit below.');
    }

    public function show(Property $property)
    {
        $this->authorizeLandlordProperty($property);

        $property->load(['landlord', 'units.tenant.user']);
        return view('admin.properties.show', compact('property'));
    }

    public function edit(Property $property)
    {
        $this->authorizeLandlordProperty($property);
        $user = request()->user();
        $landlords = User::whereHas('role', fn($q) => $q->where('name', 'LANDLORD'))
            ->when($this->isLandlord($user), fn($q) => $q->where('id', $user->id))
            ->orderBy('name')
            ->get();
        return view('admin.properties.edit', compact('property', 'landlords'));
    }

    public function update(Request $request, Property $property)
    {
        $this->authorizeLandlordProperty($property);
        if ($this->isLandlord($request->user())) {
            $request->merge(['landlord_id' => $request->user()->id]);
        }

        $data = $request->validate([
            'landlord_id' => 'required|uuid|exists:users,id',
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
        $this->authorizeLandlordProperty($property);

        $property->delete();
        return redirect()->route('admin.properties.index')->with('success', 'Property deleted.');
    }

    private function isLandlord(?User $user): bool
    {
        return $user?->role?->name === 'LANDLORD';
    }

    private function authorizeLandlordProperty(Property $property): void
    {
        $user = request()->user();
        abort_if($this->isLandlord($user) && $property->landlord_id !== $user->id, 403);
    }
}

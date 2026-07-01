<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Unit;
use Illuminate\Http\Request;

class PropertyUnitAdminController extends Controller
{
    public function create(Property $property)
    {
        return view('admin.properties.units.create', compact('property'));
    }

    public function store(Request $request, Property $property)
    {
        $data = $request->validate([
            'unit_number' => 'required|string|max:50',
            'floor' => 'nullable|integer',
            'rent_amount' => 'required|numeric|min:0',
            'status' => 'required|in:AVAILABLE,OCCUPIED,UNDER_MAINTENANCE',
        ]);

        $property->units()->create($data);

        return redirect()
            ->route('admin.properties.show', $property)
            ->with('success', 'Unit added.');
    }

    public function edit(Property $property, Unit $unit)
    {
        abort_if($unit->property_id !== $property->id, 404);

        return view('admin.properties.units.edit', compact('property', 'unit'));
    }

    public function update(Request $request, Property $property, Unit $unit)
    {
        abort_if($unit->property_id !== $property->id, 404);

        $data = $request->validate([
            'unit_number' => 'required|string|max:50',
            'floor' => 'nullable|integer',
            'rent_amount' => 'required|numeric|min:0',
            'status' => 'required|in:AVAILABLE,OCCUPIED,UNDER_MAINTENANCE',
        ]);

        $unit->update($data);

        return redirect()
            ->route('admin.properties.show', $property)
            ->with('success', 'Unit updated.');
    }

    public function destroy(Property $property, Unit $unit)
    {
        abort_if($unit->property_id !== $property->id, 404);

        if ($unit->tenant()->exists()) {
            return back()->with('error', 'Cannot delete a unit with an assigned tenant.');
        }

        $unit->delete();

        return redirect()
            ->route('admin.properties.show', $property)
            ->with('success', 'Unit deleted.');
    }
}

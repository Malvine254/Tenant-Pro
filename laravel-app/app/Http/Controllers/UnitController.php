<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index(Request $request)
    {
        $query = Unit::with(['property', 'tenant.user'])
            ->when($request->property_id, fn($q) => $q->where('property_id', $request->property_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status));
        return response()->json($query->paginate(15));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'property_id' => 'required|uuid|exists:properties,id',
            'unit_number' => 'required|string|max:50',
            'floor' => 'nullable|integer',
            'rent_amount' => 'required|numeric|min:0',
            'status' => 'in:AVAILABLE,OCCUPIED,UNDER_MAINTENANCE',
            'image_urls' => 'nullable|array',
        ]);
        return response()->json(Unit::create($data)->load('property'), 201);
    }

    public function show(Unit $unit)
    {
        return response()->json($unit->load(['property', 'tenant.user', 'maintenanceRequests', 'invoices']));
    }

    public function update(Request $request, Unit $unit)
    {
        $data = $request->validate([
            'unit_number' => 'sometimes|string|max:50',
            'floor' => 'nullable|integer',
            'rent_amount' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|in:AVAILABLE,OCCUPIED,UNDER_MAINTENANCE',
            'image_urls' => 'nullable|array',
        ]);
        $unit->update($data);
        return response()->json($unit->load('property'));
    }

    public function destroy(Unit $unit)
    {
        $unit->delete();
        return response()->json(null, 204);
    }
}

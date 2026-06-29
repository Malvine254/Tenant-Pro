<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function index(Request $request)
    {
        $query = Tenant::with(['user.role', 'unit.property'])
            ->when($request->unit_id, fn($q) => $q->where('unit_id', $request->unit_id))
            ->when($request->is_active !== null, fn($q) => $q->where('is_active', $request->boolean('is_active')));
        return response()->json($query->paginate(15));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|uuid|exists:users,id|unique:tenants,user_id',
            'unit_id' => 'required|uuid|exists:units,id',
            'move_in_date' => 'required|date',
            'move_out_date' => 'nullable|date|after:move_in_date',
            'is_active' => 'boolean',
        ]);
        $tenant = Tenant::create($data);
        // Mark unit as occupied
        $tenant->unit->update(['status' => 'OCCUPIED']);
        return response()->json($tenant->load(['user', 'unit.property']), 201);
    }

    public function show(Tenant $tenant)
    {
        return response()->json($tenant->load(['user', 'unit.property', 'unit.invoices', 'unit.maintenanceRequests']));
    }

    public function update(Request $request, Tenant $tenant)
    {
        $data = $request->validate([
            'unit_id' => 'sometimes|uuid|exists:units,id',
            'move_in_date' => 'sometimes|date',
            'move_out_date' => 'nullable|date',
            'is_active' => 'sometimes|boolean',
        ]);
        $tenant->update($data);
        return response()->json($tenant->load(['user', 'unit']));
    }

    public function destroy(Tenant $tenant)
    {
        $tenant->unit->update(['status' => 'AVAILABLE']);
        $tenant->delete();
        return response()->json(null, 204);
    }
}

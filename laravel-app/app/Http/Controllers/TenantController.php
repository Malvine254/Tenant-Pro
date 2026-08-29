<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Tenant::with(['user.role', 'unit.property'])
            ->when($this->isTenant($user), fn($q) => $q->where('user_id', $user->id))
            ->when($user?->role?->name === 'LANDLORD', fn($q) => $q->whereHas('unit.property', fn($property) => $property->where('landlord_id', $user->id)))
            ->when($request->unit_id, fn($q) => $q->where('unit_id', $request->unit_id))
            ->when($request->is_active !== null, fn($q) => $q->where('is_active', $request->boolean('is_active')));
        return response()->json($query->paginate(15));
    }

    public function store(Request $request)
    {
        $this->requireManager($request->user());
        $data = $request->validate([
            'user_id' => 'required|uuid|exists:users,id',
            'unit_id' => 'required|uuid|exists:units,id',
            'move_in_date' => 'required|date',
            'move_out_date' => 'nullable|date|after:move_in_date',
            'is_active' => 'boolean',
        ]);
        $unit = \App\Models\Unit::findOrFail($data['unit_id']);
        $this->requireUnitManager($request->user(), $unit);
        abort_unless(\App\Models\User::whereKey($data['user_id'])->whereHas('role', fn($role) => $role->where('name', 'TENANT'))->exists(), 422, 'Selected user must be a tenant.');
        abort_if(($data['is_active'] ?? true) && $unit->tenant()->where('is_active', true)->exists(), 422, 'This unit already has an active tenant.');
        $tenant = Tenant::create($data);
        // Mark unit as occupied
        $tenant->unit->update(['status' => 'OCCUPIED']);
        return response()->json($tenant->load(['user', 'unit.property']), 201);
    }

    public function show(Tenant $tenant)
    {
        $user = request()->user();
        abort_if($this->isTenant($user) && $tenant->user_id !== $user->id, 403);
        if ($user?->role?->name === 'LANDLORD') $this->requireUnitManager($user, $tenant->unit);

        return response()->json($tenant->load(['user', 'unit.property', 'unit.invoices', 'unit.maintenanceRequests']));
    }

    public function update(Request $request, Tenant $tenant)
    {
        $previousUnit = $tenant->unit;
        $this->requireUnitManager($request->user(), $tenant->unit);
        $data = $request->validate([
            'unit_id' => 'sometimes|uuid|exists:units,id',
            'move_in_date' => 'sometimes|date',
            'move_out_date' => 'nullable|date',
            'is_active' => 'sometimes|boolean',
        ]);
        if (isset($data['unit_id'])) {
            $newUnit = \App\Models\Unit::findOrFail($data['unit_id']);
            $this->requireUnitManager($request->user(), $newUnit);
            abort_if($newUnit->tenant()->where('is_active', true)->whereKeyNot($tenant->id)->exists(), 422, 'This unit already has an active tenant.');
        }
        $tenant->update($data);
        if (isset($data['unit_id']) && $previousUnit->id !== $data['unit_id']) {
            $previousUnit->update(['status' => 'AVAILABLE']);
        }
        $tenant->unit()->update([
            'status' => $tenant->is_active ? 'OCCUPIED' : 'AVAILABLE',
        ]);
        return response()->json($tenant->load(['user', 'unit']));
    }

    public function destroy(Tenant $tenant)
    {
        $this->requireUnitManager(request()->user(), $tenant->unit);
        $tenant->unit->update(['status' => 'AVAILABLE']);
        $tenant->update([
            'is_active' => false,
            'move_out_date' => $tenant->move_out_date ?? now()->toDateString(),
        ]);
        return response()->json(null, 204);
    }

    private function isTenant($user): bool
    {
        return $user?->role?->name === 'TENANT';
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Unit::with(['property', 'tenant.user'])
            ->when($this->isTenant($user), fn($q) => $q->whereHas('tenant', fn($tenant) => $tenant->where('user_id', $user->id)))
            ->when($user?->role?->name === 'LANDLORD', fn($q) => $q->whereHas('property', fn($property) => $property->where('landlord_id', $user->id)))
            ->when($request->property_id, fn($q) => $q->where('property_id', $request->property_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status));
        return response()->json($query->paginate(15));
    }

    public function store(Request $request)
    {
        $this->requireManager($request->user());
        $data = $request->validate([
            'property_id' => 'required|uuid|exists:properties,id',
            'unit_number' => 'required|string|max:50',
            'floor' => 'nullable|integer',
            'rent_amount' => 'required|numeric|min:0',
            'status' => 'in:AVAILABLE,OCCUPIED,UNDER_MAINTENANCE',
            'image_urls' => 'nullable|array',
        ]);
        $property = \App\Models\Property::findOrFail($data['property_id']);
        $this->requirePropertyManager($request->user(), $property);
        return response()->json(Unit::create($data)->load('property'), 201);
    }

    public function show(Unit $unit)
    {
        $user = request()->user();
        abort_if($this->isTenant($user) && !$unit->tenant()->where('user_id', $user->id)->exists(), 403);
        if ($user?->role?->name === 'LANDLORD') $this->requireUnitManager($user, $unit);

        return response()->json($unit->load(['property', 'tenant.user', 'maintenanceRequests', 'invoices']));
    }

    public function update(Request $request, Unit $unit)
    {
        $this->requireUnitManager($request->user(), $unit);
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
        $this->requireUnitManager(request()->user(), $unit);
        abort_if(
            $unit->tenant()->exists() || $unit->invoices()->exists() || $unit->maintenanceRequests()->exists(),
            422,
            'Units with tenancy, invoice or maintenance history cannot be deleted.'
        );
        $unit->delete();
        return response()->json(null, 204);
    }

    private function isTenant($user): bool
    {
        return $user?->role?->name === 'TENANT';
    }
}

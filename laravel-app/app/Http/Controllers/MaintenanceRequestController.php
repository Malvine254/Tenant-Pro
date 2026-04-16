<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceRequest;
use Illuminate\Http\Request;

class MaintenanceRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = MaintenanceRequest::with(['unit.property', 'reportedBy', 'assignedTo'])
            ->when($request->unit_id, fn($q) => $q->where('unit_id', $request->unit_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->priority, fn($q) => $q->where('priority', $request->priority));
        return response()->json($query->latest()->paginate(15));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tenant_id' => 'required|uuid|exists:users,id',
            'unit_id' => 'required|uuid|exists:units,id',
            'reported_by_id' => 'required|uuid|exists:users,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'in:LOW,MEDIUM,HIGH,URGENT',
        ]);
        $data['status'] = 'OPEN';
        return response()->json(MaintenanceRequest::create($data)->load(['unit', 'reportedBy']), 201);
    }

    public function show(MaintenanceRequest $maintenanceRequest)
    {
        return response()->json($maintenanceRequest->load(['unit.property', 'tenant', 'reportedBy', 'assignedTo']));
    }

    public function update(Request $request, MaintenanceRequest $maintenanceRequest)
    {
        $data = $request->validate([
            'status' => 'sometimes|in:OPEN,IN_PROGRESS,RESOLVED,CLOSED',
            'priority' => 'sometimes|in:LOW,MEDIUM,HIGH,URGENT',
            'assigned_to_id' => 'nullable|uuid|exists:users,id',
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'resolved_at' => 'nullable|date',
        ]);
        if (isset($data['status']) && $data['status'] === 'RESOLVED' && empty($data['resolved_at'])) {
            $data['resolved_at'] = now();
        }
        $maintenanceRequest->update($data);
        return response()->json($maintenanceRequest->load(['unit', 'reportedBy', 'assignedTo']));
    }

    public function destroy(MaintenanceRequest $maintenanceRequest)
    {
        $maintenanceRequest->delete();
        return response()->json(null, 204);
    }
}

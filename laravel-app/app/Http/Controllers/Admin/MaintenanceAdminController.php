<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceRequest;
use Illuminate\Http\Request;

class MaintenanceAdminController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $requests = MaintenanceRequest::with(['unit.property', 'reportedBy', 'assignedTo'])
            ->when($user?->role?->name === 'LANDLORD', fn($q) => $q->whereHas('unit.property', fn($property) => $property->where('landlord_id', $user->id)))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->priority, fn($q) => $q->where('priority', $request->priority))
            ->latest()->paginate(15);
        return view('admin.maintenance.index', compact('requests'));
    }

    public function show(MaintenanceRequest $maintenanceRequest)
    {
        $user = request()->user();
        abort_if($user?->role?->name === 'LANDLORD' && $maintenanceRequest->unit?->property?->landlord_id !== $user->id, 403);

        $maintenanceRequest->load(['unit.property', 'tenant', 'reportedBy', 'assignedTo']);
        return view('admin.maintenance.show', compact('maintenanceRequest'));
    }

    public function update(Request $request, MaintenanceRequest $maintenanceRequest)
    {
        $user = $request->user();
        abort_if($user?->role?->name === 'LANDLORD' && $maintenanceRequest->unit?->property?->landlord_id !== $user->id, 403);

        $data = $request->validate([
            'status' => 'required|in:OPEN,IN_PROGRESS,RESOLVED,CLOSED',
            'assigned_to_id' => 'nullable|uuid|exists:users,id',
        ]);
        if ($data['status'] === 'RESOLVED') {
            $data['resolved_at'] = now();
        }
        $maintenanceRequest->update($data);
        return redirect()->route('admin.maintenance.show', $maintenanceRequest)->with('success', 'Request updated.');
    }
}

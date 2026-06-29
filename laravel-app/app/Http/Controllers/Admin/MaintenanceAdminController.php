<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceRequest;
use Illuminate\Http\Request;

class MaintenanceAdminController extends Controller
{
    public function index(Request $request)
    {
        $requests = MaintenanceRequest::with(['unit.property', 'reportedBy', 'assignedTo'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->priority, fn($q) => $q->where('priority', $request->priority))
            ->latest()->paginate(15);
        return view('admin.maintenance.index', compact('requests'));
    }

    public function show(MaintenanceRequest $maintenanceRequest)
    {
        $maintenanceRequest->load(['unit.property', 'tenant', 'reportedBy', 'assignedTo']);
        return view('admin.maintenance.show', compact('maintenanceRequest'));
    }

    public function update(Request $request, MaintenanceRequest $maintenanceRequest)
    {
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

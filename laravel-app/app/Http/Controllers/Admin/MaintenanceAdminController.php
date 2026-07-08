<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceRequest;
use App\Services\TenantAppNotificationService;
use App\Services\TenantEmailService;
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
            'status' => 'required|in:OPEN,ACKNOWLEDGED,ASSIGNED,IN_PROGRESS,WAITING_TENANT,RESOLVED,CLOSED,CANCELLED',
            'assigned_to_id' => 'nullable|uuid|exists:users,id',
        ]);
        $previousStatus = $maintenanceRequest->status;

        if ($data['status'] === 'RESOLVED') {
            $data['resolved_at'] = now();
        }
        $maintenanceRequest->update($data);

        $freshRequest = $maintenanceRequest->fresh(['tenant', 'unit.property', 'assignedTo']);
        $emailSent = app(TenantEmailService::class)->maintenanceUpdated($freshRequest, $previousStatus);
        app(TenantAppNotificationService::class)->notify(
            $freshRequest->tenant,
            'MAINTENANCE_UPDATED',
            'Maintenance updated',
            'Your maintenance request "'.$freshRequest->title.'" is now '.$freshRequest->status.'.',
            ['maintenance_request_id' => $freshRequest->id, 'status' => $freshRequest->status]
        );

        return redirect()
            ->route('admin.maintenance.show', $maintenanceRequest)
            ->with('success', 'Request updated.'.($emailSent ? ' Tenant was notified by email.' : ' Email notification could not be sent; check mail logs.'));
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceRequest;
use App\Models\Property;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MaintenanceAdminController extends Controller
{
    private const STATUSES = ['OPEN', 'IN_PROGRESS', 'RESOLVED', 'CLOSED'];

    private const PRIORITIES = ['LOW', 'MEDIUM', 'HIGH', 'URGENT'];

    private const TRANSITIONS = [
        'OPEN' => ['IN_PROGRESS', 'CLOSED'],
        'IN_PROGRESS' => ['RESOLVED', 'CLOSED'],
        'RESOLVED' => ['CLOSED'],
        'CLOSED' => [],
    ];

    public function index(Request $request)
    {
        $user = $request->user();
        $query = MaintenanceRequest::query()
            ->with(['unit.property', 'tenant', 'reportedBy', 'assignedTo'])
            ->when($this->isLandlord($user), fn ($q) => $q->whereHas(
                'unit.property',
                fn ($property) => $property->where('landlord_id', $user->landlordAccountId())
            ))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('priority'), fn ($q) => $q->where('priority', $request->string('priority')))
            ->when($request->filled('property_id'), fn ($q) => $q->whereHas(
                'unit',
                fn ($unit) => $unit->where('property_id', $request->string('property_id'))
            ))
            ->when($request->filled('assigned_to_id') && $request->input('assigned_to_id') !== 'unassigned', fn ($q) => $q->where('assigned_to_id', $request->string('assigned_to_id')))
            ->when($request->input('assigned_to_id') === 'unassigned', fn ($q) => $q->whereNull('assigned_to_id'))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = '%'.trim((string) $request->input('search')).'%';
                $q->where(function ($match) use ($search) {
                    $match->where('title', 'like', $search)
                        ->orWhere('description', 'like', $search)
                        ->orWhereHas('tenant', fn ($tenant) => $tenant
                            ->where('name', 'like', $search)
                            ->orWhere('email', 'like', $search))
                        ->orWhereHas('unit', fn ($unit) => $unit->where('unit_number', 'like', $search));
                });
            })
            ->latest();

        $requests = $query->paginate(20)->withQueryString();
        $properties = $this->managedProperties($user)->orderBy('name')->get();
        $caretakers = $this->managedCaretakers($user)->orderBy('name')->get();

        $counts = [
            'open' => (clone $query)->where('status', 'OPEN')->count(),
            'in_progress' => (clone $query)->where('status', 'IN_PROGRESS')->count(),
            'resolved' => (clone $query)->where('status', 'RESOLVED')->count(),
            'total' => (clone $query)->count(),
        ];

        return view('admin.maintenance.index', compact('requests', 'properties', 'caretakers', 'counts'));
    }

    public function show(MaintenanceRequest $maintenanceRequest)
    {
        $this->ensureManaged($maintenanceRequest, request()->user());
        $maintenanceRequest->load(['unit.property', 'tenant', 'reportedBy', 'assignedTo']);
        $caretakers = $this->managedCaretakers(request()->user())->orderBy('name')->get();
        $nextStatuses = self::TRANSITIONS[$maintenanceRequest->status] ?? [];

        return view('admin.maintenance.show', compact('maintenanceRequest', 'caretakers', 'nextStatuses'));
    }

    public function assignCaretaker(Request $request, MaintenanceRequest $maintenanceRequest)
    {
        $this->ensureManaged($maintenanceRequest, $request->user());
        $data = $request->validate([
            'assigned_to_id' => ['nullable', 'uuid'],
        ]);

        if (filled($data['assigned_to_id'] ?? null)) {
            $caretaker = $this->managedCaretakers($request->user())
                ->whereKey($data['assigned_to_id'])
                ->first();
            abort_unless($caretaker, 422, 'Select an active caretaker available to this account.');
        }

        $maintenanceRequest->update(['assigned_to_id' => $data['assigned_to_id'] ?? null]);

        return back()->with('success', filled($data['assigned_to_id'] ?? null)
            ? 'Maintenance request assigned to the selected caretaker.'
            : 'Maintenance request assignment cleared.');
    }

    public function updateStatus(Request $request, MaintenanceRequest $maintenanceRequest)
    {
        $this->ensureManaged($maintenanceRequest, $request->user());
        $data = $request->validate([
            'status' => ['required', Rule::in(self::STATUSES)],
        ]);
        $currentStatus = $maintenanceRequest->status;
        $nextStatus = $data['status'];

        abort_unless(in_array($nextStatus, self::TRANSITIONS[$currentStatus] ?? [], true), 422, "A request cannot move from {$currentStatus} to {$nextStatus}.");

        DB::transaction(function () use ($maintenanceRequest, $nextStatus) {
            $maintenanceRequest->update([
                'status' => $nextStatus,
                'resolved_at' => in_array($nextStatus, ['RESOLVED', 'CLOSED'], true)
                    ? ($maintenanceRequest->resolved_at ?? now())
                    : null,
            ]);
        });

        return back()->with('success', 'Maintenance request status updated to '.str_replace('_', ' ', $nextStatus).'.');
    }

    private function ensureManaged(MaintenanceRequest $maintenanceRequest, ?User $user): void
    {
        abort_unless($maintenanceRequest->unit && $maintenanceRequest->unit->property, 404);
        if ($this->isLandlord($user)) {
            abort_unless($maintenanceRequest->unit->property->landlord_id === $user->landlordAccountId(), 403);
        }
    }

    private function managedProperties(?User $user)
    {
        return Property::query()->when(
            $this->isLandlord($user),
            fn ($q) => $q->where('landlord_id', $user->landlordAccountId())
        );
    }

    private function managedCaretakers(?User $user)
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('role', fn ($role) => $role->where('name', 'CARETAKER'))
            ->when(
                $this->isLandlord($user),
                fn ($q) => $q->where(function ($query) use ($user) {
                    $query->where('managed_landlord_id', $user->landlordAccountId())
                        ->orWhereHas('properties', fn ($property) => $property->where('landlord_id', $user->landlordAccountId()));
                })
            );
    }

    private function isLandlord(?User $user): bool
    {
        return $user?->role?->name === 'LANDLORD';
    }
}

<?php

namespace App\Services\AI\Tools;

use App\Models\MaintenanceRequest;
use App\Models\User;

class GetMaintenanceRequestsTool implements ChatTool
{
    public function name(): string
    {
        return 'get_maintenance_requests';
    }

    public function description(): string
    {
        return "List the authenticated tenant's own maintenance requests with status and priority. "
            .'Use this for questions like "what is the status of my repair request".';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'status' => [
                    'type' => 'string',
                    'enum' => ['OPEN', 'IN_PROGRESS', 'RESOLVED', 'CLOSED'],
                    'description' => 'Optional filter by request status.',
                ],
            ],
            'required' => [],
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->role?->name === 'TENANT';
    }

    public function execute(array $arguments, User $user): array
    {
        $requests = MaintenanceRequest::with('unit')
            ->where('tenant_id', $user->id)
            ->when($arguments['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->limit(20)
            ->get();

        return [
            'ok' => true,
            'data' => [
                'maintenance_requests' => $requests->map(fn (MaintenanceRequest $request) => [
                    'id' => $request->id,
                    'title' => $request->title,
                    'priority' => $request->priority,
                    'status' => $request->status,
                    'created_at' => optional($request->created_at)->format('Y-m-d'),
                    'resolved_at' => optional($request->resolved_at)->format('Y-m-d'),
                ])->all(),
            ],
        ];
    }
}

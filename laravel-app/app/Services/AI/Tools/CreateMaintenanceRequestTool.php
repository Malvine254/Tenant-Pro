<?php

namespace App\Services\AI\Tools;

use App\Models\MaintenanceRequest;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * State-changing tool. Reuses the same validation and authorization rules as
 * MaintenanceRequestController::store() so the assistant cannot bypass business rules.
 */
class CreateMaintenanceRequestTool implements ChatTool
{
    public function name(): string
    {
        return 'create_maintenance_request';
    }

    public function description(): string
    {
        return 'Create a new maintenance request on behalf of the authenticated tenant for their active '
            .'unit. Only call this when the tenant has clearly described a problem and wants it logged, '
            .'not merely asked a question.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'title' => ['type' => 'string', 'description' => 'Short summary of the issue.'],
                'description' => ['type' => 'string', 'description' => 'Full description of the issue.'],
                'priority' => [
                    'type' => 'string',
                    'enum' => ['LOW', 'MEDIUM', 'HIGH', 'URGENT'],
                    'description' => 'Urgency of the issue. Default MEDIUM.',
                ],
            ],
            'required' => ['title', 'description'],
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->role?->name === 'TENANT';
    }

    public function execute(array $arguments, User $user): array
    {
        $tenancy = $user->tenancies()->where('is_active', true)->with('unit')->first();

        if (! $tenancy) {
            return ['ok' => false, 'error' => 'No active tenancy was found, so a maintenance request could not be created.'];
        }

        $title = trim((string) ($arguments['title'] ?? ''));
        $description = trim((string) ($arguments['description'] ?? ''));

        if ($title === '' || $description === '') {
            return ['ok' => false, 'error' => 'A title and description are required to create a maintenance request.'];
        }

        $priority = in_array($arguments['priority'] ?? null, ['LOW', 'MEDIUM', 'HIGH', 'URGENT'], true)
            ? $arguments['priority']
            : 'MEDIUM';

        $request = MaintenanceRequest::create([
            'tenant_id' => $user->id,
            'unit_id' => $tenancy->unit_id,
            'reported_by_id' => $user->id,
            'title' => Str::limit($title, 255, ''),
            'description' => $description,
            'priority' => $priority,
            'status' => 'OPEN',
            'client_request_id' => (string) Str::uuid(),
        ]);

        return [
            'ok' => true,
            'data' => [
                'maintenance_request_id' => $request->id,
                'title' => $request->title,
                'priority' => $request->priority,
                'status' => $request->status,
            ],
            'message' => 'Maintenance request created successfully.',
        ];
    }
}

<?php

namespace App\Services\AI\Tools;

use App\Models\User;

class GetTenancyDetailsTool implements ChatTool
{
    public function name(): string
    {
        return 'get_tenancy_details';
    }

    public function description(): string
    {
        return "Get the authenticated tenant's active tenancy details: property name, unit number, "
            ."rent amount, landlord name, and move-in date. Use this to answer questions about the "
            .'tenant\'s current rental, unit, or property.';
    }

    public function parameters(): array
    {
        return ['type' => 'object', 'properties' => new \stdClass(), 'required' => []];
    }

    public function authorize(User $user): bool
    {
        return $user->role?->name === 'TENANT';
    }

    public function execute(array $arguments, User $user): array
    {
        $tenancies = $user->tenancies()
            ->where('is_active', true)
            ->with('unit.property.landlord')
            ->get();

        if ($tenancies->isEmpty()) {
            return ['ok' => true, 'data' => ['tenancies' => []], 'message' => 'No active tenancy found for this user.'];
        }

        return [
            'ok' => true,
            'data' => [
                'tenancies' => $tenancies->map(fn ($tenancy) => [
                    'property_name' => $tenancy->unit?->property?->name,
                    'unit_number' => $tenancy->unit?->unit_number,
                    'rent_amount_formatted' => $tenancy->unit?->rent_amount_formatted,
                    'landlord_name' => $tenancy->unit?->property?->landlord?->name,
                    'move_in_date' => optional($tenancy->move_in_date)->format('Y-m-d'),
                ])->all(),
            ],
        ];
    }
}

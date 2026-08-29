<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\Unit;

abstract class Controller
{
    protected function requireManager($user): void
    {
        abort_unless(in_array($user?->role?->name, ['LANDLORD', 'ADMIN', 'SUPER_ADMIN'], true), 403);
    }

    protected function requirePropertyManager($user, Property $property): void
    {
        $this->requireManager($user);
        abort_if($user?->role?->name === 'LANDLORD' && $property->landlord_id !== $user->id, 403);
    }

    protected function requireUnitManager($user, Unit $unit): void
    {
        $unit->loadMissing('property');
        $this->requirePropertyManager($user, $unit->property);
    }
}

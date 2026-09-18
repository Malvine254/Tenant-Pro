<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignUuid('tenant_owner_landlord_id')
                ->nullable()
                ->after('managed_landlord_id')
                ->constrained('users')
                ->nullOnDelete();
        });

        User::query()
            ->whereNull('tenant_owner_landlord_id')
            ->whereHas('role', fn ($role) => $role->where('name', 'TENANT'))
            ->with(['tenancies.unit.property'])
            ->chunkById(100, function ($tenants) {
                foreach ($tenants as $tenant) {
                    /** @var User $tenant */
                    $ownerId = $tenant->tenancies
                        ->sortBy('move_in_date')
                        ->map(fn ($tenancy) => $tenancy->unit?->property?->landlord_id)
                        ->first(fn ($landlordId) => filled($landlordId));

                    if ($ownerId) {
                        $tenant->update(['tenant_owner_landlord_id' => $ownerId]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_owner_landlord_id']);
            $table->dropColumn('tenant_owner_landlord_id');
        });
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('support_conversations', function (Blueprint $table) {
            $table->uuid('property_id')->nullable()->after('landlord_user_id');
            $table->foreign('property_id')->references('id')->on('properties')->nullOnDelete();
            $table->index(['property_id', 'updated_at'], 'support_conversations_property_updated_idx');
        });

        DB::statement("UPDATE support_conversations sc
            SET sc.property_id = (
                SELECT u.property_id
                FROM tenants t
                INNER JOIN units u ON u.id = t.unit_id
                INNER JOIN properties p ON p.id = u.property_id
                WHERE t.user_id = sc.tenant_user_id
                  AND t.is_active = 1
                  AND (sc.landlord_user_id IS NULL OR p.landlord_id = sc.landlord_user_id)
                ORDER BY t.updated_at DESC, t.created_at DESC
                LIMIT 1
            )
            WHERE sc.property_id IS NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('support_conversations', function (Blueprint $table) {
            $table->dropIndex('support_conversations_property_updated_idx');
            $table->dropForeign(['property_id']);
            $table->dropColumn('property_id');
        });
    }
};

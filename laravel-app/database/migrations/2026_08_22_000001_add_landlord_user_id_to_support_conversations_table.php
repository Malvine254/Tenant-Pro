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
            $table->uuid('landlord_user_id')->nullable()->after('tenant_user_id');
            $table->foreign('landlord_user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['landlord_user_id', 'updated_at'], 'support_conversations_landlord_updated_idx');
        });

        // Backfill existing conversations so landlord inboxes are isolated immediately.
        DB::statement('UPDATE support_conversations
            SET landlord_user_id = (
                SELECT p.landlord_id FROM tenants t
                JOIN units u ON u.id = t.unit_id
                JOIN properties p ON p.id = u.property_id
                WHERE t.user_id = support_conversations.tenant_user_id AND t.is_active = 1
                ORDER BY t.updated_at DESC, t.created_at DESC LIMIT 1
            ) WHERE landlord_user_id IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('support_conversations', function (Blueprint $table) {
            $table->dropIndex('support_conversations_landlord_updated_idx');
            $table->dropForeign(['landlord_user_id']);
            $table->dropColumn('landlord_user_id');
        });
    }
};

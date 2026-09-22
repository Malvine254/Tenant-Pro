<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_messages', function (Blueprint $table) {
            $table->boolean('is_ai')->default(false)->after('is_from_tenant');
            $table->json('tool_calls')->nullable()->after('is_ai');
        });

        Schema::table('support_conversations', function (Blueprint $table) {
            $table->boolean('ai_enabled')->default(true)->after('is_open');
            $table->timestamp('escalated_at')->nullable()->after('ai_enabled');
            $table->string('escalation_reason', 500)->nullable()->after('escalated_at');
        });
    }

    public function down(): void
    {
        Schema::table('support_messages', function (Blueprint $table) {
            $table->dropColumn(['is_ai', 'tool_calls']);
        });

        Schema::table('support_conversations', function (Blueprint $table) {
            $table->dropColumn(['ai_enabled', 'escalated_at', 'escalation_reason']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignUuid('managed_landlord_id')
                ->nullable()
                ->after('role_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('team_invited_at')->nullable()->after('managed_landlord_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['managed_landlord_id']);
            $table->dropColumn(['managed_landlord_id', 'team_invited_at']);
        });
    }
};

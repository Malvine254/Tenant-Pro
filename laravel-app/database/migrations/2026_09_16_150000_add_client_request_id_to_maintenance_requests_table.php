<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->uuid('client_request_id')->nullable()->after('reported_by_id');
            $table->unique(['tenant_id', 'client_request_id']);
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'client_request_id']);
            $table->dropColumn('client_request_id');
        });
    }
};
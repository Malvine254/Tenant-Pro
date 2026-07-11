<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropUnique('tenants_user_id_unique');
            $table->unique(['user_id', 'unit_id'], 'tenants_user_unit_unique');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropUnique('tenants_user_unit_unique');
            $table->unique('user_id');
        });
    }
};

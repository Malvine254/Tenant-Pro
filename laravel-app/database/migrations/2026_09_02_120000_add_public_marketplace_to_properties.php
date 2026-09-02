<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->boolean('is_publicly_listed')->default(false)->after('billing_settings')->index();
            $table->timestamp('published_at')->nullable()->after('is_publicly_listed');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropIndex(['is_publicly_listed']);
            $table->dropColumn(['is_publicly_listed', 'published_at']);
        });
    }
};

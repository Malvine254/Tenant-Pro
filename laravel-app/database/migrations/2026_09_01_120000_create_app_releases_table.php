<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_releases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('platform', 20)->default('ANDROID');
            $table->string('version_name', 40);
            $table->unsignedBigInteger('version_code');
            $table->string('channel', 20)->default('PRODUCTION');
            $table->string('file_path');
            $table->string('file_name');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('checksum', 64)->nullable();
            $table->text('release_notes')->nullable();
            $table->boolean('is_current')->default(false);
            $table->boolean('is_mandatory')->default(false);
            $table->unsignedInteger('download_count')->default(0);
            $table->timestamp('notified_at')->nullable();
            $table->uuid('uploaded_by')->nullable();
            $table->timestamps();

            $table->unique(['platform', 'version_code']);
            $table->index(['platform', 'is_current']);
            $table->foreign('uploaded_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_releases');
    }
};

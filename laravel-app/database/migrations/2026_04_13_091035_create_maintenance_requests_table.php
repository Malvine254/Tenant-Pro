<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('maintenance_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('users')->onDelete('cascade');
            $table->uuid('unit_id');
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('cascade');
            $table->uuid('reported_by_id');
            $table->foreign('reported_by_id')->references('id')->on('users');
            $table->uuid('assigned_to_id')->nullable();
            $table->foreign('assigned_to_id')->references('id')->on('users')->onDelete('set null');
            $table->string('title');
            $table->text('description');
            $table->string('priority')->default('MEDIUM');
            $table->string('status')->default('OPEN');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_requests');
    }
};

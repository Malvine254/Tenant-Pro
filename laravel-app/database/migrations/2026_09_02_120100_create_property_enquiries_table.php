<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_enquiries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('property_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone_number', 32)->nullable();
            $table->text('message')->nullable();
            $table->string('status', 24)->default('NEW')->index();
            $table->string('source_ip_hash', 64)->nullable();
            $table->timestamps();
            $table->index(['property_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_enquiries');
    }
};

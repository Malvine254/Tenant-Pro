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
        Schema::create('support_conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_user_id');
            $table->foreign('tenant_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('subject')->nullable();
            $table->string('topic');
            $table->boolean('is_open')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_conversations');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->unsignedTinyInteger('bathrooms')->nullable();
            $table->decimal('deposit_amount', 12, 2)->nullable();
            $table->decimal('service_charge', 12, 2)->nullable();
            $table->decimal('other_move_in_cost', 12, 2)->nullable();
            $table->string('other_move_in_label')->nullable();
            $table->json('amenities')->nullable();
            $table->date('available_from')->nullable();
            $table->timestamp('availability_confirmed_at')->nullable();
        });
        Schema::table('properties', function (Blueprint $table) {
            $table->string('neighbourhood', 100)->nullable();
            $table->text('area_notes')->nullable();
        });
        Schema::create('listing_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('property_id')->constrained()->cascadeOnDelete();
            $table->string('reason', 50);
            $table->text('details');
            $table->string('email')->nullable();
            $table->string('status', 20)->default('OPEN')->index();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignUuid('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_reports');
        Schema::table('properties', fn (Blueprint $table) => $table->dropColumn(['neighbourhood', 'area_notes']));
        Schema::table('units', fn (Blueprint $table) => $table->dropColumn(['bathrooms', 'deposit_amount', 'service_charge', 'other_move_in_cost', 'other_move_in_label', 'amenities', 'available_from', 'availability_confirmed_at']));
    }
};

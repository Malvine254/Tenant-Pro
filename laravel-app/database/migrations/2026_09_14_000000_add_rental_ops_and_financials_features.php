<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('water_previous_reading', 10, 2)->nullable()->after('amount');
            $table->decimal('water_current_reading', 10, 2)->nullable()->after('water_previous_reading');
            $table->decimal('water_rate_per_unit', 10, 2)->nullable()->after('water_current_reading');
            $table->decimal('electricity_previous_reading', 10, 2)->nullable()->after('water_rate_per_unit');
            $table->decimal('electricity_current_reading', 10, 2)->nullable()->after('electricity_previous_reading');
            $table->decimal('electricity_rate_per_unit', 10, 2)->nullable()->after('electricity_current_reading');
            $table->json('utility_breakdown')->nullable()->after('electricity_rate_per_unit');
            $table->timestamp('last_reminder_sent_at')->nullable()->after('paid_at');
        });

        Schema::create('property_expenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('property_id')->constrained('properties')->cascadeOnDelete();
            $table->foreignUuid('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->string('category', 50)->default('MAINTENANCE'); // MAINTENANCE, UTILITIES, REPAIR, COUNTY_RATES, SALARY, SECURITY, OTHER
            $table->string('title', 255);
            $table->decimal('amount', 12, 2);
            $table->date('expense_date');
            $table->string('receipt_photo_path')->nullable();
            $table->text('notes')->nullable();
            $table->foreignUuid('recorded_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['property_id', 'expense_date']);
        });

        Schema::create('property_inspections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('property_id')->constrained('properties')->cascadeOnDelete();
            $table->foreignUuid('unit_id')->constrained('units')->cascadeOnDelete();
            $table->foreignUuid('tenant_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 30)->default('MOVE_IN'); // MOVE_IN, MOVE_OUT, PERIODIC
            $table->string('status', 30)->default('COMPLETED'); // DRAFT, COMPLETED
            $table->date('inspection_date');
            $table->string('inspector_name', 150);
            $table->decimal('meter_reading_water', 10, 2)->nullable();
            $table->decimal('meter_reading_electricity', 10, 2)->nullable();
            $table->json('checklist_data')->nullable(); // [{"category":"Walls & Paint","condition":"GOOD","notes":""}, ...]
            $table->text('general_notes')->nullable();
            $table->text('tenant_acknowledgement')->nullable();
            $table->timestamps();

            $table->index(['property_id', 'unit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_inspections');
        Schema::dropIfExists('property_expenses');

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'water_previous_reading',
                'water_current_reading',
                'water_rate_per_unit',
                'electricity_previous_reading',
                'electricity_current_reading',
                'electricity_rate_per_unit',
                'utility_breakdown',
                'last_reminder_sent_at',
            ]);
        });
    }
};

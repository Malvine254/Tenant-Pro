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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('requires_subscription')->default(false)->after('is_active');
            $table->string('billing_status', 30)->default('not_required')->after('requires_subscription');
            $table->decimal('monthly_service_fee', 12, 2)->default(0)->after('billing_status');
            $table->timestamp('trial_started_at')->nullable()->after('monthly_service_fee');
            $table->timestamp('trial_ends_at')->nullable()->after('trial_started_at');
            $table->timestamp('service_paid_until')->nullable()->after('trial_ends_at');
            $table->timestamp('subscription_started_at')->nullable()->after('service_paid_until');
            $table->timestamp('subscription_last_paid_at')->nullable()->after('subscription_started_at');

            $table->index(['requires_subscription', 'billing_status']);
            $table->index('trial_ends_at');
            $table->index('service_paid_until');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['requires_subscription', 'billing_status']);
            $table->dropIndex(['trial_ends_at']);
            $table->dropIndex(['service_paid_until']);

            $table->dropColumn([
                'requires_subscription',
                'billing_status',
                'monthly_service_fee',
                'trial_started_at',
                'trial_ends_at',
                'service_paid_until',
                'subscription_started_at',
                'subscription_last_paid_at',
            ]);
        });
    }
};

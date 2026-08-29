<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('external_reference')->nullable()->after('type');
            $table->string('checkout_request_id')->nullable()->after('external_reference');
            $table->string('merchant_request_id')->nullable()->after('checkout_request_id');
            $table->timestamp('processed_at')->nullable()->after('description');
            $table->json('raw_payload')->nullable()->after('processed_at');
            $table->index('external_reference', 'transactions_external_reference_index');
            $table->index('checkout_request_id', 'transactions_checkout_request_id_index');
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->index('checkout_request_id', 'payments_checkout_request_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_external_reference_index');
            $table->dropIndex('transactions_checkout_request_id_index');
            $table->dropColumn([
                'external_reference',
                'checkout_request_id',
                'merchant_request_id',
                'processed_at',
                'raw_payload',
            ]);
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_checkout_request_id_index');
        });
    }
};

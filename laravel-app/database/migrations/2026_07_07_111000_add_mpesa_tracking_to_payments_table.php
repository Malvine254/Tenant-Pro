<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('payment_phone')->nullable()->after('method');
            $table->string('mpesa_receipt')->nullable()->after('payment_phone');
            $table->string('status')->default('SUCCESSFUL')->after('mpesa_receipt');
            $table->string('checkout_request_id')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['payment_phone', 'mpesa_receipt', 'status', 'checkout_request_id']);
        });
    }
};

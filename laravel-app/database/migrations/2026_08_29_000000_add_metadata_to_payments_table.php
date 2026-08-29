<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->json('metadata')->nullable()->after('paid_at');
            $table->unique('mpesa_receipt', 'payments_mpesa_receipt_unique');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique('payments_mpesa_receipt_unique');
            $table->dropColumn('metadata');
        });
    }
};

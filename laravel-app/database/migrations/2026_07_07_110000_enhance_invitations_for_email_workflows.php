<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invitations', function (Blueprint $table) {
            $table->string('invite_type')->default('TENANT')->after('id');
            $table->string('invitee_name')->nullable()->after('code');
            $table->string('email')->nullable()->after('invitee_name');
            $table->string('business_name')->nullable()->after('phone_number');
            $table->text('message')->nullable()->after('business_name');
            $table->timestamp('last_sent_at')->nullable()->after('accepted_at');

            $table->dropForeign(['property_id']);
            $table->dropForeign(['unit_id']);
            $table->uuid('property_id')->nullable()->change();
            $table->uuid('unit_id')->nullable()->change();
            $table->foreign('property_id')->references('id')->on('properties')->nullOnDelete();
            $table->foreign('unit_id')->references('id')->on('units')->nullOnDelete();

            $table->string('phone_number')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('invitations', function (Blueprint $table) {
            $table->dropForeign(['property_id']);
            $table->dropForeign(['unit_id']);
            $table->dropColumn(['invite_type', 'invitee_name', 'email', 'business_name', 'message', 'last_sent_at']);

            $table->uuid('property_id')->nullable(false)->change();
            $table->uuid('unit_id')->nullable(false)->change();
            $table->string('phone_number')->nullable(false)->change();

            $table->foreign('property_id')->references('id')->on('properties')->onDelete('cascade');
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('cascade');
        });
    }
};

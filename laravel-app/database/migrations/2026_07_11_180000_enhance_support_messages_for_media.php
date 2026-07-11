<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('support_messages', function (Blueprint $table) {
            $table->string('message_type', 20)->default('text')->after('body');
            $table->string('attachment_mime_type', 150)->nullable()->after('attachment_uri');
            $table->unsignedBigInteger('attachment_size')->nullable()->after('attachment_mime_type');
            $table->string('thumbnail_url', 2048)->nullable()->after('attachment_size');
            $table->unsignedInteger('duration')->nullable()->comment('Audio duration in seconds')->after('thumbnail_url');
            $table->string('upload_status', 20)->default('complete')->after('duration');
            $table->timestamp('delivered_at')->nullable()->after('status');
            $table->timestamp('read_at')->nullable()->after('delivered_at');
            $table->uuid('client_message_id')->nullable()->after('sender_id');
            $table->unique(['sender_id', 'client_message_id'], 'support_messages_sender_client_unique');
        });
    }

    public function down(): void
    {
        Schema::table('support_messages', function (Blueprint $table) {
            $table->dropUnique('support_messages_sender_client_unique');
            $table->dropColumn(['message_type', 'attachment_mime_type', 'attachment_size', 'thumbnail_url',
                'duration', 'upload_status', 'delivered_at', 'read_at', 'client_message_id']);
        });
    }
};

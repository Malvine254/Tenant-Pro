<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('requires_password_change')->default(false)->after('password');
        });

        // An invitation delivered to this address proves access to the mailbox.
        // Repair tenant accounts created before invitation login verification existed.
        DB::table('invitations')
            ->where('invite_type', 'TENANT')
            ->whereNotNull('email')
            ->pluck('email')
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter()
            ->unique()
            ->each(function (string $email) {
                DB::table('users')
                    ->whereNull('email_verified_at')
                    ->whereRaw('LOWER(email) = ?', [$email])
                    ->update(['email_verified_at' => now()]);
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('requires_password_change');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * is_publicly_listed defaulted to false when introduced, so every property
 * created before the marketplace existed silently never appeared on it —
 * landlords never saw a prompt to opt in. Publishing should be the default;
 * landlords can still switch a property off if they don't want it listed.
 *
 * Uses a raw ALTER (not Blueprint::change()) because this changes only the
 * column default and doesn't need doctrine/dbal, which production doesn't
 * have installed.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('properties')
            ->whereNull('published_at')
            ->update(['is_publicly_listed' => true, 'published_at' => now()]);

        // SQLite (used for local dev/tests) can't ALTER a column default in place;
        // the backfill above already covers correctness there, so only MySQL/production
        // needs the schema-level default changed for rows created after this point.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE properties MODIFY is_publicly_listed TINYINT(1) NOT NULL DEFAULT 1');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE properties MODIFY is_publicly_listed TINYINT(1) NOT NULL DEFAULT 0');
        }
    }
};

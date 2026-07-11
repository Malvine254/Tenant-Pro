<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tenants')) return;

        $this->dropForeignKeyIfPresent('tenants', 'user_id');
        $this->dropIndexIfPresent('tenants', 'tenants_user_id_unique');
        if (! $this->indexExists('tenants', 'tenants_user_unit_unique')) {
            Schema::table('tenants', fn (Blueprint $table) =>
                $table->unique(['user_id', 'unit_id'], 'tenants_user_unit_unique')
            );
        }
        if (! $this->foreignKeyExists('tenants', 'user_id')) {
            Schema::table('tenants', fn (Blueprint $table) =>
                $table->foreign('user_id', 'tenants_user_id_foreign')
                    ->references('id')->on('users')->cascadeOnUpdate()->cascadeOnDelete()
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('tenants')) return;

        // A user cannot be made unique again while duplicate tenancies exist.
        $duplicates = DB::table('tenants')->select('user_id')->groupBy('user_id')->havingRaw('COUNT(*) > 1')->exists();
        if ($duplicates) {
            throw new RuntimeException('Cannot roll back multiple tenancies: duplicate tenants.user_id values exist.');
        }

        $this->dropForeignKeyIfPresent('tenants', 'user_id');
        $this->dropIndexIfPresent('tenants', 'tenants_user_unit_unique');
        if (! $this->indexExists('tenants', 'tenants_user_id_unique')) {
            Schema::table('tenants', fn (Blueprint $table) => $table->unique('user_id', 'tenants_user_id_unique'));
        }
        if (! $this->foreignKeyExists('tenants', 'user_id')) {
            Schema::table('tenants', fn (Blueprint $table) =>
                $table->foreign('user_id', 'tenants_user_id_foreign')
                    ->references('id')->on('users')->cascadeOnUpdate()->cascadeOnDelete()
            );
        }
    }

    private function dropForeignKeyIfPresent(string $table, string $column): void
    {
        $name = $this->foreignKeyName($table, $column);
        if ($name) Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropForeign($name));
    }

    private function dropIndexIfPresent(string $table, string $name): void
    {
        if ($this->indexExists($table, $name)) Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropIndex($name));
    }

    private function indexExists(string $table, string $name): bool
    {
        return collect(Schema::getIndexes($table))->contains(fn ($index) => ($index['name'] ?? null) === $name);
    }

    private function foreignKeyExists(string $table, string $column): bool
    {
        return $this->foreignKeyName($table, $column) !== null;
    }

    private function foreignKeyName(string $table, string $column): ?string
    {
        $foreign = collect(Schema::getForeignKeys($table))->first(
            fn ($key) => in_array($column, $key['columns'] ?? [], true)
        );
        return $foreign['name'] ?? null;
    }
};

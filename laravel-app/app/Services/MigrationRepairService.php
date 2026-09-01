<?php

namespace App\Services;

use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\Schema;

/**
 * Reconciles the migrations log with the database for deployments where schema changes
 * were applied outside of `migrate` (manual SQL imports, partial deploys). Migrations
 * whose tables and columns all exist already are recorded as ran so `migrate` stops
 * replaying them and failing on duplicate tables or columns.
 */
class MigrationRepairService
{
    /** Blueprint methods whose first string argument names a new column. */
    private const COLUMN_METHODS = [
        'bigIncrements', 'bigInteger', 'binary', 'boolean', 'char', 'date', 'dateTime', 'dateTimeTz',
        'decimal', 'double', 'enum', 'float', 'foreignId', 'foreignIdFor', 'foreignUlid', 'foreignUuid',
        'geometry', 'increments', 'integer', 'ipAddress', 'json', 'jsonb', 'longText', 'macAddress',
        'mediumIncrements', 'mediumInteger', 'mediumText', 'morphs', 'nullableMorphs', 'point', 'polygon',
        'set', 'smallIncrements', 'smallInteger', 'string', 'text', 'time', 'timeTz', 'timestamp',
        'timestampTz', 'tinyInteger', 'tinyText', 'ulid', 'unsignedBigInteger', 'unsignedDecimal',
        'unsignedInteger', 'unsignedMediumInteger', 'unsignedSmallInteger', 'unsignedTinyInteger',
        'uuid', 'year',
    ];

    /** Destructive or opaque operations that must never be auto-marked as applied. */
    private const UNSAFE_OPERATIONS = [
        'dropColumn', 'dropIfExists', 'renameColumn', 'rename', 'DB::statement', 'DB::table',
        'dropAllTables', 'truncate',
    ];

    public function __construct(private readonly Migrator $migrator) {}

    /**
     * @return array{marked: list<string>, pending: list<string>, missing: list<string>}
     */
    public function repair(bool $apply = true): array
    {
        $repository = $this->migrator->getRepository();

        if (! $repository->repositoryExists()) {
            $repository->createRepository();
        }

        $ran = $repository->getRan();
        $files = $this->migrator->getMigrationFiles($this->migrator->paths() ?: [database_path('migrations')]);

        $marked = [];
        $pending = [];
        $missing = [];

        foreach ($files as $name => $path) {
            if (in_array($name, $ran, true)) {
                continue;
            }

            [$applied, $absent] = $this->alreadyApplied($path);

            if ($applied) {
                $marked[] = $name;

                continue;
            }

            $pending[] = $name;
            $missing = array_merge($missing, $absent);
        }

        if ($apply && $marked !== []) {
            $batch = $repository->getNextBatchNumber();

            foreach ($marked as $name) {
                $repository->log($name, $batch);
            }
        }

        return [
            'marked' => $marked,
            'pending' => $pending,
            'missing' => array_values(array_unique($missing)),
        ];
    }

    /**
     * @return array{0: bool, 1: list<string>} Whether the schema is already in place, and what is absent.
     */
    private function alreadyApplied(string $path): array
    {
        $up = $this->upMethodBody((string) file_get_contents($path));

        if ($up === null) {
            return [false, []];
        }

        foreach (self::UNSAFE_OPERATIONS as $operation) {
            if (str_contains($up, $operation)) {
                return [false, []];
            }
        }

        $absent = [];
        $checked = 0;

        foreach ($this->createdTables($up) as $table) {
            $checked++;

            if (! Schema::hasTable($table)) {
                $absent[] = $table;
            }
        }

        foreach ($this->addedColumns($up) as [$table, $column]) {
            $checked++;

            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                $absent[] = $table.'.'.$column;
            }
        }

        // Nothing detectable means we cannot prove it ran; let migrate handle it.
        return [$checked > 0 && $absent === [], $absent];
    }

    /**
     * @return list<string>
     */
    private function createdTables(string $up): array
    {
        preg_match_all("/Schema::(?:connection\([^)]*\)->)?create\(\s*'([^']+)'/", $up, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    private function addedColumns(string $up): array
    {
        preg_match_all("/Schema::(?:connection\([^)]*\)->)?table\(\s*'([^']+)'/", $up, $matches, PREG_OFFSET_CAPTURE);

        $columns = [];
        $methods = implode('|', self::COLUMN_METHODS);

        foreach ($matches[1] ?? [] as $match) {
            [$table, $offset] = $match;
            $start = strpos($up, '{', $offset);
            $block = $start === false ? null : $this->balancedBlock($up, $start);

            if ($block === null) {
                continue;
            }

            preg_match_all("/->(?:{$methods})\(\s*'([^']+)'/", $block, $found);

            foreach ($found[1] ?? [] as $column) {
                $columns[] = [$table, $column];
            }
        }

        return $columns;
    }

    private function upMethodBody(string $source): ?string
    {
        if (! preg_match('/function\s+up\s*\([^)]*\)\s*(?::\s*\w+\s*)?\{/', $source, $match, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        $braceAt = $match[0][1] + strlen($match[0][0]) - 1;

        return $this->balancedBlock($source, $braceAt);
    }

    private function balancedBlock(string $source, int $openBraceAt): ?string
    {
        $depth = 0;
        $length = strlen($source);

        for ($i = $openBraceAt; $i < $length; $i++) {
            if ($source[$i] === '{') {
                $depth++;
            } elseif ($source[$i] === '}') {
                $depth--;

                if ($depth === 0) {
                    return substr($source, $openBraceAt + 1, $i - $openBraceAt - 1);
                }
            }
        }

        return null;
    }
}

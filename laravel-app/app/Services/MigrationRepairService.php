<?php

namespace App\Services;

use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\Schema;

/**
 * Reconciles the migrations log with the database for deployments where tables were
 * created outside of `migrate` (manual SQL imports, partial deploys). Migrations whose
 * tables all exist already are recorded as ran so `migrate` stops re-creating them.
 */
class MigrationRepairService
{
    public function __construct(private readonly Migrator $migrator) {}

    /**
     * @return array{marked: list<string>, pending: list<string>, missing_tables: list<string>}
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
        $missingTables = [];

        foreach ($files as $name => $path) {
            if (in_array($name, $ran, true)) {
                continue;
            }

            $tables = $this->createdTables($path);

            if ($tables === []) {
                $pending[] = $name;

                continue;
            }

            $absent = array_values(array_filter($tables, fn (string $table) => ! Schema::hasTable($table)));

            if ($absent !== []) {
                $pending[] = $name;
                $missingTables = array_merge($missingTables, $absent);

                continue;
            }

            $marked[] = $name;
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
            'missing_tables' => array_values(array_unique($missingTables)),
        ];
    }

    /**
     * @return list<string>
     */
    private function createdTables(string $path): array
    {
        $contents = (string) file_get_contents($path);

        preg_match_all("/Schema::(?:connection\([^)]*\)->)?create\(\s*'([^']+)'/", $contents, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }
}

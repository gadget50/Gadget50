<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyData extends Command
{
    protected $signature = 'gadget50:import-legacy {--source= : A separate legacy database connection name}';
    protected $description = 'Safely import legacy Gadget 50 data without deleting or duplicating records.';

    private array $tables = ['settings', 'users', 'categories', 'menus', 'news', 'login_rate_limits', 'auth_challenges', 'audit_logs'];

    public function handle(): int
    {
        $source = $this->option('source');
        $legacy = $source ? DB::connection($source) : DB::connection();
        $target = DB::connection();

        foreach ($this->tables as $table) {
            if (!$legacy->getSchemaBuilder()->hasTable($table)) {
                $this->warn("Skipping {$table}: table does not exist in source.");
                continue;
            }
            if (!$target->getSchemaBuilder()->hasTable($table)) {
                $this->error("Target table {$table} is missing. Run migrations first.");
                return self::FAILURE;
            }
        }

        $target->transaction(function () use ($legacy, $target) {
            foreach ($this->tables as $table) {
                if (!$legacy->getSchemaBuilder()->hasTable($table)) continue;
                $columns = $target->getSchemaBuilder()->getColumnListing($table);
                $legacy->table($table)->orderBy($this->keyFor($table))->chunkById(500, function ($rows) use ($target, $table, $columns) {
                    foreach ($rows as $row) {
                        $values = array_intersect_key((array) $row, array_flip($columns));
                        $key = $this->keyFor($table);
                        if (!array_key_exists($key, $values)) continue;
                        $target->table($table)->updateOrInsert([$key => $values[$key]], $values);
                    }
                }, $this->keyFor($table));
                $this->info("Imported {$table}.");
            }
        });

        $this->info('Legacy import completed without dropping any table or row.');
        return self::SUCCESS;
    }

    private function keyFor(string $table): string
    {
        return match ($table) {
            'login_rate_limits' => 'rate_key',
            default => 'id',
        };
    }
}

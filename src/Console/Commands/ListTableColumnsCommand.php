<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ListTableColumnsCommand extends Command
{
    protected $signature = 'table:list {table? : The specific table to list columns for}';

    protected $description = 'List all tables and their columns with types';

    public function handle()
    {
        $specificTable = $this->argument('table');

        if ($specificTable) {
            $this->listColumnsForTable($specificTable);
        } else {
            $tables = $this->getAllTables();
            foreach ($tables as $table) {
                $this->listColumnsForTable($table);
                $this->line(''); // Add a blank line between tables
            }
        }
    }

    protected function getAllTables()
    {
        $tables = DB::select('SHOW TABLES');
        return array_map(function ($table) {
            // Assuming your DB default connection settings return an object
            $table = (array)$table;  // Convert to array
            return array_values($table)[0];  // Extract table name
        }, $tables);
    }

    protected function listColumnsForTable($table)
    {
        if (!Schema::hasTable($table)) {
            $this->error("Table '{$table}' does not exist.");
            return;
        }

        $this->info("Table: {$table}");
        $this->table(
            ['Column', 'Type', 'Nullable', 'Default'],
            $this->getColumnsForTable($table)
        );
    }
protected function getColumnsForTable($table)
{
    $columns = Schema::getColumnListing($table);
    $columnData = [];

    foreach ($columns as $column) {
        $type = Schema::getColumnType($table, $column);
        // Ensure the query is a string
        $columnDetails = DB::select("SHOW COLUMNS FROM `{$table}` WHERE Field = ?", [$column]);

        if (!empty($columnDetails)) {
            $default = $columnDetails[0]->Default ?? 'NULL';
            $nullable = $columnDetails[0]->Null === 'YES' ? 'Yes' : 'No';

            $columnData[] = [
                'Column'   => $column,
                'Type'     => $type,
                'Nullable' => $nullable,
                'Default'  => $default
            ];
        }
    }

    return $columnData;
}
}

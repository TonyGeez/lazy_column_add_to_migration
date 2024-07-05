<?php

namespace TonyGeez\LazyColumnAddToMigration\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use TonyGeez\LazyColumnAddToMigration\Console\Commands\Helpers\DatabaseHelper;

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
            $tables = DatabaseHelper::getAllTables();
            foreach ($tables as $table) {
                $this->listColumnsForTable($table);
                $this->line(''); // Add a blank line between tables
            }
        }
    }

    protected function listColumnsForTable($table)
    {
        $this->info("Table: {$table}");
        $this->table(
            ['Column', 'Type', 'Nullable', 'Default'],
            DatabaseHelper::getColumnsForTable($table)
        );
    }
}

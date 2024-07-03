<?php

namespace TonyGeez\LazyColumnAddToMigration\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ListTableColumnsCommand extends Command
{
    protected $signature = 'table:list {table? : The specific table to list columns for}';

    protected $description = 'List all tables and their columns with types and relations';

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
            $table = (array)$table;
            return array_values($table)[0];
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
            ['Column', 'Type', 'Nullable', 'Default', 'Relation'],
            $this->getColumnsForTable($table)
        );
    }

    protected function getColumnsForTable($table)
    {
        $columns = Schema::getColumnListing($table);
        $columnData = [];

        foreach ($columns as $column) {
            $type = Schema::getColumnType($table, $column);
            $columnDetails = DB::select("SHOW COLUMNS FROM `{$table}` WHERE Field = ?", [$column]);

            if (!empty($columnDetails)) {
                $default = $columnDetails[0]->Default ?? 'NULL';
                $nullable = $columnDetails[0]->Null === 'YES' ? 'Yes' : 'No';
                $relation = $this->getRelation($table, $column);
                $isUnsigned = strpos($columnDetails[0]->Type, 'unsigned') !== false;

                $columnData[] = [
                    'Column'   => $column,
                    'Type'     => $this->mapTypeToLaravelSchema($type, $isUnsigned, $relation),
                    'Nullable' => $nullable,
                    'Default'  => $default,
                    'Relation' => $relation
                ];
            }
        }

        return $columnData;
    }

    protected function getRelation($table, $column)
    {
        $foreignKeys = DB::select("SELECT * FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL", [$table, $column]);

        if (!empty($foreignKeys)) {
            $fk = $foreignKeys[0];
            return "{$fk->REFERENCED_TABLE_NAME}({$fk->REFERENCED_COLUMN_NAME})";
        }

        return '';
    }

    protected function mapTypeToLaravelSchema($type, $isUnsigned, $relation)
    {
        $map = [
            'int' => $isUnsigned ? 'unsignedInteger' : 'integer',
            'bigint' => $isUnsigned ? 'unsignedBigInteger' : 'bigInteger',
            'varchar' => 'string',
            'text' => 'text',
            'date' => 'date',
            'datetime' => 'dateTime',
            'timestamp' => 'timestamp',
            'boolean' => 'boolean',
            'decimal' => 'decimal',
            'float' => 'float',
            'double' => 'double',
            'json' => 'json',
        ];

        $type = $map[$type] ?? $type;

        if (!empty($relation)) {
            $type = "foreignId";
        }

        return '$table->' . $type . '(\'' . $column . '\')';
    }
}

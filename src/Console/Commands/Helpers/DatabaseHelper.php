<?php

namespace TonyGeez\LazyColumnAddToMigration\Console\Commands\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseHelper
{
    public static function getAllTables()
    {
        $tables = DB::select('SHOW TABLES');
        return array_map(function ($table) {
            $table = (array)$table;
            return array_values($table)[0];
        }, $tables);
    }

    public static function getColumnsForTable($table)
    {
        $columns = Schema::getColumnListing($table);
        $columnData = [];

        foreach ($columns as $column) {
            $type = Schema::getColumnType($table, $column);
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

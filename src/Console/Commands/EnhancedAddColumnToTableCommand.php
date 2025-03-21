<?php

namespace TonyGeez\LazyColumnAddToMigration\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EnhancedAddColumnToTableCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'table:add 
                    {table? : The name of the table}
                    {column? : The name of the column to add}
                    {--type= : The type of the column}
                    {--nullable : Make the column nullable}
                    {--after= : Add the column after a specific column}
                    {--default= : Set a default value for the column}
                    {--foreign-model= : The model class for foreignIdFor}
                    {--on-delete= : Action on delete for foreign keys (CASCADE, SET NULL, etc.)}
                    {--on-update= : Action on update for foreign keys}
                    {--index : Add an index to the column}
                    {--unique : Add a unique index to the column}
                    {--fulltext : Add a fulltext index to the column (MySQL only)}
                    {--dry-run : Preview the migration without creating it}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add a new column to an existing table';
    
    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        // Get all tables from the database
        $tables = DB::select('SHOW TABLES');
        $tableNames = array_map(function($item) {
            return array_values((array)$item)[0];
        }, $tables);

        // If no table is provided, ask the user to select one from the list
        if (!$this->argument('table')) {
            $selectedTable = $this->choice('Select the table to update:', $tableNames);
            $this->info("Selected table: {$selectedTable}");
            $table = $selectedTable;
        } else {
            $table = $this->argument('table');
        }

        // Check if the table exists
        if (!in_array($table, $tableNames)) {
            $this->error("Table '{$table}' does not exist in the database.");
            return;
        }

        // Get column name
        $column = $this->argument('column') ?? $this->ask('Enter the column name:');

        // Check if column already exists
        $columns = $this->getTableColumns($table);
        if (in_array(strtolower($column), array_map('strtolower', $columns))) {
            if (!$this->confirm("Column '{$column}' already exists in table '{$table}'. Do you want to continue?", false)) {
                return;
            }
        }

        // Check if column name is a reserved SQL keyword
        $reservedKeywords = $this->getSqlReservedKeywords();
        if (in_array(strtoupper($column), $reservedKeywords)) {
            $this->warn("Warning: '{$column}' is a reserved SQL keyword. This might cause issues.");
            if (!$this->confirm("Do you want to continue?", false)) {
                return;
            }
        }

        // Determine if it's a foreign key based on the type option
        $type = $this->option('type') ?? $this->choice(
            'Choose the column type:',
            $this->getColumnTypes(),
            'string'
        );

        $isForeignKey = $type === 'foreignId';

        // Handle column definition
        if ($isForeignKey) {
            $columnDefinition = $this->handleForeignKey($table, $column);
        } else {
            $columnDefinition = $this->buildColumnDefinition($type, $column);
        }

        // Check if column should be nullable
        $nullable = $this->option('nullable') ?? $this->confirm('Should the column be nullable?', false);
        if ($nullable) {
            $columnDefinition .= "->nullable()";
        }

        // Handle default value
        $default = $this->option('default') ?? $this->ask('Enter a default value (optional):');
        if (!empty($default)) {
            $columnDefinition .= "->default(" . $this->formatDefaultValue($type, $default) . ")";
        }

        // Determine column position
        $after = $this->option('after') ?? $this->ask('Enter the column to place the new column after (optional):');
        if ($after) {
            $columnDefinition .= "->after('{$after}')";
        }

        // Handle index options
        if ($this->option('index')) {
            $columnDefinition .= "->index()";
        } elseif ($this->option('unique')) {
            $columnDefinition .= "->unique()";
        } elseif ($this->option('fulltext')) {
            $columnDefinition .= "->fullText()";
        }

        $columnDefinition .= ";";

        // Preview the migration if dry-run is enabled
        if ($this->option('dry-run')) {
            $this->info("=== Migration Preview ===");
            $this->info("Table: {$table}");
            $this->info("Column: {$column}");
            $this->info("Definition: {$columnDefinition}");
            $this->info("========================");
            
            if (!$this->confirm('Do you want to create this migration?', true)) {
                return;
            }
        }

        // Generate migration file
        $this->generateMigrationFile($table, $column, $columnDefinition);
    }

    /**
     * Handle the creation of a foreign key column.
     *
     * @param string $table
     * @param string $column
     * @return string
     */
    private function handleForeignKey($table, $column)
    {
        $foreignModel = $this->option('foreign-model') ?? $this->ask("Enter the fully qualified model class for the foreign key (e.g., 'App\\Models\\Project'):");
        $relatedTable = Str::plural(Str::snake(class_basename($foreignModel)));

        $columnDefinition = "\$table->foreignIdFor({$foreignModel}::class, '{$column}')";
        $columnDefinition .= "->constrained('{$relatedTable}')";

        // Handle on delete action
        $onDelete = $this->option('on-delete');
        if ($onDelete) {
            $columnDefinition .= "->onDelete('{$onDelete}')";
        } else {
            $useOnDelete = $this->confirm('Do you want to specify an ON DELETE action?', false);
            if ($useOnDelete) {
                $onDeleteAction = $this->choice('Select ON DELETE action:', [
                    'CASCADE', 'SET NULL', 'NO ACTION', 'RESTRICT', 'SET DEFAULT'
                ], 'CASCADE');
                $columnDefinition .= "->onDelete('{$onDeleteAction}')";
            }
        }

        // Handle on update action
        $onUpdate = $this->option('on-update');
        if ($onUpdate) {
            $columnDefinition .= "->onUpdate('{$onUpdate}')";
        } else {
            $useOnUpdate = $this->confirm('Do you want to specify an ON UPDATE action?', false);
            if ($useOnUpdate) {
                $onUpdateAction = $this->choice('Select ON UPDATE action:', [
                    'CASCADE', 'SET NULL', 'NO ACTION', 'RESTRICT', 'SET DEFAULT'
                ], 'CASCADE');
                $columnDefinition .= "->onUpdate('{$onUpdateAction}')";
            }
        }

        return $columnDefinition;
    }

    /**
     * Build the column definition.
     *
     * @param string $type
     * @param string $column
     * @return string
     */
    private function buildColumnDefinition($type, $column)
    {
        $definition = "\$table->{$type}('{$column}'";

        if ($type === 'enum') {
            $values = $this->ask('Enter enum values separated by commas:');
            $values = array_map('trim', explode(',', $values));
            $valuesString = implode("', '", $values);
            $definition .= ", ['{$valuesString}']";
        } elseif (in_array($type, ['decimal', 'float'])) {
            $total = $this->ask('Enter total digits (including decimals):');
            $places = $this->ask('Enter decimal places:');
            $definition .= ", {$total}, {$places}";
        } elseif ($type === 'string') {
            $length = $this->ask('Enter string length (default: 255):', 255);
            if ($length != 255) {
                $definition .= ", {$length}";
            }
        } elseif ($type === 'uuid') {
            // UUID columns don't need additional parameters
        } elseif ($type === 'ipAddress' || $type === 'macAddress') {
            // These types don't need additional parameters
        }

        $definition .= ")";

        return $definition;
    }

    /**
     * Format the default value based on the column type.
     *
     * @param string $type
     * @param mixed $value
     * @return string
     */
    private function formatDefaultValue($type, $value)
    {
        if (in_array($type, ['date', 'dateTime', 'timestamp']) && strtoupper($value) === 'CURRENT_TIMESTAMP') {
            return "DB::raw('CURRENT_TIMESTAMP')";
        }

        if (in_array($type, ['integer', 'bigInteger', 'unsignedInteger', 'float', 'decimal'])) {
            return $value;
        }

        if ($type === 'boolean') {
            return strtolower($value) === 'true' ? 'true' : 'false';
        }

        if ($type === 'json' && (Str::startsWith($value, '{') || Str::startsWith($value, '['))) {
            return $value; // Return JSON as is
        }

        return "'{$value}'";
    }

    /**
     * Get the list of available column types.
     *
     * @return array
     */
    private function getColumnTypes()
    {
        return [
            'bigInteger', 'boolean', 'date', 'dateTime', 'decimal', 'enum', 'float', 
            'foreignId', 'id', 'increments', 'integer', 'json', 'longText', 'string', 'text', 'timestamps', 
            'unsignedInteger', 'uuid', 'ipAddress', 'macAddress', 'year', 'time', 'tinyInteger', 'mediumInteger',
            'mediumText', 'char', 'binary', 'geometry', 'point', 'lineString', 'polygon', 'multiPoint',
            'multiLineString', 'multiPolygon', 'geometryCollection'
        ];
    }

    /**
     * Get the list of SQL reserved keywords.
     *
     * @return array
     */
    private function getSqlReservedKeywords()
    {
        return [
            'ADD', 'ALL', 'ALTER', 'AND', 'AS', 'ASC', 'BETWEEN', 'BY', 'CASE', 'CHECK', 'COLUMN', 
            'CONSTRAINT', 'CREATE', 'DATABASE', 'DEFAULT', 'DELETE', 'DESC', 'DISTINCT', 'DROP', 
            'ELSE', 'END', 'EXISTS', 'FOREIGN', 'FROM', 'GROUP', 'HAVING', 'IN', 'INDEX', 'INSERT', 
            'INTO', 'IS', 'JOIN', 'KEY', 'LEFT', 'LIKE', 'LIMIT', 'NOT', 'NULL', 'ON', 'OR', 'ORDER', 
            'PRIMARY', 'REFERENCES', 'RIGHT', 'SELECT', 'SET', 'TABLE', 'THEN', 'TO', 'UNION', 'UNIQUE', 
            'UPDATE', 'VALUES', 'VIEW', 'WHERE'
        ];
    }

    /**
     * Get columns for a specific table.
     *
     * @param string $table
     * @return array
     */
    private function getTableColumns($table)
    {
        $columns = DB::select("SHOW COLUMNS FROM `{$table}`");
        return array_map(function($column) {
            return $column->Field;
        }, $columns);
    }

    /**
     * Generate the migration file.
     *
     * @param string $table
     * @param string $column
     * @param string $columnDefinition
     * @return void
     */
    private function generateMigrationFile($table, $column, $columnDefinition)
    {
        $migrationName = "add_{$column}_to_{$table}_table";
        $fileName = date('Y_m_d_His') . "_" . $migrationName . ".php";
        $path = database_path("migrations/{$fileName}");

        $stub = File::get(__DIR__ . '/stubs/add-column.stub');

        $stub = str_replace(
            ['{{table}}', '{{column}}', '{{columnDefinition}}'],
            [$table, $column, $columnDefinition],
            $stub
        );

        File::put($path, $stub);

        $this->info("Migration created successfully: {$fileName}");
    }
}
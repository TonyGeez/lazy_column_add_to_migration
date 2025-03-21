<?php

namespace TonyGeez\LazyColumnAddToMigration\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OldAddColumnToTableCommand extends Command
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
                    {--foreign-model= : The model class for foreignIdFor}';

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

        // Get column name
        $column = $this->argument('column') ?? $this->ask('Enter the column name:');

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

        $columnDefinition .= ";";

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
        if (in_array($type, ['date', 'dateTime']) && strtoupper($value) === 'CURRENT_TIMESTAMP') {
            return "DB::raw('CURRENT_TIMESTAMP')";
        }

        if (in_array($type, ['integer', 'bigInteger', 'unsignedInteger', 'float', 'decimal'])) {
            return $value;
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
            'unsignedInteger'
        ];
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

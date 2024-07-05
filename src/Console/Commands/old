<?php

namespace TonyGeez\LazyColumnAddToMigration\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use TonyGeez\LazyColumnAddToMigration\Helpers\DatabaseHelper;

class AddColumnToTableCommand extends Command
{
    protected $signature = 'table:add 
                    {table? : The name of the table}
                    {column? : The name of the column to add}
                    {--type= : The type of the column}
                    {--nullable : Make the column nullable}
                    {--after= : Add the column after a specific column}
                    {--default= : Set a default value for the column}
                    {--foreign-model= : The model class for foreignIdFor}';

    protected $description = 'Add a new column to an existing table';

    public function handle()
    {
        $tables = DatabaseHelper::getAllTables();

        if (!$this->argument('table')) {
            $selectedTable = $this->choice('Select the table to update:', $tables);
            $this->info("Selected table: {$selectedTable}");
            $table = $selectedTable;
        } else {
            $table = $this->argument('table');
        }

        $column = $this->argument('column') ?? $this->ask('Enter the column name:');

        $type = $this->option('type') ?? $this->choice(
            'Choose the column type:',
            $this->getColumnTypes(),
            'string'
        );

        $isForeignKey = $type === 'foreignId';

        if ($isForeignKey) {
            $models = $this->getModelClasses();
            $foreignModel = $this->option('foreign-model') ?? $this->choice('Select the model class for the foreign key:', $models);
            $columnDefinition = $this->handleForeignKey($table, $column, $foreignModel);
        } else {
            $columnDefinition = $this->buildColumnDefinition($type, $column);
        }

        $nullable = $this->option('nullable') ?? $this->confirm('Should the column be nullable?', false);
        if ($nullable) {
            $columnDefinition .= "->nullable()";
        }

        $default = $this->option('default') ?? $this->ask('Enter a default value (optional):');
        if (!empty($default)) {
            $columnDefinition .= "->default(" . $this->formatDefaultValue($type, $default) . ")";
        }

        $after = $this->option('after') ?? $this->ask('Enter the column to place the new column after (optional):');
        if ($after) {
            $columnDefinition .= "->after('{$after}')";
        }

        $columnDefinition .= ";";

        $this->generateMigrationFile($table, $column, $columnDefinition);
    }

    private function getModelClasses()
    {
        $models = [];
        $path = app_path('Models');
        $files = File::files($path);

        foreach ($files as $file) {
            $className = 'App\\Models\\' . pathinfo($file, PATHINFO_FILENAME);
            if (class_exists($className)) {
                $models[] = $className;
            }
        }

        return $models;
    }

    private function handleForeignKey($table, $column, $foreignModel)
    {
        $relatedTable = Str::plural(Str::snake(class_basename($foreignModel)));

        $columnDefinition = "\$table->foreignIdFor({$foreignModel}::class, '{$column}')";
        $columnDefinition .= "->constrained('{$relatedTable}')";

        return $columnDefinition;
    }

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

    private function getColumnTypes()
    {
        return [
            'bigInteger', 'boolean', 'date', 'dateTime', 'decimal', 'enum', 'float', 
            'foreignId', 'id', 'increments', 'integer', 'json', 'longText', 'string', 'text', 'timestamps', 
            'unsignedInteger'
        ];
    }

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

<?php

namespace TonyGeez\LazyColumnAddToMigration\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AddColumnToTableCommand extends Command
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
    // Get table and column names
    $table = $this->argument('table') ?? $this->ask('Enter the table name:');
    $column = $this->argument('column') ?? $this->ask('Enter the column name:');

    // Check if the column should be a foreign key
    $isForeignKey = $this->confirm('Is this column a foreign key?', false);

    // Determine column type and definition
    if ($isForeignKey) {
        $columnDefinition = $this->handleForeignKey($table, $column);
    } else {
        $type = $this->determineColumnType(false);
        $columnDefinition = $this->buildColumnDefinition($type, $column);
    }

    // Check if column should be nullable
    $nullable = $this->confirm('Should the column be nullable?', $this->option('nullable'));
    if ($nullable) {
        $columnDefinition .= "->nullable()";
    }

    // Handle default value
    $default = $this->option('default') ?? $this->ask('Enter a default value (optional):');
    if (!empty($default)) {
        $columnDefinition .= "->default(" . $this->formatDefaultValue($type ?? 'string', $default) . ")";
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

private function handleForeignKey($table, $column)
{
    $relatedTable = $this->ask("Enter the table this foreign key relates to (e.g., 'projects' for {$column}):");
    $relatedModel = $this->ask("Enter the fully qualified model class for the related table (e.g., 'App\\Models\\Project'):");

    $columnDefinition = "\$table->foreignIdFor({$relatedModel}::class, '{$column}')";
    $columnDefinition .= "->constrained('{$relatedTable}')";

    return $columnDefinition;
}
    /**
     * Determine the column type.
     *
     * @param bool $isForeignKey
     * @return string
     */
    private function determineColumnType($isForeignKey)
    {
        if ($isForeignKey) {
            return 'foreignId';
        }

        $types = [
            'bigInteger', 'boolean', 'date', 'dateTime', 'decimal', 'enum', 'float', 
            'id', 'increments', 'integer', 'json', 'longText', 'string', 'text', 'timestamps', 
            'unsignedInteger'
        ];

        return $this->choice(
            'Choose the column type:',
            $types,
            array_search($this->option('type'), $types) !== false ? array_search($this->option('type'), $types) : 1
        );
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
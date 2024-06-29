<?php

namespace TonyGeez\LazyColumnAddToMigration\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

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
                    {--after= : Add the column after a specific column}';

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

        // Determine column type
        $type = $this->choice(
            'Choose the column type:',
            ['integer', 'string', 'boolean', 'date', 'text', 'bigInteger', 'decimal'],
            array_search($this->option('type'), ['integer', 'string', 'boolean', 'date', 'text', 'bigInteger', 'decimal']) !== false 
                ? array_search($this->option('type'), ['integer', 'string', 'boolean', 'date', 'text', 'bigInteger', 'decimal']) 
                : 1
        );

        // Check if column should be nullable
        $nullable = $this->confirm('Should the column be nullable?', $this->option('nullable'));

        // Handle default value for date columns
        $default = null;
        if ($type === 'date' && !$nullable) {
            $default = $this->ask('Enter a default date (YYYY-MM-DD) or leave blank for current date:');
            if (empty($default)) {
                $default = 'CURRENT_TIMESTAMP';
            }
        }

        // Determine column position
        $after = $this->option('after') ?? $this->ask('Enter the column to place the new column after (optional):');

        // Generate migration file name
        $migrationName = "add_{$column}_to_{$table}_table";
        $fileName = date('Y_m_d_His') . "_" . $migrationName . ".php";
        $path = database_path("migrations/{$fileName}");

        // Get migration stub content
        $stub = File::get(__DIR__ . '/stubs/add-column.stub');

        // Replace placeholders in stub
        $stub = str_replace(
            ['{{table}}', '{{column}}', '{{type}}'],
            [$table, $column, $type],
            $stub
        );

        // Build column definition
        $columnDefinition = "\$table->{$type}('{$column}')";
        if ($nullable) {
            $columnDefinition .= "->nullable()";
        } elseif ($default !== null) {
            $columnDefinition .= "->default(DB::raw('{$default}'))";
        }
        if ($after) {
            $columnDefinition .= "->after('{$after}')";
        }
        $columnDefinition .= ";";

        // Replace column definition in stub
        $stub = str_replace('{{columnDefinition}}', $columnDefinition, $stub);

        // Create migration file
        File::put($path, $stub);

        // Output success message
        $this->info("Migration created successfully: {$fileName}");
    }
}
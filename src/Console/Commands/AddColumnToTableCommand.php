<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class AddColumnToTableCommand extends Command
{
    protected $signature = 'table:add 
                    {table? : The name of the table}
                    {column? : The name of the column to add}
                    {--type= : The type of the column}
                    {--nullable : Make the column nullable}
                    {--after= : Add the column after a specific column}';

    protected $description = 'Add a new column to an existing table';
    
 public function handle()
{
    $table = $this->argument('table') ?? $this->ask('Enter the table name:');
    $column = $this->argument('column') ?? $this->ask('Enter the column name:');

    $type = $this->choice(
        'Choose the column type:',
        ['integer', 'string', 'boolean', 'date', 'text', 'bigInteger', 'decimal'],
        array_search($this->option('type'), ['integer', 'string', 'boolean', 'date', 'text', 'bigInteger', 'decimal']) !== false 
            ? array_search($this->option('type'), ['integer', 'string', 'boolean', 'date', 'text', 'bigInteger', 'decimal']) 
            : 1
    );

    $nullable = $this->confirm('Should the column be nullable?', $this->option('nullable'));

    $default = null;
    if ($type === 'date' && !$nullable) {
        $default = $this->ask('Enter a default date (YYYY-MM-DD) or leave blank for current date:');
        if (empty($default)) {
            $default = 'CURRENT_TIMESTAMP';
        }
    }

    $after = $this->option('after') ?? $this->ask('Enter the column to place the new column after (optional):');

    $migrationName = "add_{$column}_to_{$table}_table";
    $fileName = date('Y_m_d_His') . "_" . $migrationName . ".php";
    $path = database_path("migrations/{$fileName}");

    $stub = File::get(app_path('Console/Commands/stubs/add-column.stub'));

    $stub = str_replace(
        ['{{table}}', '{{column}}', '{{type}}'],
        [$table, $column, $type],
        $stub
    );

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

    $stub = str_replace('{{columnDefinition}}', $columnDefinition, $stub);

    File::put($path, $stub);

    $this->info("Migration created successfully: {$fileName}");
}
}

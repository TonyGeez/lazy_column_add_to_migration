<?php
namespace TonyGeez\LazyColumnAddToMigration;

use Illuminate\Support\ServiceProvider;

class LazyColumnServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\Commands\AddColumnToTableCommand::class,
                Console\Commands\ListTableColumnsCommand::class
            ]);
        }
    }
}

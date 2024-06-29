<?php

namespace TonyGeez\LazyColumnAddToMigration;

use Illuminate\Support\ServiceProvider;
use TonyGeez\LazyColumnAddToMigration\Console\Commands\AddColumnToTableCommand;
use TonyGeez\LazyColumnAddToMigration\Console\Commands\ListTableColumnsCommand;

class LazyColumnServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                AddColumnToTableCommand::class,
                ListTableColumnsCommand::class,
            ]);
        }
    }

    public function register()
    {
        //
    }
}

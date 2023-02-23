<?php

namespace Portable\LaravelDbTools;

use Illuminate\Support\ServiceProvider;

class LaravelDbToolsServiceProvider extends ServiceProvider
{

    protected $commands = [
        'Portable\LaravelDbTools\Console\Commands\DbExport',
        'Portable\LaravelDbTools\Console\Commands\DbImport',
    ];

    public function register()
    {
        $this->commands($this->commands);
    }
}

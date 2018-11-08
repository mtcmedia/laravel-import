<?php

namespace Mtc\Import\Providers;

use Illuminate\Support\ServiceProvider;
use Mtc\Import\Console\Commands\ImportDatabase;
use Mtc\Import\Console\Commands\ImportXml;
use Mtc\Import\Console\Commands\ImportCsv;
use Mtc\Import\Console\Commands\ImportJson;

/**
 * Class ImportServiceProvider
 *
 * @package Mtc\Import
 */
class ImportServiceProvider extends ServiceProvider
{
    /**
     * Extend service provider booting
     */
    public function boot()
    {
        $this->commands([
            ImportDatabase::class,
            ImportXml::class,
            ImportCsv::class,
            ImportJson::class,
        ]);

        $this->mergeConfigFrom(dirname(__DIR__,2 ) . '/config/import.php', 'import');
        $this->mergeConfigFrom(dirname(__DIR__,2 ) . '/config/database.php', 'database');

        $this->publishes([
            __DIR__ . '/../../config/' => config_path()
        ], 'config');

    }
}

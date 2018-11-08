<?php

namespace Mtc\Import\Console\Commands;

use Mtc\Import\Drivers\Json;
use Illuminate\Console\Command;

/**
 * Class ImportJson
 *
 * @package Mtc\Import\Console\Commands
 */
class ImportJson extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:json {source-map} {data-file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the import using Json driver';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $import = new Json($this->argument('data-file'));
        $import->loadMapFromFile($this->argument('source-map'));
        $import->run($this);
    }
}

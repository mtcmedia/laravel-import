<?php

namespace Mtc\Import\Console\Commands;

use Mtc\Import\Drivers\Csv;
use Illuminate\Console\Command;

/**
 * Class ImportCsv
 *
 * @package Mtc\Import\Console\Commands
 */
class ImportCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:csv {source-map} {data-file} {--header-offset=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the import using CSV driver';

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
        $import = new Csv($this->argument('data-file'), $this->option('header-offset'));
        $import->loadMapFromFile($this->argument('source-map'));
        $import->run($this);
    }
}

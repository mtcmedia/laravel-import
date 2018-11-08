<?php

namespace Mtc\Import\Console\Commands;

use Mtc\Import\Drivers\Xml;
use Illuminate\Console\Command;

/**
 * Class ImportXml
 *
 * @package Mtc\Import\Console\Commands
 */
class ImportXml extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:xml {source-map} {data-file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the import using Xml driver';

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
        $import = new Xml($this->argument('data-file'));
        $import->loadMapFromFile($this->argument('source-map'));
        $import->run($this);
    }
}

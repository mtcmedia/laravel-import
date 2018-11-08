<?php

namespace Mtc\Import\Console\Commands;

use Mtc\Import\Drivers\Database;
use Illuminate\Console\Command;

/**
 * Class ImportDatabase
 *
 * @package Mtc\Import\Console\Commands
 */
class ImportDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:database {source-map} {--prefix=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the import using Database driver';

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
        $import = new Database($this->option('prefix'));
        $import->loadMapFromFile($this->argument('source-map'));
        $import->run($this);
    }
}

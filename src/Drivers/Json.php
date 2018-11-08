<?php

namespace Mtc\Import\Drivers;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Mtc\Import\BaseImportProcessor;
use Mtc\Import\Contracts\ImportDriverContract;

/**
 * Class Json
 * Engine that allows importing data from a json file
 *
 * @package Mtc\Import
 */
class Json extends BaseImportProcessor implements ImportDriverContract
{
    /**
     * All data entries
     *
     * @var $data
     */
    protected $data;

    /**
     * Json constructor.
     *
     * @param string $data_file_path
     * @throws \Exception
     */
    public function __construct($data_file_path = '')
    {
        $this->loadDataFromFile($data_file_path);
        $this->data = json_decode($this->data);
    }

    /**
     * Run the import
     *
     * @param null $console
     */
    public function run($console = null)
    {
        $this->setDataToIterator();

        // We add Console output to show progress of the running import
        if ($console instanceof Command) {
            $progress_bar = $console->getOutput()->createProgressBar($this->data->count());
        }

        $this->data
        ->each(function ($data_object) use ($progress_bar) {
            $this->mapped_data = $this->mapData($this->data_map, $data_object);
            $this->populateData($this->mapped_data);

            $progress_bar->advance();
        });
    }

    /**
     * We need to update xz
     */
    protected function setDataToIterator()
    {
        if (!empty($this->import_map_data['path_to_model_iterator'])) {
            $this->data = collect($this->findValueInData($this->import_map_data['path_to_model_iterator'], $this->data));
        }
    }
}

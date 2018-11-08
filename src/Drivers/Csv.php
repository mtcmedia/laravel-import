<?php

namespace Mtc\Import\Drivers;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use League\Csv\Reader;
use Mtc\Import\BaseImportProcessor;
use Mtc\Import\Contracts\ImportDriverContract;

/**
 * Class Database
 * Engine that allows importing data from a different database
 *
 * @package Mtc\Import
 */
class Csv extends BaseImportProcessor implements ImportDriverContract
{
    /**
     * All data entries
     *
     * @var $data
     */
    protected $data;

    /**
     * Offset for header - number of lines to skip
     *
     * @var int
     */
    protected $header_offset = null;

    /**
     * Json constructor.
     *
     * @param string $data_file_path
     * @throws \Exception
     */
    public function __construct($data_file_path = '', $header_offset = null)
    {
        if ($header_offset !== null) {
            // We are subtracting offset by 1 due to language interpretation
            // User will expect offset to be 1 signifying skipping 1 line
            // Whilst League/Csv Reader will interpret that the header is on index 1 which is line 2 of the file
            $this->header_offset = $header_offset - 1;
        }
        $this->loadDataFromFile($data_file_path);
    }

    /**
     * Load import map from the provided map file
     *
     * @param $map_file
     * @throws \Exception
     */
    public function loadDataFromFile($data_file)
    {
        $this->path_to_import_map_file = $data_file;
        $import_file_path = storage_path(config('import.storage_path') . $data_file);

        if (!File::exists($import_file_path)) {
            throw new \Exception("Failed to load import map at $import_file_path");
        }

        $this->raw_import_data = Reader::createFromPath($import_file_path);
        if ($this->header_offset) {
            $this->raw_import_data->setHeaderOffset($this->header_offset);
        }
        $this->data = $this->raw_import_data->getRecords();
    }

    /**
     * Run the import
     *
     * @param null $console
     */
    public function run($console = null)
    {

        // We add Console output to show progress of the running import
        if ($console instanceof Command) {
            $progress_bar = $console->getOutput()->createProgressBar($this->raw_import_data->count());
        }


        foreach ($this->data as $data_object) {
            $this->mapped_data = $this->mapData($this->data_map, $data_object);
            $this->populateData($this->mapped_data);

            $progress_bar->advance();
        }
    }

}

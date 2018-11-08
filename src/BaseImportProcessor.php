<?php

namespace Mtc\Import;

use Illuminate\Support\Facades\File;

/**
 * Class BaseImportProcessor
 * Base instance for
 * @package Mtc\Import
 */
class BaseImportProcessor
{
    use Traits\MapsData;
    use Traits\PopulatesDatabase;

    /**
     * Path to the import file
     *
     * @var $path_to_import_map_file
     */
    protected $path_to_import_map_file;

    /**
     * Contents of the import map
     *
     * @var $import_file_data
     */
    protected $import_map_data;

    /**
     * The configuration of how fields should be mapped
     *
     * @var $data_map
     */
    protected $data_map;

    /**
     * Data record once mapped by the parser
     *
     * @var $mapped_data
     */
    protected $mapped_data;

    /**
     * The primary Eloquent model that will be populated
     *
     * @var $model
     */
    protected $model;

    /**
     * Raw data from import data file
     *
     * @var $raw_import_data
     */
    protected $raw_import_data;

    /**
     * Load import map from the provided map file
     *
     * @param $map_file
     * @throws \Exception
     */
    public function loadMapFromFile($map_file)
    {
        $this->path_to_import_map_file = $map_file;
        $import_source_path = storage_path(config('import.storage_map_path') . $map_file);

        if (!File::exists($import_source_path)) {
            throw new \Exception("Failed to load import map at $import_source_path");
        }

        $this->import_map_data = json_decode(File::get($import_source_path), true);
        $this->data_map = $this->import_map_data['data_map'];
        $this->model = $this->import_map_data['model'];
    }

    /**
     * Load data from the provided file
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

        $this->raw_import_data = File::get($import_file_path);
    }

}
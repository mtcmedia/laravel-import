<?php

namespace Mtc\Import\Drivers;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Mtc\Import\BaseImportProcessor;
use Mtc\Import\Contracts\ImportDriverContract;

/**
 * Class Database
 * Engine that allows importing data from a different database
 *
 * @package Mtc\Import
 */
class Database extends BaseImportProcessor implements ImportDriverContract
{
    /**
     * DB table prefix
     * Allows setting one map for frameworks that use prefixes (prestaShop/Joomla)
     *
     * @var string $prefix
     */
    protected $prefix = '';

    /**
     * Database query relationship info
     *
     * @var $query
     */
    protected $query;

    /**
     * Database constructor.
     *
     * @param string $prefix
     */
    public function __construct($prefix = '')
    {
        $this->prefix = $prefix;
    }

    /**
     * Extend the map loading from file to set up necessary information
     *
     * @param $map_file
     * @throws \Exception
     */
    public function loadMapFromFile($map_file)
    {
        parent::loadMapFromFile($map_file);

        $this->query = $this->import_map_data['query'];
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
            $total_num_rows = DB::connection(config('import.import_db_connection'))
                ->table($this->prefix . $this->query['table'])
                ->count();
            $progress_bar = $console->getOutput()->createProgressBar($total_num_rows);
        }

        DB::connection(config('import.import_db_connection'))
            ->table($this->prefix . $this->query['table'])
            ->orderBy($this->data_map['id'])
            ->chunk(config('import.db_chunk_size'), function ($data_chunk) use ($progress_bar) {
                // We are running chunks to avoid crashing on large databases
                $data_chunk
                    ->each(function ($data_object) use ($progress_bar) {
                        if (!empty($this->query['relationships'])) {
                            $this->getRelationshipData($data_object, $this->query['relationships']);
                        }
                        $this->mapped_data = $this->mapData($this->data_map, $data_object);
                        $this->populateData($this->mapped_data);

                        $progress_bar->advance();
                    });
            });
    }

    /**
     * Add the relationship data for the object
     * Since we are importing data we don't have eloquent objects that would automatically cascade model data
     * and running joins result all data being in the same table.
     * Whilst this is not performance-efficient this is the easiest way of ensuring data consistency
     * We also are not using pre-loaded tables to avoid running out of memory for large tables
     *
     * @param $data
     * @param $relationship_list
     */
    protected function getRelationshipData($data, $relationship_list)
    {
        foreach ($relationship_list as $relationship_name => $relationship) {
            $data->{$relationship_name} = DB::connection(config('import.import_db_connection'))
                ->table($this->prefix . $relationship['table'])
                ->where($relationship['child_column'], $data->{$relationship['parent_column']})
                ->get()
                ->each(function ($child_relationship) use ($relationship) {
                    if (!empty($relationship['relationships'])) {
                        $this->getRelationshipData($child_relationship, $relationship['relationships']);
                    }
                });
        }
    }
}

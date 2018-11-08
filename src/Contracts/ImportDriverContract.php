<?php

namespace Mtc\Import\Contracts;

/**
 * Interface ImportDriverContract
 * Define the constraints of the import engine
 *
 * @package Mtc\Import\Contracts
 */
interface ImportDriverContract
{
    /**
     * Run the import process
     *
     * @param $console
     * @return mixed
     */
    public function run($console);

    /**
     * Ability to load the import map from file
     *
     * @param $path_to_file
     * @return mixed
     */
    public function loadMapFromFile($path_to_file);
}
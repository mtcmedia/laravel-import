<?php

return [
    // Loop operator - symbol that signifies that we need to go through all collection entries
    'loop_operator' => '*',

    // name of the db connection that will be used in the import from DB
    'import_db_connection' => 'import',

    // Path to where import files are stored inside storage directory
    'storage_path' => 'import/',

    // Path to where import map files are stored inside storage directory
    'storage_map_path' => 'import/maps/',

    // DB import uses chunks to avoid running out of memory when fetching data
    'db_chunk_size' => 100,
];
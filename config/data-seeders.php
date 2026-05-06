<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Data Seeder Path
    |--------------------------------------------------------------------------
    |
    | The directory where data seeder files live. The runner will look here
    | for files prefixed with a timestamp (similar to Laravel migrations).
    |
    */
    'path' => database_path('data-seeders'),

    /*
    |--------------------------------------------------------------------------
    | State Table
    |--------------------------------------------------------------------------
    |
    | The table used to track which data seeders have already run, and the
    | batch in which they ran.
    |
    */
    'table' => 'data_seeders',

    /*
    |--------------------------------------------------------------------------
    | Database Connection
    |--------------------------------------------------------------------------
    |
    | The database connection used by the runner and the state table. Set to
    | null to use the application's default connection.
    |
    */
    'connection' => null,

];

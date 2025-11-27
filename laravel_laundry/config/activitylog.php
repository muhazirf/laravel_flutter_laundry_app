<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Database Connection
    |--------------------------------------------------------------------------
    |
    | The default database connection to use for the activity log.
    |
    */
    'database_connection' => env('DB_CONNECTION', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Activity Log Table
    |--------------------------------------------------------------------------
    |
    | The table that will store the activity logs.
    |
    */
    'table_name' => env('ACTIVITY_LOG_TABLE', 'activity_log'),

    /*
    |--------------------------------------------------------------------------
    | Default Log Name
    |--------------------------------------------------------------------------
    |
    | The default log name to use if none is specified.
    |
    */
    'default_log_name' => env('ACTIVITY_LOG_DEFAULT_LOG_NAME', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Auth Driver
    |--------------------------------------------------------------------------
    |
    | The authentication driver to use for retrieving the current user.
    |
    */
    'auth_driver' => env('ACTIVITY_LOG_AUTH_DRIVER', 'auth'),

    /*
    |--------------------------------------------------------------------------
    | Enable Logging
    |--------------------------------------------------------------------------
    |
    | Whether to enable activity logging.
    |
    */
    'enabled' => env('ACTIVITY_LOG_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Subject Returns Soft Deleted Models
    |--------------------------------------------------------------------------
    |
    | Whether to return soft deleted models when retrieving the subject.
    |
    */
    'subject_returns_soft_deleted_models' => false,

    /*
    |--------------------------------------------------------------------------
    | Causer Returns Soft Deleted Models
    |--------------------------------------------------------------------------
    |
    | Whether to return soft deleted models when retrieving the causer.
    |
    */
    'causer_returns_soft_deleted_models' => false,
];
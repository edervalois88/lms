<?php

use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Railway-aware database configuration
|--------------------------------------------------------------------------
| Railway injects MySQL credentials as MYSQLHOST / MYSQL_HOST / DATABASE_URL.
| We normalise all known formats so Laravel always gets the right connection
| regardless of how the platform names its variables.
*/

// Resolve host from all known Railway / generic MySQL env var names.
$dbHost = env('DB_HOST',
    env('MYSQLHOST',
    env('MYSQL_HOST',
    env('MYSQL_PRIVATE_HOST',
    null))));

$dbPort = (int) env('DB_PORT',
    env('MYSQLPORT',
    env('MYSQL_PORT',
    env('MYSQL_PRIVATE_PORT',
    3306))));

$dbDatabase = env('DB_DATABASE',
    env('MYSQLDATABASE',
    env('MYSQL_DATABASE',
    env('MYSQL_DBNAME',
    'forge'))));

$dbUsername = env('DB_USERNAME',
    env('MYSQLUSER',
    env('MYSQL_USER',
    env('MYSQL_USERNAME',
    'forge'))));

$dbPassword = env('DB_PASSWORD',
    env('MYSQLPASSWORD',
    env('MYSQL_PASSWORD',
    '')));

// Railway also provides a full connection URL — prefer it when present.
$databaseUrl = env('DATABASE_URL', env('MYSQL_URL', env('MYSQL_PRIVATE_URL', null)));

// Auto-detect connection type: if any MySQL source exists and DB_CONNECTION
// has not been explicitly set to something else, default to mysql.
$defaultConnection = env('DB_CONNECTION', 'sqlite');
if ($defaultConnection === 'sqlite' && ($dbHost !== null || $databaseUrl !== null)) {
    $defaultConnection = 'mysql';
}

return [

    'default' => $defaultConnection,

    'connections' => [

        'sqlite' => [
            'driver'                  => 'sqlite',
            'url'                     => env('DB_URL'),
            'database'                => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix'                  => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
            'busy_timeout'            => null,
            'journal_mode'            => null,
            'synchronous'             => null,
        ],

        'mysql' => [
            'driver'         => 'mysql',
            'url'            => $databaseUrl,
            'host'           => $dbHost ?? '127.0.0.1',
            'port'           => $dbPort,
            'database'       => $dbDatabase,
            'username'       => $dbUsername,
            'password'       => $dbPassword,
            'unix_socket'    => env('DB_SOCKET', ''),
            'charset'        => env('DB_CHARSET', 'utf8mb4'),
            'collation'      => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix'         => '',
            'prefix_indexes' => true,
            'strict'         => true,
            'engine'         => null,
            'options'        => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'mariadb' => [
            'driver'         => 'mariadb',
            'url'            => env('DB_URL'),
            'host'           => env('DB_HOST', '127.0.0.1'),
            'port'           => env('DB_PORT', '3306'),
            'database'       => env('DB_DATABASE', 'forge'),
            'username'       => env('DB_USERNAME', 'forge'),
            'password'       => env('DB_PASSWORD', ''),
            'unix_socket'    => env('DB_SOCKET', ''),
            'charset'        => env('DB_CHARSET', 'utf8mb4'),
            'collation'      => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix'         => '',
            'prefix_indexes' => true,
            'strict'         => true,
            'engine'         => null,
            'options'        => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'pgsql' => [
            'driver'         => 'pgsql',
            'url'            => env('DB_URL'),
            'host'           => env('DB_HOST', '127.0.0.1'),
            'port'           => env('DB_PORT', '5432'),
            'database'       => env('DB_DATABASE', 'forge'),
            'username'       => env('DB_USERNAME', 'forge'),
            'password'       => env('DB_PASSWORD', ''),
            'charset'        => 'utf8',
            'prefix'         => '',
            'prefix_indexes' => true,
            'search_path'    => 'public',
            'sslmode'        => 'prefer',
        ],

        'sqlsrv' => [
            'driver'         => 'sqlsrv',
            'url'            => env('DB_URL'),
            'host'           => env('DB_HOST', 'localhost'),
            'port'           => env('DB_PORT', '1433'),
            'database'       => env('DB_DATABASE', 'forge'),
            'username'       => env('DB_USERNAME', 'forge'),
            'password'       => env('DB_PASSWORD', ''),
            'charset'        => env('DB_CHARSET', 'utf8'),
            'prefix'         => '',
            'prefix_indexes' => true,
        ],

    ],

    'migrations' => [
        'table'                => 'migrations',
        'update_date_on_publish' => true,
    ],

    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix'  => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
        ],

        'default' => [
            'url'      => env('REDIS_URL'),
            'host'     => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port'     => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],

        'cache' => [
            'url'      => env('REDIS_URL'),
            'host'     => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port'     => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],

    ],

];

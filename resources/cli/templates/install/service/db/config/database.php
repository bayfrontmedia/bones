<?php

/*
 * For more information, see:
 * https://github.com/bayfrontmedia/simple-pdo/blob/master/docs/getting-started.md#factory-setup
 */

use Bayfront\Bones\Application\Utilities\App;
use Bayfront\SimplePdo\Db;

$options = [];

if (App::getEnv('DB_SECURE_TRANSPORT')) {
    $options = [
        PDO::MYSQL_ATTR_SSL_CA => true,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
    ];
}

return [
    Db::DB_DEFAULT => [ // Connection name
        'adapter' => App::getEnv('DB_ADAPTER'), // Adapter to use
        'host' => App::getEnv('DB_HOST'),
        'port' => App::getEnv('DB_PORT'),
        'database' => App::getEnv('DB_DATABASE'),
        'user' => App::getEnv('DB_USER'),
        'password' => App::getEnv('DB_PASSWORD'),
        'options' => $options
    ]
];
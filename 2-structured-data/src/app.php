<?php

/*
 * Copyright 2015 Google Inc. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * Create a new Silex Application with Twig.  Configure it for debugging.
 * Follows Silex Skeleton pattern.
 */
use Google\Auth\Credentials\GCECredentials;
use Google\Cloud\Samples\Bookshelf\DataModel\Sql;
use Google\Cloud\Samples\Bookshelf\DataModel\Datastore;
use Google\Cloud\Samples\Bookshelf\DataModel\MongoDb;
use Silex\Application;
use Silex\Provider\TwigServiceProvider;
use Symfony\Component\Yaml\Yaml;

$app = new Application();

// register twig
$app->register(new TwigServiceProvider(), array(
    'twig.path' => __DIR__ . '/../templates',
    'twig.options' => array(
        'strict_variables' => false,
    ),
));

// parse configuration
$config = getenv('BOOKSHELF_CONFIG') ?:
    __DIR__ . '/../config/' . 'settings.yml';

$app['config'] = Yaml::parse(file_get_contents($config));

// determine the datamodel backend using the app configuration
$app['bookshelf.model'] = function ($app) {
    /** @var array $config */
    $config = $app['config'];
    if (empty($config['bookshelf_backend'])) {
        throw new \DomainException('"bookshelf_backend" must be set in bookshelf config');
    }

    // Data Model
    switch ($config['bookshelf_backend']) {
        case 'mongodb':
            return new MongoDb(
                $config['mongo_url'],
                $config['mongo_database'],
                $config['mongo_collection']
            );
        case 'datastore':
            return new Datastore(
                $config['google_project_id']
            );
        case 'mysql':
            // Add Unix Socket for CloudSQL 2nd Gen when applicable
            $socket = GCECredentials::onGce()
                ? ';unix_socket=/cloudsql/' . $config['mysql_connection_name']
                : '';
            if (getenv('GAE_INSTANCE')) {
                $mysql_dsn_deployed = 'mysql:unix_socket=/mysql/' . $config['mysql_connection_name'] . ';dbname=' . $config['mysql_database_name'];
                return new Sql(
                    $mysql_dsn_deployed . $socket,
                    $config['mysql_user'],
                    $config['mysql_password']
                );
            } else {
                $mysql_dsn_local = 'mysql:host=127.0.0.1;port='. $config['mysql_port'] . ';dbname=' . $config['mysql_database_name'];
                return new Sql(
                    $mysql_dsn_local . $socket,
                    $config['mysql_user'],
                    $config['mysql_password']
                );
            }       
        case 'postgres':
            // Add Unix Socket for Postgres when applicable
            $socket = GCECredentials::onGce()
                ? ';host=/cloudsql/' . $config['postgres_connection_name']
                : '';
            if (getenv('GAE_INSTANCE')) {
                $postgres_dsn_deployed = 'pgsql:host=/cloudsql/' . $config['postgres_connection_name'] . ';dbname=' . $config['postgres_database_name'];
                return new Sql(
                    $postgres_dsn_deployed . $socket,
                    $config['postgres_user'],
                    $config['postgres_password']
                );
            } else {
                $postgres_dsn_local = 'pgsql:host=127.0.0.1;port=' . $config['postgres_port'] . ';dbname=' . $config['postgres_database_name'];
                return new Sql(
                    $postgres_dsn_local . $socket,
                    $config['postgres_user'],
                    $config['postgres_password']
                );
            }       
        default:
            throw new \DomainException("Invalid \"bookshelf_backend\" given: $config[bookshelf_backend]. "
                . "Possible values are mysql, postgres, mongodb, or datastore.");
    }
};

// Turn on debug locally
if (in_array(@$_SERVER['REMOTE_ADDR'], ['127.0.0.1', 'fe80::1', '::1'])
    || php_sapi_name() === 'cli-server'
) {
    $app['debug'] = true;
} else {
    $app['debug'] = filter_var(getenv('BOOKSHELF_DEBUG'),
                               FILTER_VALIDATE_BOOLEAN);
}

// add service parameters
$app['bookshelf.page_size'] = 10;

return $app;

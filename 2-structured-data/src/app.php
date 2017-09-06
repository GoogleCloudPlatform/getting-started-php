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
            $mysql_dsn = Sql::getMysqlDsn(
                $config['cloudsql_database_name'],
                $config['cloudsql_port'],
                getenv('GAE_INSTANCE') ? $config['cloudsql_connection_name'] : null
            );
            return new Sql(
                $mysql_dsn,
                $config['cloudsql_user'],
                $config['cloudsql_password']
            );
        case 'postgres':
            $postgres_dsn = Sql::getPostgresDsn(
                $config['cloudsql_database_name'],
                $config['cloudsql_port'],
                getenv('GAE_INSTANCE') ? $config['cloudsql_connection_name'] : null
            );
            return new Sql(
                $postgres_dsn,
                $config['cloudsql_user'],
                $config['cloudsql_password']
            );
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
    $app['debug'] = filter_var(
        getenv('BOOKSHELF_DEBUG'),
                               FILTER_VALIDATE_BOOLEAN
    );
}

// add service parameters
$app['bookshelf.page_size'] = 10;

return $app;

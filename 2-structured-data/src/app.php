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

// determine the datamodel backend using the app configuration
$app['bookshelf.model'] = function ($app) {
    // Data Model
    $backend = getenv('BOOKSHELF_BACKEND') ?: 'datastore';
    switch ($backend) {
        case 'datastore':
            return new Datastore(
                getenv('GOOGLE_CLOUD_PROJECT')
            );
        case 'mysql':
        case 'postgres':
            $dbName = getenv('CLOUDSQL_DATABASE_NAME') ?: 'bookshelf';
            $connectionName = getenv('CLOUDSQL_CONNECTION_NAME');
            if (getenv('GAE_INSTANCE')) {
                $dsn = ($backend === 'mysql')
                    ? Sql::getMysqlDsn($dbName, $connectionName)
                    : Sql::getPostgresDsn($dbName, $connectionName);
            } else {
                $dsn = ($backend === 'mysql')
                    ? Sql::getMysqlDsnForProxy($dbName)
                    : Sql::getPostgresDsnForProxy($dbName);

            }
            return new Sql(
                $dsn,
                getenv('CLOUDSQL_USER'),
                getenv('CLOUDSQL_PASSWORD')
            );
        case 'mongodb':
            return new MongoDb(
                getenv('MONGO_URL'),
                getenv('MONGO_DATABASE'),
                getenv('MONGO_COLLECTION')
            );
        default:
            throw new \DomainException("Invalid \"BOOKSHELF_BACKEND\" given"
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

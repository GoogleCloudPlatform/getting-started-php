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

use Google\Cloud\Samples\Bookshelf\DataModel\CloudSql;
use Google\Cloud\Samples\Bookshelf\DataModel\Datastore;
use Google\Cloud\Samples\Bookshelf\DataModel\MongoDb;
use Google\Cloud\Samples\Bookshelf\FileSystem\CloudStorage;

require_once __DIR__ . '/../vendor/autoload.php';

/** @var Silex\Application $app */
$app = require __DIR__ . '/../src/app.php';
require __DIR__ . '/../src/controllers.php';

/** @var array $config */
$config = $app['config'];

// Cloud Storage
$app['bookshelf.storage'] = new CloudStorage($config['google_project_id']);

// Data Model
switch ($config['bookshelf_backend']) {
    case 'mongodb':
        $app['bookshelf.model'] = new MongoDb(
            $config['mongo_url'],
            $config['mongo_namespace']
        );
        break;
    case 'datastore':
        $app['bookshelf.model'] = new Datastore(
            $config['google_project_id']
        );
        break;
    case 'cloudsql':
        $app['bookshelf.model'] = new CloudSql(
            $config['mysql_dsn'],
            $config['mysql_user'],
            $config['mysql_password']
        );
        break;
    default:
        throw new Exception("Invalid BOOKSHELF_DATA_BACKEND given: $config[bookshelf_backend]. "
            . "Possible values are cloudsql, mongodb, or datastore.");
}

$app->run();

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
use Google\Cloud\Bookshelf\CloudFirestore;
use Google\Cloud\Bookshelf\CloudStorage;
use Silex\Application;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;

$app = new Application();

// register twig
$app->register(new TwigServiceProvider(), array(
    'twig.path' => __DIR__ . '/../templates',
    'twig.options' => array(
        'strict_variables' => false,
    ),
));

// register the url generator
$app->register(new UrlGeneratorServiceProvider);

// [START logging]
$app->register(new Silex\Provider\MonologServiceProvider(), [
    'monolog.handler' => new Monolog\Handler\ErrorLogHandler(),
]);
// [END logging]

// Cloud Storage
$app['bookshelf.storage'] = function ($app) {
    $projectId = getenv('GOOGLE_CLOUD_PROJECT');
    $bucketName = $projectId . '.appspot.com';
    return new CloudStorage($projectId, $bucketName);
};

// determine the datamodel backend using the app configuration
$app['bookshelf.db'] = function ($app) {
    $projectId = getenv('GOOGLE_CLOUD_PROJECT');
    $collectionName = getenv('CLOUDSQL_COLLECTION_NAME') ?: 'books';
    return new CloudFirestore($projectId, $collectionName);
};

// Turn on debugging
$app['debug'] = true;

// add service parameters
$app['bookshelf.page_size'] = 10;

// Register stackdriver error handling
Google\Cloud\ErrorReporting\Bootstrap::init();
$app->error(function(\Exception $e, $code ) use ( $app ) {
    Google\Cloud\ErrorReporting\Bootstrap::exceptionHandler($e);
});

return $app;

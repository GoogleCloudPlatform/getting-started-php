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
use Google\Cloud\Bookshelf\CloudSql;
use Google\Cloud\Bookshelf\CloudStorage;
use Silex\Application;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Symfony\Component\Yaml\Yaml;

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

// register the session handler
// [START session]
$app->register(new SessionServiceProvider);
// fall back on PHP's "session.save_handler" for session storage
$app['session.storage.handler'] = null;
$app['user'] = function ($app) {
    /** @var Symfony\Component\HttpFoundation\Session\Session $session */
    $session = $app['session'];

    return $session->has('user') ? $session->get('user') : null;
};
// [END session]

// [START logging]
$app->register(new Silex\Provider\MonologServiceProvider(), [
    'monolog.handler' => new Monolog\Handler\ErrorLogHandler(),
]);
// [END logging]

// create the google authorization client
// [START google_client]
$app['google_client'] = function ($app) {
    /** @var Symfony\Component\Routing\Generator\UrlGenerator $urlGen */
    $urlGen = $app['url_generator'];
    $redirectUri = $urlGen->generate('login_callback', [], $urlGen::ABSOLUTE_URL);
    return new Google_Client([
        'client_id'     => getenv('GOOGLE_CLIENT_ID'),
        'client_secret' => getenv('GOOGLE_CLIENT_SECRET'),
        'redirect_uri'  => $redirectUri,
    ]);
    $client->setLogger($app['monolog']);
    return $client;
};
// [END google_client]

// Cloud Storage
$app['bookshelf.storage'] = function ($app) {
    $projectId = getenv('GOOGLE_CLOUD_PROJECT');
    $bucketName = $projectId . '.appspot.com';
    return new CloudStorage($projectId, $bucketName);
};

// determine the datamodel backend using the app configuration
$app['bookshelf.db'] = function ($app) {
    $dbName = getenv('CLOUDSQL_DATABASE_NAME') ?: 'bookshelf';
    $connectionName = getenv('CLOUDSQL_CONNECTION_NAME');
    $port = getenv('CLOUDSQL_PORT');
    if (getenv('GAE_INSTANCE')) {
        $dsn = CloudSql::getMysqlDsn($dbName, $connectionName);
    } else {
        $dsn = CloudSql::getMysqlDsnForProxy($dbName, $port);
    }
    return new CloudSql(
        $dsn,
        getenv('CLOUDSQL_USER'),
        getenv('CLOUDSQL_PASSWORD')
    );
};

// Turn on debugging
$app['debug'] = true;

// add service parameters
$app['bookshelf.page_size'] = 10;

return $app;

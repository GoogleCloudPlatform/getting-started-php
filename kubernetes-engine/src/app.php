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
use Google\Cloud\Logger\AppEngineFlexHandler;
// [START pubsub_client]
use Google\Cloud\PubSub\PubSubClient;
// [END pubsub_client]
use Google\Cloud\Samples\Bookshelf\DataModel\Sql;
use Google\Cloud\Samples\Bookshelf\DataModel\Datastore;
use Google\Cloud\Samples\Bookshelf\DataModel\MongoDb;
use Google\Cloud\Samples\Bookshelf\FileSystem\CloudStorage;
// [START add_worker_ns]
use Google\Cloud\Samples\Bookshelf\PubSub\LookupBookDetailsJob;
use Google\Cloud\Samples\Bookshelf\PubSub\Worker;
// [END add_worker_ns]
// [START pubsub_server_ns]
use Google\Cloud\Samples\Bookshelf\PubSub\HealthCheckListener;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
// [END pubsub_server_ns]
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

// parse configuration
$config = getenv('BOOKSHELF_CONFIG') ?:
    __DIR__ . '/../config/' . 'settings.yml';

$app['config'] = Yaml::parse(file_get_contents($config));

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

// add AppEngineFlexHandler on prod
// [START logging]
$app->register(new Silex\Provider\MonologServiceProvider());
if (isset($_SERVER['GAE_VM']) && $_SERVER['GAE_VM'] === 'true') {
    $app['monolog.handler'] = new AppEngineFlexHandler();
} else {
    $app['monolog.handler'] = new Monolog\Handler\ErrorLogHandler();
}
// [END logging]

// create the google authorization client
// [START google_client]
$app['google_client'] = function ($app) {
    $client = new Google_Client([
        'client_id'     => $app['config']['google_client_id'],
        'client_secret' => $app['config']['google_client_secret'],
    ]);
    $client->setLogger($app['monolog']);
    if ($app['routes']->get('login_callback')) {
        /** @var Symfony\Component\Routing\Generator\UrlGenerator $urlGen */
        $urlGen = $app['url_generator'];
        $redirectUri = $urlGen->generate('login_callback', [], $urlGen::ABSOLUTE_URL);
        $client->setRedirectUri($redirectUri);
    }
    return $client;
};
// [END google_client]

// [START pubsub_client]
$app['pubsub.client'] = function ($app) {
    // create the pubsub client
    $projectId = $app['config']['google_project_id'];
    $pubsub = new PubSubClient([
        'projectId' => $projectId,
    ]);
    return $pubsub;
};
// [END pubsub_client]

// [START pubsub_topic]
$app['pubsub.topic'] = function ($app) {
    // create the topic if it does not exist.
    /** @var Google\Cloud\PubSub\PubSubClient **/
    $pubsub = $app['pubsub.client'];
    $topicName = $app['config']['pubsub_topic_name'];
    $topic = $pubsub->topic($topicName);
    if (!$topic->exists()) {
        $topic->create();
    }
    return $topic;
};
// [END pubsub_topic]

$app['pubsub.subscription'] = function ($app) {
    // create the subscription if it does not exist.
    /** @var Google\Cloud\PubSub\Topic $topic **/
    $topic = $app['pubsub.topic'];
    $subName = $app['config']['pubsub_subscription_name'];
    $subscription = $topic->subscription($subName);
    if (!$subscription->exists()) {
        $subscription->create();
    }
    return $subscription;
};

$app['pubsub.server'] = function ($app) {
    /** @var Monolog\Logger $logger **/
    $logger = $app['monolog'];
    // [START pubsub_server]
    // Listen to port 8080 for our health checker
    $server = IoServer::factory(
        new HttpServer(new HealthCheckListener($logger)),
        8080
    );
    // [END pubsub_server]
    // [START add_worker]
    // create the job and worker
    $job = new LookupBookDetailsJob($app['bookshelf.model'], $app['google_client']);
    $worker = new Worker($app['pubsub.subscription'], $job, $logger);
    // add our worker to the event loop
    $server->loop->addPeriodicTimer(0, $worker);
    // [END add_worker]
    return $server;
};

// Cloud Storage
$app['bookshelf.storage'] = function ($app) {
    /** @var array $config */
    $config = $app['config'];
    $projectId = $config['google_project_id'];
    $bucketName = $projectId . '.appspot.com';
    return new CloudStorage($projectId, $bucketName);
};

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

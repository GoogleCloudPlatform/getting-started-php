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
$app['user'] = function ($app) {
    /** @var Symfony\Component\HttpFoundation\Session\Session $session */
    $session = $app['session'];

    return $session->has('user') ? $session->get('user') : null;
};
// [END session]

// add logging to stderr
// [START logging]
$app->register(new Silex\Provider\MonologServiceProvider());
$app['monolog.handler'] = new Monolog\Handler\ErrorLogHandler();
// [END logging]

// create the google authorization client
// [START google_client]
$app['google_client'] = function ($app) {
  /** @var Symfony\Component\Routing\Generator\UrlGenerator $urlGen */
  $urlGen = $app['url_generator'];
  $redirectUri = $urlGen->generate('login_callback', [], $urlGen::ABSOLUTE_URL);
  return new Google_Client([
    'client_id'     => $app['config']['google_client_id'],
    'client_secret' => $app['config']['google_client_secret'],
    'redirect_uri'  => $redirectUri,
  ]);
};
// [END google_client]

// turn debug on by default
$app['debug'] = !in_array(
    getenv('BOOKSHELF_DEBUG'),
    ['false', '', '0', 'off', 'no']
);

// add service parameters
$app['bookshelf.page_size'] = 10;

return $app;

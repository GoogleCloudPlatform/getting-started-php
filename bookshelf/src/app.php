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
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Google\Cloud\Samples\Bookshelf\User\SimpleUser;

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

// turn debug on by default
$app['debug'] = !in_array(
    getenv('BOOKSHELF_DEBUG'),
    ['false', '', '0', 'off', 'no']
);

// create the user object
$app['user'] = function ($app) {
  return SimpleUser::createFromRequest($app['request']);
};

// create the google authorization client
$app['google_client'] = function ($app) {
  /** @var Symfony\Component\Routing\Generator\UrlGenerator $urlGen */
  $urlGen = $app['url_generator'];
  $redirectUri = $urlGen->generate('login_callback', [], $urlGen::ABSOLUTE_URL);

  return new Google_Client([
    'client_id'     => getenv('GOOGLE_CLIENT_ID'),
    'client_secret' => getenv('GOOGLE_CLIENT_SECRET'),
    'redirect_uri'  => $redirectUri,
  ]);
};

// add service parameters
$app['bookshelf.page_size'] = 10;

// add logging to stderr
$app->register(new Silex\Provider\MonologServiceProvider());
$app['monolog.handler'] = new Monolog\Handler\ErrorLogHandler();

return $app;

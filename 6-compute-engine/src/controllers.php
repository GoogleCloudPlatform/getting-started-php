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

namespace Google\Cloud\Samples\Bookshelf;

/*
 * Adds all the controllers to $app.  Follows Silex Skeleton pattern.
 */
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Google\Cloud\Samples\Bookshelf\DataModel\DataModelInterface;
use Google\Cloud\Samples\Bookshelf\FileSystem\CloudStorage;

$app->get('/', function (Request $request) use ($app) {
    return $app->redirect('/books/');
});

$app->get('/books/', function (Request $request) use ($app) {
    /** @var DataModelInterface $model */
    $model = $app['bookshelf.model'];
    /** @var Twig_Environment $twig */
    $twig = $app['twig'];
    $token = $request->query->get('page_token');
    $bookList = $model->listBooks($app['bookshelf.page_size'], $token);

    return $twig->render('list.html.twig', array(
        'books' => $bookList['books'],
        'next_page_token' => $bookList['cursor'],
    ));
});

$app->get('/books/add', function () use ($app) {
    /** @var Twig_Environment $twig */
    $twig = $app['twig'];

    return $twig->render('form.html.twig', array(
        'action' => 'Add',
        'book' => array(),
    ));
});

$app->post('/books/add', function (Request $request) use ($app) {
    /** @var DataModelInterface $model */
    $model = $app['bookshelf.model'];
    /** @var CloudStorage $storage */
    $storage = $app['bookshelf.storage'];
    $files = $request->files;
    $book = $request->request->all();
    $image = $files->get('image');
    if ($image && $image->isValid()) {
        $book['imageUrl'] = $storage->storeFile(
            $image->getRealPath(),
            $image->getMimeType()
        );
    }
    if (!empty($book['publishedDate'])) {
        $book['publishedDate'] = date('c', strtotime($book['publishedDate']));
    }
    if ($app['user']->getLoggedIn()) {
        $book['createdBy'] = $app['user']->name;
        $book['createdById'] = $app['user']->id;
    }
    $id = $model->create($book);

    return $app->redirect("/books/$id");
});

$app->get('/books/{id}', function ($id) use ($app) {
    /** @var DataModelInterface $model */
    $model = $app['bookshelf.model'];
    $book = $model->read($id);
    if (!$book) {
        return new Response('', Response::HTTP_NOT_FOUND);
    }
    /** @var Twig_Environment $twig */
    $twig = $app['twig'];

    return $twig->render('view.html.twig', array('book' => $book));
});

$app->get('/books/{id}/edit', function ($id) use ($app) {
    /** @var DataModelInterface $model */
    $model = $app['bookshelf.model'];
    $book = $model->read($id);
    if (!$book) {
        return new Response('', Response::HTTP_NOT_FOUND);
    }
    /** @var Twig_Environment $twig */
    $twig = $app['twig'];

    return $twig->render('form.html.twig', array(
        'action' => 'Edit',
        'book' => $book,
    ));
});

$app->post('/books/{id}/edit', function (Request $request, $id) use ($app) {
    $book = $request->request->all();
    $book['id'] = $id;
    /** @var CloudStorage $storage */
    $storage = $app['bookshelf.storage'];
    /** @var DataModelInterface $model */
    $model = $app['bookshelf.model'];
    $files = $request->files;
    $image = $files->get('image');
    if ($image && $image->isValid()) {
        $book['imageUrl'] = $storage->storeFile(
            $image->getRealPath(),
            $image->getMimeType()
        );
    }
    if (!empty($book['publishedDate'])) {
        $book['publishedDate'] = date('c', strtotime($book['publishedDate']));
    }
    if ($model->update($book)) {
        return $app->redirect("/books/$id");
    }

    return new Response('', Response::HTTP_NOT_FOUND);
});

$app->post('/books/{id}/delete', function ($id) use ($app) {
        /** @var DataModelInterface $model */
        $model = $app['bookshelf.model'];
        $book = $model->read($id);
        if ($book) {
            $model->delete($id);

            return $app->redirect('/books/', Response::HTTP_SEE_OTHER);
        }

        return new Response('', Response::HTTP_NOT_FOUND);
    }
);

$app->get('/login', function () use ($app) {
    /** @var Google_Client $client */
    $client = $app['google_client'];

    $scopes = [ \Google_Service_Oauth2::USERINFO_PROFILE ];
    $authUrl = $client->createAuthUrl($scopes);

    return $app->redirect($authUrl);
})->bind('login');

$app->get('/login/callback', function () use ($app) {
    /** @var Request $request */
    $request = $app['request'];

    if (!$code = $request->query->get('code')) {
        return new Response('Code required', Response::HTTP_BAD_REQUEST);
    }

    /** @var Google_Client $client */
    $client = $app['google_client'];
    $authResponse = $client->fetchAccessTokenWithAuthCode($code);

    if ($client->getAccessToken()) {
        $userInfo = $client->verifyIdToken();

        // set the user info in a cookie and redirect to the homepage
        $cookie = new Cookie('google_user_info', json_encode($userInfo));
        $response = new Response('', Response::HTTP_FOUND, ['Location' => '/']);
        $response->headers->setCookie($cookie);

        return $response;
    }

    // an error occured while trying to authorize - display it
    return new Response($authResponse['error_description'], 400);

})->bind('login_callback');

$app->get('/logout', function () use ($app) {
    $response = new Response('', Response::HTTP_FOUND, ['Location' => '/']);
    $response->headers->clearCookie('google_user_info');

    return $response;
})->bind('logout');


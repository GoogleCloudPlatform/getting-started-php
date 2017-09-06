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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Google\Cloud\Samples\Bookshelf\DataModel\DataModelInterface;
use Google\Cloud\Samples\Bookshelf\FileSystem\CloudStorage;

$app->get('/', function (Request $request) use ($app) {
    return $app->redirect('/books/');
});

// [START index]
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
// [END index]

// [START add]
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
        $book['image_url'] = $storage->storeFile(
            $image->getRealPath(),
            $image->getMimeType()
        );
    }
    if ($app['user']) {
        $book['created_by'] = $app['user']['name'];
        $book['created_by_id'] = $app['user']['id'];
    }

    # [START publish_topic]
    if ($id = $model->create($book)) {
        /** @var Google\Cloud\PubSub\PubSub\Topic $topic */
        $topic = $app['pubsub.topic'];
        $topic->publish([
            'data' => 'Updated Book',
            'attributes' => [
                'id' => $id
            ]
        ]);
    }
    # [END publish_topic]

    return $app->redirect("/books/$id");
});
// [END add]

// [START show]
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
// [END show]

// [START edit]
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
    if (!$model->read($id)) {
        return new Response('', Response::HTTP_NOT_FOUND);
    }
    // [START add_image]
    $files = $request->files;
    $image = $files->get('image');
    if ($image && $image->isValid()) {
        $book['image_url'] = $storage->storeFile(
            $image->getRealPath(),
            $image->getMimeType()
        );
    }
    // [END add_image]
    if ($model->update($book)) {
        /** @var Google\Cloud\PubSub\Topic $topic */
        $topic = $app['pubsub.topic'];
        $topic->publish([
            'data' => 'Edit Book',
            'attributes' => [
                'id' => $id
            ]
        ]);

        return $app->redirect("/books/$id");
    }

    return new Response('Could not update book');
});
// [END edit]

// [START delete]
$app->post('/books/{id}/delete', function ($id) use ($app) {
    /** @var DataModelInterface $model */
    $model = $app['bookshelf.model'];
    $book = $model->read($id);
    if ($book) {
        $model->delete($id);
        // [START delete_image]
        if (!empty($book['image_url'])) {
            /** @var CloudStorage $storage */
            $storage = $app['bookshelf.storage'];
            $storage->deleteFile($book['image_url']);
        }
        // [END delete_image]

        // [START logging]
        $app['monolog']->notice('Deleted Book: ' . $book['id']);
        // [END logging]

        return $app->redirect('/books/', Response::HTTP_SEE_OTHER);
    }

    return new Response('', Response::HTTP_NOT_FOUND);
});
// [END delete]

# [START login]
$app->get('/login', function () use ($app) {
    /** @var Google_Client $client */
    $client = $app['google_client'];
    $scopes = [ \Google_Service_Oauth2::USERINFO_PROFILE ];
    $authUrl = $client->createAuthUrl($scopes);

    return $app->redirect($authUrl);
})->bind('login');
# [END login]

# [START login_callback]
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

        /** @var Symfony\Component\HttpFoundation\Session\Session $session */
        $session = $app['session'];
        $session->set('user', [
            'id'      => $userInfo['sub'],
            'name'    => $userInfo['name'],
            'picture' => $userInfo['picture'],
        ]);

        return new Response('', Response::HTTP_FOUND, ['Location' => '/']);
    }

    // an error occured while trying to authorize - display it
    return new Response($authResponse['error_description'], 400);
})->bind('login_callback');
# [END login_callback]

# [START logout]
$app->get('/logout', function () use ($app) {
    /** @var Symfony\Component\HttpFoundation\Session\Session $session */
    $session = $app['session'];
    $session->remove('user');

    return new Response('', Response::HTTP_FOUND, ['Location' => '/']);
})->bind('logout');
# [END logout]

$app->get('/_ah/health', function (Request $request) use ($app) {
    return 'OK';
});

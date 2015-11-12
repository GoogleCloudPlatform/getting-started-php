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

$listBooks = function (Request $request) use ($app) {
    /** @var DataModel $model */
    $model = $app['bookshelf.model'];
    /** @var Twig_Environment $twig */
    $twig = $app['twig'];
    $token = $request->query->get('page_token');
    $bookList = $model->listBooks($app['bookshelf.page_size'], $token);

    return $twig->render('list.html', array(
        'books' => $bookList['books'],
        'next_page_token' => $bookList['cursor'],
    ));
};
$app->get('/', $listBooks);
$app->get('/books/', $listBooks);

$app->get('/books/add', function () use ($app) {
    /** @var Twig_Environment $twig */
    $twig = $app['twig'];

    return $twig->render('form.html', array(
        'action' => 'Add',
        'book' => array(),
    ));
});

$app->post('/books/add', function (Request $request) use ($app) {
    /** @var DataModel $model */
    $model = $app['bookshelf.model'];
    /** @var ImageStorage $storage */
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
    $id = $model->create($book);

    return $app->redirect("/books/$id");
});

$app->get('/books/{id}', function ($id) use ($app) {
    /** @var DataModel $model */
    $model = $app['bookshelf.model'];
    $book = $model->read($id);
    if (!$book) {
        return new Response('', 404);
    }
    /** @var Twig_Environment $twig */
    $twig = $app['twig'];

    return $twig->render('view.html', array('book' => $book));
});

$app->get('/books/{id}/edit', function ($id) use ($app) {
    /** @var DataModel $model */
    $model = $app['bookshelf.model'];
    $book = $model->read($id);
    if (!$book) {
        return new Response('', 404);
    }
    $book = $model->read($id);
    /** @var Twig_Environment $twig */
    $twig = $app['twig'];

    return $twig->render('form.html', array(
        'action' => 'Edit',
        'book' => $book,
    ));
});

$app->post('/books/{id}/edit', function (Request $request, $id) use ($app) {
    $book = $request->request->all();
    $book['id'] = $id;
    /** @var ImageStorage $storage */
    $storage = $app['bookshelf.storage'];
    /** @var DataModel $model */
    $model = $app['bookshelf.model'];
    $files = $request->files;
    $image = $files->get('image');
    // If the user uploaded a new image, we have to clean up the old image.
    $oldImageUrl = '';
    if ($image && $image->isValid()) {
        $oldBook = $model->read($id);
        if (isset($oldBook['imageUrl'])) {
            $oldImageUrl = $oldBook['imageUrl'];
        }
        $book['imageUrl'] = $storage->storeFile(
            $image->getRealPath(),
            $image->getMimeType()
        );
    }
    if ($model->update($book)) {
        if ($oldImageUrl) {
            $storage->deleteFile($oldImageUrl);
        }

        return $app->redirect("/books/$id");
    } else {
        return new Response('', 404);
    }
});

$app->post('/books/{id}/delete',
    function ($id) use ($app) {
        /** @var DataModel $model */
        $model = $app['bookshelf.model'];
        $book = $model->read($id);
        if ($book) {
            $model->delete($id);
            /** @var ImageStorage $storage */
            $storage = $app['bookshelf.storage'];
            $imageUrl = isset($book['imageUrl']) ? $book['imageUrl'] : null;
            if ($imageUrl) {
                $storage->deleteFile($imageUrl);
            }

            return $app->redirect('/books/', 303);
        } else {
            return new Response('', 404);
        }
    });

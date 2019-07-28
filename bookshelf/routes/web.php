<?php
/*
 * Copyright 2019 Google Inc. All Rights Reserved.
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

use Illuminate\Http\Request;
use Illuminate\Http\Response;

$projectId = getenv('GOOGLE_CLOUD_PROJECT');
$collectionName = getenv('FIRESTORE_COLLECTION') ?: 'books';

# [START firestore_client]
// Use the client library to call Firestore
use Google\Cloud\Firestore\FirestoreClient;

$firestore = new FirestoreClient([
    'projectId' => $projectId,
]);
$collection =  $firestore->collection($collectionName);
# [END firestore_client]

# [START cloud_storage_client]
// Use the client library to call Cloud Storage
use Google\Cloud\Storage\StorageClient;

$storage = new StorageClient([
    'projectId' => $projectId,
]);
$bucketId = $projectId . '.appspot.com';
$gcsBucket = $storage->bucket($bucketId);
# [END cloud_storage_client]

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function (Request $request) use ($collection) {
    $pageSize = getenv('BOOKSHELF_PAGE_SIZE') ?: 10;
    $query = $collection->limit($pageSize)->orderBy('title');
    if ($token = $request->query->get('page_token')) {
        $lastBook = $collection->document($token)->snapshot();
        $query = $query->startAfter($lastBook);
    }

    return view('list', [
        'books' => $query->documents(),
        'pageSize' => $pageSize,
    ]);
});


// [START add]
$router->get('/books/add', function () {
    return view('form', [
        'action' => 'Add',
        'book' => [],
    ]);
});

$router->post('/books/add', function (Request $request) use ($collection, $gcsBucket) {
    $files = $request->files;
    $bookData = $request->request->all();
    $image = $files->get('image');
    if ($image && $image->isValid()) {
        $f = fopen($image->getRealPath(), 'r');
        $object = $gcsBucket->upload($f, [
            'metadata' => ['contentType' => $image->getMimeType()],
            'predefinedAcl' => 'publicRead',
        ]);
        $bookData['image_url'] = $object->info()['mediaLink'];
    }

    // Create the book
    $bookRef = $collection->newDocument();
    $bookRef->set($bookData);

    return redirect("/books/" . $bookRef->id());
});
// [END add]

// [START show]
$router->get('/books/{bookId}', function ($bookId) use ($collection) {
    # [START firestore_client_get_book]
    $bookRef = $collection->document($bookId);
    $snapshot = $bookRef->snapshot();
    # [END firestore_client_get_book]

    if (!$snapshot->exists()) {
        return new Response('', Response::HTTP_NOT_FOUND);
    }

    return view('show', ['book' => $snapshot]);
});
// [END show]

// [START edit]
$router->get('/books/{bookId}/edit', function ($bookId) use ($collection) {
    $bookRef = $collection->document($bookId);
    $snapshot = $bookRef->snapshot();

    if (!$snapshot->exists()) {
        return new Response('', Response::HTTP_NOT_FOUND);
    }

    return view('form', [
        'action' => 'Edit',
        'book' => $snapshot,
    ]);
});

$router->post('/books/{bookId}/edit', function (Request $request, $bookId) use ($collection, $gcsBucket) {
    $bookRef = $collection->document($bookId);
    $snapshot = $bookRef->snapshot();

    if (!$snapshot->exists()) {
        return new Response('', Response::HTTP_NOT_FOUND);
    }

    // Get book data from the request object
    $bookData = $request->request->all();
    $bookData['id'] = $bookId;

    // [START add_image]
    $files = $request->files;
    $image = $files->get('image');
    if ($image && $image->isValid()) {
        $f = fopen($image->getRealPath(), 'r');
        $object = $gcsBucket->upload($f, [
            'metadata' => ['contentType' => $image->getMimeType()],
            'predefinedAcl' => 'publicRead',
        ]);
        $bookData['image_url'] = $object->info()['mediaLink'];
    }
    // [END add_image]

    // Create array to update the Firestore document
    $updateData = [];
    foreach ($bookData as $key => $value) {
        $updateData[] = ['path' => $key, 'value' => $value];
    }

    if ($bookRef->update($updateData)) {
        return redirect("/books/$bookId");
    }

    return new Response('Could not update book');
});
// [END edit]

// [START delete]
$router->post('/books/{bookId}/delete', function ($bookId) use ($collection, $gcsBucket) {
    $bookRef = $collection->document($bookId);
    $snapshot = $bookRef->snapshot();

    if (!$snapshot->exists()) {
        return new Response('', Response::HTTP_NOT_FOUND);
    }

    $bookRef->delete();

    // [START delete_image]
    if ($imageUrl = $snapshot->get('image_url')) {
        $components = explode('/', parse_url($imageUrl, PHP_URL_PATH));
        $name = $components[count($components) - 1];
        $object = $gcsBucket->object($name);
        $object->delete();
    }
    // [END delete_image]

    return redirect('/', Response::HTTP_SEE_OTHER);
});
// [END delete]

$router->get('/logs', function (Request $request) {
    $monolog = new Monolog\Logger('app');
    $monolog->info('Hey, you triggered a custom log entry. Good job!');
    return redirect('/');
});

$router->get('/errors', function (Request $request) {
    throw new \Exception('This is an intentional exception.');
});

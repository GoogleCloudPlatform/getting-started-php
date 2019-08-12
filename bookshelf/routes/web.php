<?php
/*
 * Copyright 2019 Google LLC
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
use Google\Cloud\Core\Exception\FailedPreconditionException;

$projectId = getenv('GOOGLE_CLOUD_PROJECT');
$collectionName = getenv('FIRESTORE_COLLECTION') ?: 'books';
$bookFields = ['id', 'title', 'author', 'description', 'published_date', 'image_url'];

# [START bookshelf_firestore_client]
// Use the client library to call Firestore
use Google\Cloud\Firestore\FirestoreClient;

$firestore = new FirestoreClient([
    'projectId' => $projectId,
]);
$collection =  $firestore->collection($collectionName);
# [END bookshelf_firestore_client]

# [START bookshelf_cloud_storage_client]
// Use the client library to call Cloud Storage
use Google\Cloud\Storage\StorageClient;

$storage = new StorageClient([
    'projectId' => $projectId,
]);
$bucketId = $projectId . '_bucket';
$gcsBucket = $storage->bucket($bucketId);
# [END bookshelf_cloud_storage_client]

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

/**
 * The Bookshelf homepage, displaying a paginated list of books.
 *
 * @param $collection The Firestore collection for retrieving the list of books.
 */
$router->get('/', function (Request $request) use ($collection) {
    $pageSize = getenv('BOOKSHELF_PAGE_SIZE') ?: 10;
    $query = $collection->limit($pageSize)->orderBy('title');
    if ($token = $request->query->get('page_token')) {
        $lastBook = $collection->document($token)->snapshot();
        $query = $query->startAfter($lastBook);
    }

    try {
        $books = $query->documents();
    } catch (FailedPreconditionException $e) {
        // Firestore hasn't been enabled, catch the error gracefully.
        $books = [];
    }

    return view('list', [
        'books' => $books,
        'pageSize' => $pageSize,
    ]);
});

/**
 * A page displaying a form for creating a book.
 */
$router->get('/books/add', function () {
    return view('form', [
        'action' => 'Add',
        'book' => null,
    ]);
});

/**
 * Receives form data and creates a book object in the Firestore collection.
 *
 * @param $collection The Firestore collection for creating the book.
 * @param $gcsBucket The Storage Bucket object for storing the book cover image.
 * @param $bookFields Array of valid book fields.
 */
$router->post('/books/add', function (Request $request) use ($collection, $gcsBucket, $bookFields) {
    $bookData = $request->request->all();

    // Validate the book data
    if ($invalid = array_diff_key($bookData, array_flip($bookFields))) {
        throw new \Exception('unsupported field: ' . implode(', ', array_keys($invalid)));
    }

    $image = $request->files->get('image');
    if ($image && $image->isValid()) {
        $file = fopen($image->getRealPath(), 'r');
        $object = $gcsBucket->upload($file, [
            'metadata' => ['contentType' => $image->getMimeType()],
            'predefinedAcl' => 'publicRead',
        ]);
        $bookData['image_url'] = $object->info()['mediaLink'];
    }

    // Create the book
    $bookRef = $collection->newDocument();
    $bookRef->set($bookData);

    return redirect('/books/' . $bookRef->id());
});

/**
 * Page for viewing the details of a specific book.
 *
 * @param $bookId The Firestore book ID.
 * @param $collection The Firestore collection for retrieving the book to view.
 */
$router->get('/books/{bookId}', function ($bookId) use ($collection) {
    # [START bookshelf_firestore_client_get_book]
    $bookRef = $collection->document($bookId);
    $snapshot = $bookRef->snapshot();
    # [END bookshelf_firestore_client_get_book]

    if (!$snapshot->exists()) {
        return new Response('', Response::HTTP_NOT_FOUND);
    }

    return view('show', ['book' => $snapshot]);
});

/**
 * Page for editing a specific book.
 *
 * @param $bookId The Firestore book ID.
 * @param $collection The Firestore collection for retrieving book to edit.
 */
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

/**
 * Receives form data and edits a book object in the Firestore collection.
 *
 * @param $bookId The Firestore book ID.
 * @param $collection The Firestore collection for updating the book object.
 * @param $gcsBucket The Storage Bucket object for updating the book cover image.
 * @param $bookFields Array of valid book fields.
 */
$router->post('/books/{bookId}/edit', function (Request $request, $bookId) use ($collection, $gcsBucket, $bookFields) {
    $bookRef = $collection->document($bookId);
    $snapshot = $bookRef->snapshot();

    if (!$snapshot->exists()) {
        return new Response('', Response::HTTP_NOT_FOUND);
    }

    // Get book data from the request object
    $bookData = $request->request->all();
    $bookData['id'] = $bookId;

    // Validate the book data
    if ($invalid = array_diff_key($bookData, array_flip($bookFields))) {
        throw new \Exception('unsupported field: ' . implode(', ', array_keys($invalid)));
    }

    $image = $request->files->get('image');
    if ($image && $image->isValid()) {
        $file = fopen($image->getRealPath(), 'r');
        $object = $gcsBucket->upload($file, [
            'metadata' => ['contentType' => $image->getMimeType()],
            'predefinedAcl' => 'publicRead',
        ]);
        $bookData['image_url'] = $object->info()['mediaLink'];
    }

    $bookRef->set($bookData, ['merge' => true]);
    return redirect('/books/' . $bookId);
});

/**
 * Deletes the book object from the Firestore collection.
 *
 * @param $bookId The Firestore book ID.
 * @param $collection The Firestore collection for deleting the book object.
 * @param $gcsBucket The Storage Bucket object for deleting the book cover image.
 */
$router->post('/books/{bookId}/delete', function ($bookId) use ($collection, $gcsBucket) {
    $bookRef = $collection->document($bookId);
    $snapshot = $bookRef->snapshot();

    if (!$snapshot->exists()) {
        return new Response('', Response::HTTP_NOT_FOUND);
    }

    $bookRef->delete();

    if ($imageUrl = $snapshot->get('image_url')) {
        $components = explode('/', parse_url($imageUrl, PHP_URL_PATH));
        $name = $components[count($components) - 1];
        $object = $gcsBucket->object($name);
        $object->delete();
    }

    return redirect('/', Response::HTTP_SEE_OTHER);
});

/**
 * Sends a message of type INFO to Stackdriver Logging
 */
$router->get('/logs', function (Request $request) {
    $message = 'Hey, you triggered a custom log entry. Good job!';
    $monolog = new Monolog\Logger('app');
    $monolog->info($request->get('message') ?: $message);
    return redirect('/');
});

/**
 * Sends an exception to Stackdriver Error Reporting
 */
$router->get('/errors', function (Request $request) {
    $message = 'This is an intentional exception.';
    throw new \Exception($request->get('message') ?: $message);
});

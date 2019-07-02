<?php

use Illuminate\Http\Request;
use Illuminate\Http\Response;

$projectId = getenv('GCLOUD_PROJECT');

# [START firestore_client]
// Use the client library to call Firestore
use Google\Cloud\Firestore\FirestoreClient;
$firestore = new FirestoreClient([
    'projectId' => $projectId,
]);
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

$router->get('/', function (Request $request) use ($firestore) {
    $pageSize = 10;
    $collection = $firestore->collection('books');
    $query = $collection->limit($pageSize);
    if ($token = $request->query->get('page_token')) {
        $query = $query->startAfter($cursorDoc);
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

$router->post('/books/add', function (Request $request) use ($firestore, $gcsBucket) {
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
    $bookRef = $firestore->collection('books')->newDocument();
    $bookRef->set($bookData);

    return redirect("/books/" . $bookRef->id());
});
// [END add]

// [START show]
$router->get('/books/{bookId}', function ($bookId) use ($firestore) {
    # [START firestore_client_get_book]
    $bookRef = $firestore->collection('books')->document($bookId);
    $snapshot = $bookRef->snapshot();
    # [END firestore_client_get_book]

    if (!$snapshot->exists()) {
        return new Response('', Response::HTTP_NOT_FOUND);
    }

    return view('show', ['book' => $snapshot]);
});
// [END show]

// [START edit]
$router->get('/books/{bookId}/edit', function ($bookId) use ($firestore) {
    $bookRef = $firestore->collection('books')->document($bookId);
    $snapshot = $bookRef->snapshot();

    if (!$snapshot->exists()) {
        return new Response('', Response::HTTP_NOT_FOUND);
    }

    return view('form', [
        'action' => 'Edit',
        'book' => $snapshot,
    ]);
});

$router->post('/books/{bookId}/edit', function (Request $request, $bookId) use ($firestore, $gcsBucket) {
    $bookRef = $firestore->collection('books')->document($bookId);
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
$router->post('/books/{bookId}/delete', function ($bookId) use ($firestore, $gcsBucket) {
    $bookRef = $firestore->collection('books')->document($bookId);
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

    // [START logging]
    // $app['monolog']->notice('Deleted Book: ' . $book['id']);
    // [END logging]

    return redirect('/', Response::HTTP_SEE_OTHER);
});
// [END delete]

// $app->get('/exception', function (Request $request) use ($app) {
//     throw new \Exception('Test');
// });
<?php

require __DIR__ . '/vendor/autoload.php';

use Google\Cloud\Firestore\FirestoreClient;
use Google\Cloud\Firestore\Transaction;
use Google\Cloud\Translate\TranslateClient;
use Symfony\Component\HttpFoundation\Request;

function translateString(array $data)
{
    if (!$projectId = getenv('GOOGLE_CLOUD_PROJECT')) {
        throw new Exception('Env var GOOGLE_CLOUD_PROJECT must be set');
    }
    $translate = new TranslateClient([
        'projectId' => $projectId,
    ]);
    $firestore = new FirestoreClient([
        'projectId' => $projectId,
    ]);

    if (empty($data['language']) || empty($data['text'])) {
        throw new Exception('Error parsing data');
    }

    $docId = sprintf('%s:%s', $data['language'], base64_encode($data['text']));
    $docRef = $firestore->collection('translations')->document($docId);

    $translation = [
        'original' => $data['text'],
        'lang' => $data['language'],
    ];

    $firestore->runTransaction(
        function (Transaction $transaction) use ($translate, $translation, $docRef) {
            $snapshot = $transaction->snapshot($docRef);
            if ($snapshot->exists()) {
                // Do nothing if the document already exists
                return;
            }
            $result = $translate->translate($translation['original'], [
                'target' => $translation['lang'],
            ]);
            $transaction->set($docRef, $translation + [
                'translated' => $result['text'],
                'originalLang' => $result['source'],
            ]);
        }
    );
}

// Return early for tests
if (getenv('PHPUNIT_TESTS') === '1') {
    return;
}

$request = Request::createFromGlobals();
$message = json_decode($request->getContent(), true);
if (empty($message['message']['data'])) {
    throw new Exception('No message received');
}
$data = json_decode(base64_decode($message['message']['data']), true);
if (!$data) {
    throw new Exception('Error decoding data from message');
}
translateString($data);
echo "Done.";

<?php

use Google\Cloud\Firestore\FirestoreClient;
use Google\Cloud\Firestore\Transaction;
use Google\Cloud\Translate\TranslateClient;

function translateString(array $data)
{
    if (empty($data['language']) || empty($data['text'])) {
        throw new Exception('Error parsing translation data');
    }
    $translate = new TranslateClient();
    $firestore = new FirestoreClient();

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
                return; // Do nothing if the document already exists
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

    echo "Done.";
}

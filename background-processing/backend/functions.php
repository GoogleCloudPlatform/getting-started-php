<?php

# [START getting_started_background_translate]
# [START getting_started_background_translate_setup]
use Google\Cloud\Firestore\FirestoreClient;
use Google\Cloud\Firestore\Transaction;
use Google\Cloud\Translate\TranslateClient;

# [END getting_started_background_translate_setup]

/**
 * @param array $data {
 *     The PubSub message data containing text and target language.
 *
 *     @type string $text
 *           The full text to translate.
 *     @type string $language
 *           The target language for the translation.
 * }
 */
function translateString(array $data)
{
    if (empty($data['language']) || empty($data['text'])) {
        throw new Exception('Error parsing translation data');
    }

    # [START getting_started_background_translate_init]
    $firestore = new FirestoreClient();
    $translate = new TranslateClient();

    $translation = [
        'original' => $data['text'],
        'lang' => $data['language'],
    ];
    # [END getting_started_background_translate_init]

    # [START getting_started_background_translate_transaction]
    $docId = sprintf('%s:%s', $data['language'], base64_encode($data['text']));
    $docRef = $firestore->collection('translations')->document($docId);

    $firestore->runTransaction(
        function (Transaction $transaction) use ($translate, $translation, $docRef) {
            $snapshot = $transaction->snapshot($docRef);
            if ($snapshot->exists()) {
                return; // Do nothing if the document already exists
            }

            # [START getting_started_background_translate_string]
            $result = $translate->translate($translation['original'], [
                'target' => $translation['lang'],
            ]);
            # [END getting_started_background_translate_string]
            $transaction->set($docRef, $translation + [
                'translated' => $result['text'],
                'originalLang' => $result['source'],
            ]);
        }
    );
    # [END getting_started_background_translate_transaction]

    echo "Done.";
}
# [END getting_started_background_translate]

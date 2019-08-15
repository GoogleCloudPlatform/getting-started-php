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

use Google\Cloud\Firestore\FirestoreClient;
use Google\Cloud\PubSub\PubSubClient;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

if (!$projectId = getenv('GOOGLE_CLOUD_PROJECT')) {
    throw new Exception('Env var GOOGLE_CLOUD_PROJECT must be set');
}

/**
 * An example route using the Lumen framework.
 */
$router->get('/', function (Request $request) use ($projectId) {
    $firestore = new FirestoreClient([
        'projectId' => $projectId,
    ]);
    $translations = $firestore->collection('translations')->documents();
    return view('home', ['translations' => $translations]);
});

/**
 * An example route using the Lumen framework.
 */
$router->post('/request-translation', function (Request $request) use ($projectId) {
    $acceptableLanguages = ['de', 'en', 'es', 'fr', 'ja', 'sw'];
    if (!in_array($lang = $request->get('lang'), $acceptableLanguages)) {
        throw new Exception('Unsupported Language: ' . $lang);
    }
    $pubsub = new PubSubClient([
        'projectId' => $projectId,
    ]);
    $topic = $pubsub->topic('translate');
    $topic->publish(['data' => json_encode([
        'language' => $lang,
        'text' => $request->get('v'),
    ])]);
    $bookData = $request->request->all();

    return '';
});

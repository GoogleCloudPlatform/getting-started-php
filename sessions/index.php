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

# [START getting_started_sessions_all]
require_once __DIR__ . '/vendor/autoload.php';

use Google\Cloud\Firestore\FirestoreClient;

$projectId = getenv('GOOGLE_CLOUD_PROJECT');
# [START getting_started_sessions_register_handler]
// Instantiate the Firestore Client for your project ID.
$firestore = new FirestoreClient([
    'projectId' => $projectId,
]);

# [START getting_started_sessions_create_handler]
$handler = $firestore->sessionHandler(['gcLimit' => 500]);
# [END getting_started_sessions_create_handler]

// Configure PHP to use the the Firebase session handler.
session_set_save_handler($handler, true);
session_save_path('sessions');
session_start();
# [END getting_started_sessions_register_handler]

# [START getting_started_sessions_front_controller]
$colors = ['red', 'blue', 'green', 'yellow', 'pink'];
/**
 * This is an example of a front controller for a flat file PHP site. Using a
 * Static list provides security against URL injection by default.
 */
switch (@parse_url($_SERVER['REQUEST_URI'])['path']) {
    case '/':
        if (!isset($_SESSION['views'])) {
            $_SESSION['views'] = 0;
            $_SESSION['color'] = $colors[rand(0, 4)];
        }
        printf(
            '<body bgcolor="%s">Views: %s</body>',
            $_SESSION['color'],
            $_SESSION['views']++
        );
        break;
    default:
        http_response_code(404);
        exit('Not Found');
}
# [END getting_started_sessions_front_controller]
# [END getting_started_sessions_all]

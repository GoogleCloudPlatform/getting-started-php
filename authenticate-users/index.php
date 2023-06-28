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

# [START getting_started_auth_all]
require_once __DIR__ . '/vendor/autoload.php';

# [START getting_started_auth_validate_assertion]
/**
 * Checks that the JWT assertion is valid (properly signed, for the
 * correct audience) and if so, returns strings for the requesting user's
 * email and a persistent user ID. If not valid, returns null for each field.
 *
 * @param string $idToken The JWT string to assert.
 * @param string $audience The audience of the JWT.
 *
 * @return string[] array containing [$email, $id]
 * @throws Exception on failed validation
 */
function validate_assertion(string $idToken, string $audience) : array
{
    $auth = new Google\Auth\AccessToken();
    $info = $auth->verify($idToken, [
      'certsLocation' => Google\Auth\AccessToken::IAP_CERT_URL,
      'throwException' => true,
    ]);

    if ($audience != $info['aud'] ?? '') {
        throw new Exception(sprintf(
            'Audience %s did not match expected %s', $info['aud'], $audience
        ));
    }

    return [$info['email'], $info['sub']];
}
# [END getting_started_auth_validate_assertion]

# [START getting_started_auth_front_controller]
/**
 * This is an example of a front controller for a flat file PHP site. Using a
 * static list provides security against URL injection by default.
 */
switch (@parse_url($_SERVER['REQUEST_URI'])['path']) {
    case '/':
        if (!Google\Auth\Credentials\GCECredentials::onGce()) {
            throw new Exception('You must deploy to appengine to run this sample');
        }
        # [START getting_started_auth_audience]
        $metadata = new Google\Cloud\Core\Compute\Metadata();
        $audience = sprintf(
            '/projects/%s/apps/%s',
            $metadata->getNumericProjectId(),
            $metadata->getProjectId()
        );
        # [END getting_started_auth_audience]
        $idToken = getallheaders()['X-Goog-Iap-Jwt-Assertion'] ?? '';
        try {
            list($email, $id) = validate_assertion($idToken, $audience);
            printf("<h1>Hello %s</h1>", $email);
        } catch (Exception $e) {
            printf('Failed to validate assertion: %s', $e->getMessage());
        }
        break;
    case '': break; // Nothing to do, we're running our tests
    default:
        http_response_code(404);
        exit('Not Found');
}
# [END getting_started_auth_front_controller]
# [END getting_started_auth_all]

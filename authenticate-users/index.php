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

/**
 * Checks that the JWT assertion is valid (properly signed, for the
 * correct audience) and if so, returns strings for the requesting user's
 * email and a persistent user ID. If not valid, returns null for each field.
 *
 * @param string $assertion The JWT string to assert.
 * @param string $assertion The audience of the JWT.
 *
 * @return array containing [$email, $id], or [null, null] on failed validation.
 */
function validate_assertion(string $idToken, $audience = null) : array
{
    // Get audience from the metadata server if it isn't passed in
    if ($audience === null) {
        # [START getting_started_auth_audience]
        $metadata = new Google\Cloud\Core\Compute\Metadata();
        $audience = sprintf(
            '/projects/%s/apps/%s',
            $metadata->getNumericProjectId(),
            $metadata->getProjectId()
        );
        # [END getting_started_auth_audience]
    }

    # [START getting_started_auth_validate_assertion]
    $auth = new Google\Auth\AccessToken();
    $info = $auth->verify($idToken, [
      'certsLocation' => Google\Auth\AccessToken::IAP_CERT_URL,
      'throwException' => true,
    ]);

    if ($info['aud'] ?? '' != $audience) {
        throw new Exception('Audience did not match');
    }

    return [$info['email'], $info['sub']];
    # [END getting_started_auth_validate_assertion]
}

# [START getting_started_auth_front_controller]
/**
 * This is an example of a front controller for a flat file PHP site. Using a
 * static list provides security against URL injection by default.
 */
switch (@parse_url($_SERVER['REQUEST_URI'])['path']) {
    case '/':
        $idToken = getallheaders()['X-Goog-Iap-Jwt-Assertion'] ?? '';
        try {
            list($email, $id) = validate_assertion($idToken);
            printf("<h1>Hello %s</h1>", $email);
        } catch (Exception $e) {
            printf('Failed to validate assertion: ', $e->getMessage());
        }
        break;
    case '': break; // Nothing to do, we're running our tests
    default:
        http_response_code(404);
        exit('Not Found');
}
# [END getting_started_auth_front_controller]
# [END getting_started_auth_all]

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

# [START getting_started_auth_certs]
/**
 * Returns a dictionary of current Google public key certificates for
 * validating Google-signed JWTs.
 */
function certs() : string
{
    $client = new GuzzleHttp\Client();
    $response = $client->get(
        'https://www.gstatic.com/iap/verify/public_key-jwk'
    );
    return $response->getBody();
}
# [END getting_started_auth_certs]

# [START getting_started_auth_audience]
/**
 * Returns the audience value (the JWT 'aud' property) for the current
 * running instance. Since this involves a metadata lookup, the result is
 * cached when first requested for faster future responses.
 */
function audience() : string
{
    $metadata = new Google\Cloud\Core\Compute\Metadata();
    $projectNumber = $metadata->getNumericProjectId();
    $projectId = $metadata->getProjectId();
    $audience = sprintf('/projects/%s/apps/%s', $projectNumber, $projectId);
    return $audience;
}
# [END getting_started_auth_audience]

# [START getting_started_auth_validate_assertion]
/**
 * Checks that the JWT assertion is valid (properly signed, for the
 * correct audience) and if so, returns strings for the requesting user's
 * email and a persistent user ID. If not valid, returns null for each field.
 *
 * @param string $assertion The JWT string to assert.
 * @param string $certs The certificates to use for the assertion validation.
 * @param string $audience The required audience of the JWT.
 * @return array containing [$email, $id], or [null, null] on failed validation.
 */
function validate_assertion(string $assertion, string $certs, string $audience) : array
{
    $jwkset = new SimpleJWT\Keys\KeySet();
    $jwkset->load($certs);
    try {
        $info = SimpleJWT\JWT::decode(
            $assertion,
            $jwkset,
            'ES256'
        );
        if ($info->getClaim('aud') != $audience) {
            throw new Exception('Audience did not match');
        }
        return [$info->getClaim('email'), $info->getClaim('sub')];
    } catch (Exception $e) {
        printf('Failed to validate assertion: %s', $e->getMessage());
        return [null, null];
    }
}
# [END getting_started_auth_validate_assertion]

# [START getting_started_auth_front_controller]
/**
 * This is an example of a front controller for a flat file PHP site. Using a
 * static list provides security against URL injection by default.
 */
switch (@parse_url($_SERVER['REQUEST_URI'])['path']) {
    case '/':
        $assertion = getallheaders()['X-Goog-Iap-Jwt-Assertion'] ?? '';
        list($email, $id) = validate_assertion($assertion, certs(), audience());
        if ($email) {
            printf("<h1>Hello %s</h1>", $email);
        }
        break;
    case '': break; // Nothing to do, we're running our tests
    default:
        http_response_code(404);
        exit('Not Found');
}
# [END getting_started_auth_front_controller]
# [END getting_started_auth_all]

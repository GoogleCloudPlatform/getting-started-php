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

namespace Google\Cloud\GettingStarted;

use Google\Auth\CredentialsLoader;
use Google\Auth\OAuth2;
use Google\Cloud\Firestore\FirestoreClient;
use Google\Cloud\PubSub\PubSubClient;
use Google\Cloud\TestUtils\DeploymentTrait;
use Google\Cloud\TestUtils\EventuallyConsistentTestTrait;
use Google\Cloud\TestUtils\FileUtil;
use Google\Cloud\TestUtils\GcloudWrapper\AppEngine;
use Google\Cloud\TestUtils\GcloudWrapper\CloudRun;
use Google\Cloud\TestUtils\TestTrait;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

/**
 * Class DeployTest
 */
class DeployTest extends TestCase
{
    use EventuallyConsistentTestTrait;
    use DeploymentTrait;
    use TestTrait;

    /** @var \Google\Cloud\TestUtils\GcloudWrapper\AppEngine */
    private static $frontend;

    /** @var \Google\Cloud\TestUtils\GcloudWrapper\CloudRun */
    private static $backend;

    /** @var \Google\Cloud\PubSub\Subscription */
    private static $subscription;

    /** @var string */
    private static $image;

    /**
     * Deploy the application.
     *
     * @beforeClass
     */
    public static function setUpDeploymentVars()
    {
        $projectId = self::requireEnv('GOOGLE_PROJECT_ID');
        $versionId = self::requireEnv('GOOGLE_VERSION_ID');
        self::$frontend = new AppEngine($projectId, $versionId . '-frontend');
        self::$backend = new CloudRun($projectId);
        self::$subscription = (new PubSubClient(['projectId' => $projectId]))
            ->topic('translate')
            ->subscription('test-' . $versionId);
        self::$image = sprintf('gcr.io/%s/%s-image', $projectId, $versionId);
    }

    private static function beforeDeploy()
    {
        // Ensure setUpDeploymentVars has been called
        if (is_null(self::$frontend)) {
            self::setUpDeploymentVars();
        }

        $frontendDir = FileUtil::cloneDirectoryIntoTmp(__DIR__ . '/../../app');
        self::$frontend->setDir($frontendDir);

        $backendDir = FileUtil::cloneDirectoryIntoTmp(__DIR__ . '/../../backend');
        self::$backend->setDir($backendDir);
    }

    private static function doDeploy()
    {
        // Deploy both the frontend and backend to App Engine.
        if (false === self::$frontend->deploy()) {
            return false;
        }

        if (false === self::$backend->build(self::$image)) {
            return false;
        }

        if (false === self::$backend->deploy(self::$image)) {
            return false;
        }

        if (self::$subscription->exists()) {
            self::$subscription->delete();
        }

        $serviceAccountJson = json_decode(file_get_contents(
            self::requireEnv('GOOGLE_APPLICATION_CREDENTIALS')
        ), true);

        // Create the pubsub subscription
        self::$subscription->create([
            'pushConfig' => [
                'pushEndpoint' => self::$backend->getBaseUrl(),
                'oidcToken' => [
                    'serviceAccountEmail' => $serviceAccountJson['client_email']
                ]
            ],
        ]);

        return true;
    }

    /**
     * Delete a deployed App Engine app.
     */
    private static function doDelete()
    {
        self::$frontend->delete();
        self::$backend->delete();
        self::$backend->deleteImage(self::$image);
        self::$subscription->delete();
    }

    public function testFrontend()
    {
        $resp = $this->client->get('/');
        $this->assertEquals('200', $resp->getStatusCode());
        $this->assertContains(
            'Translate with Background Processing',
            (string) $resp->getBody()
        );
    }

    public function testBackend()
    {
        // Get the ID Token from the Google OAuth2 endpoint
        $targetAudience = self::$backend->getBaseUrl();
        $jsonKey = CredentialsLoader::fromEnv();
        $auth = new OAuth2([
            'audience' => CredentialsLoader::TOKEN_CREDENTIAL_URI,
            'issuer' => $jsonKey['client_email'],
            'signingAlgorithm' => 'RS256',
            'signingKey' => $jsonKey['private_key'],
            'tokenCredentialUri' => CredentialsLoader::TOKEN_CREDENTIAL_URI,
            'additionalClaims' => ['target_audience' => $targetAudience],
        ]);
        $token = $auth->fetchAuthToken();

        // Send an authenticated HTTP request to the backend
        $timestamp = time();
        $text = 'Test sent directly to the backend ' . $timestamp;
        $backendClient = new Client();
        $resp = $backendClient->post($targetAudience, [
            'json' => [
                'message' => [
                    'data' => base64_encode(json_encode([
                        'language' => 'es',
                        'text' => $text,
                    ])),
                ]
            ],
            'headers' => ['Authorization' => 'Bearer ' . $token['id_token']],
        ]);
        $this->assertEquals('200', $resp->getStatusCode());
        $this->assertContains('Done.', (string) $resp->getBody());

        // Verify the translation exists in Firestore
        $firestore = new FirestoreClient();
        $docRef = $firestore->collection('translations')
            ->document('es:' . base64_encode($text));

        $this->assertTrue($docRef->snapshot()->exists());
        $this->assertEquals($text, $docRef->snapshot()['original']);
        $this->assertEquals('en', $docRef->snapshot()['originalLang']);
        $this->assertEquals('es', $docRef->snapshot()['lang']);
        $this->assertContains((string) $timestamp, $docRef->snapshot()['translated']);

        $docRef->delete();
    }

    /**
     * @depends testFrontend
     * @depends testBackend
     */
    public function testRequestTranslation()
    {
        $timestamp = time();
        $resp = $this->client->post('/request-translation?lang=es', [
            'form_params' => ['v' => 'Living the crazy life ' . $timestamp],
        ]);

        $this->assertEquals('200', $resp->getStatusCode());
        $this->runEventuallyConsistentTest(function () use ($timestamp) {
            $resp = $this->client->get('/');
            $this->assertContains(
                'Viviendo la vida loca ' . $timestamp,
                (string) $resp->getBody()
            );
        });
    }

    public function getBaseUri()
    {
        return self::$frontend->getBaseUrl();
    }
}

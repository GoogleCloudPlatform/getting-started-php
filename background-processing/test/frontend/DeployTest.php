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

use Google\Cloud\Firestore\FirestoreClient;
use Google\Cloud\PubSub\PubSubClient;
use Google\Cloud\TestUtils\DeploymentTrait;
use Google\Cloud\TestUtils\EventuallyConsistentTestTrait;
use Google\Cloud\TestUtils\FileUtil;
use Google\Cloud\TestUtils\GcloudWrapper;
use Google\Cloud\TestUtils\TestTrait;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

/**
 * Class DeployTest
 */
class DeployTest extends TestCase
{
    use TestTrait;
    use EventuallyConsistentTestTrait;
    use DeploymentTrait;

    /** @var \Google\Cloud\TestUtils\GcloudWrapper */
    private static $appGcloudWrapper;

    /** @var \Google\Cloud\TestUtils\GcloudWrapper */
    private static $backendGcloudWrapper;

    /** @var \Google\Cloud\PubSub\Subscription */
    private static $subscription;

    /**
     * Deploy the application.
     *
     * @beforeClass
     */
    public static function setUpGcloudWrappers()
    {
        $projectId = self::requireEnv('GOOGLE_PROJECT_ID');
        $versionId = self::requireEnv('GOOGLE_VERSION_ID');
        self::$appGcloudWrapper = new GcloudWrapper($projectId, $versionId . '-app');
        self::$backendGcloudWrapper = new GcloudWrapper($projectId, $versionId . '-backend');
        self::$subscription = (new PubSubClient(['projectId' => $projectId]))
            ->topic('translate')
            ->subscription($versionId . '-test');
    }

    private static function beforeDeploy()
    {
        $appDir = FileUtil::cloneDirectoryIntoTmp(__DIR__ . '/../../appengine-frontend');
        self::$appGcloudWrapper->setDir($appDir);

        $backendDir = FileUtil::cloneDirectoryIntoTmp(__DIR__ . '/../../cloud-run-backend');
        self::$backendGcloudWrapper->setDir($backendDir);
    }

    private static function doDeploy()
    {
        // Deploy both the app and backend to App Engine.
        if (self::$appGcloudWrapper->deploy() === false) {
            throw new \Exception('Failed to deploy app');
        }

        if (self::$backendGcloudWrapper->deploy() === false) {
            throw new \Exception('Failed to deploy backend');
        }

        if (self::$subscription->exists()) {
            self::$subscription->delete();
        }

        // Create the pubsub subscription
        self::$subscription->create([
            'pushConfig' => [
                'pushEndpoint' => self::$backendGcloudWrapper->getBaseUrl()
            ],
        ]);

        return true;
    }

    /**
     * Delete a deployed App Engine app.
     */
    private static function doDelete()
    {
        self::$appGcloudWrapper->delete();
        self::$backendGcloudWrapper->delete();
        self::$subscription->delete();
    }

    /**
     * Return the URI of the deployed App Engine app.
     */
    private function getBaseUri()
    {
        return self::$appGcloudWrapper->getBaseUrl();
    }

    public function testApp()
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
        $client = new Client([
            'base_uri' => self::$backendGcloudWrapper->getBaseUrl()
        ]);

        $text = 'living the crazy life';
        $resp = $client->post('/', [
            'json' => [
                'message' => [
                    'data' => base64_encode(json_encode([
                        'language' => 'es',
                        'text' => $text,
                    ])),
                ]
            ]
        ]);
        $this->assertEquals('200', $resp->getStatusCode());
        $this->assertContains('Done.', (string) $resp->getBody());

        $firestore = new FirestoreClient();
        $docRef = $firestore->collection('translations')
            ->document('es:' . base64_encode($text));

        $this->assertTrue($docRef->snapshot()->exists());

        $this->assertEquals($text, $docRef->snapshot()['original']);
        $this->assertEquals('en', $docRef->snapshot()['originalLang']);
        $this->assertEquals('es', $docRef->snapshot()['lang']);
        $this->assertContains('la vida loca', $docRef->snapshot()['translated']);

        $docRef->delete();
    }

    /**
     * @depends testApp
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
}

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

use Google\Auth\ApplicationDefaultCredentials;
use Google\Cloud\TestUtils\AppEngineDeploymentTrait;
use Google\Cloud\TestUtils\GcloudWrapper;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use PHPUnit\Framework\TestCase;

/**
 * Class DeployTest
 */
class DeployTest extends TestCase
{
    use AppEngineDeploymentTrait;

    private static $iapProjectId = 'cloud-iap-for-testing';
    private static $iapClientId = '1031437410300-ki5srmdg37qc6cl521dlqcmt4gbjufn5.apps.googleusercontent.com';

    /**
     * Check project env vars needed to deploy the application.
     * Override so GOOGLE_PROJECT_ID is not required.
     */
    private static function checkProjectEnvVars()
    {
    }

    /**
     * Deploy the application.
     * Override to set custom project ID for IAP
     */
    public static function deployApp()
    {
        // This has to go here because the requirements are out of order
        self::requireEnv('GOOGLE_APPLICATION_CREDENTIALS');

        // Deploy using the IAP project ID
        self::$gcloudWrapper = new GcloudWrapper(
            self::$iapProjectId,
            self::requireEnv('GOOGLE_VERSION_ID')
        );
        self::baseDeployApp();
    }

    /**
     * Set up the client.
     * Override to use ID Token auth for IAP
     *
     * @before
     */
    public function setUpClient()
    {
        $stack = HandlerStack::create();
        $stack->push(ApplicationDefaultCredentials::getIdTokenMiddleware(
            self::$iapClientId
        ));

        // create the HTTP client
        $this->client = new Client([
          'handler' => $stack,
          'auth' => 'google_auth',
          'base_uri' => self::getBaseUri(),
        ]);
    }

    public function testIndex()
    {
        $serviceAccountEmail = json_decode(file_get_contents(
            self::requireEnv('GOOGLE_APPLICATION_CREDENTIALS')
        ), true)['client_email'];
        $resp = $this->client->get('/');
        $this->assertEquals('200', $resp->getStatusCode());
        $this->assertContains(
            sprintf('<h1>Hello %s</h1>', $serviceAccountEmail),
            (string) $resp->getBody()
        );
    }
}

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

use Google\Cloud\TestUtils\DeploymentTrait;
use Google\Cloud\TestUtils\EventuallyConsistentTestTrait;
use Google\Cloud\TestUtils\FileUtil;
use Google\Cloud\TestUtils\GcloudWrapper;
use Google\Cloud\TestUtils\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class DeployTest
 */
class DeployTest extends TestCase
{
    use TestTrait;
    use EventuallyConsistentTestTrait;
    use DeploymentTrait;

    private static $appDir;
    private static $workerDir;

    /** @var \Google\Cloud\TestUtils\GcloudWrapper */
    private static $appGcloudWrapper;

    /** @var \Google\Cloud\TestUtils\GcloudWrapper */
    private static $workerGcloudWrapper;

    private static function doDeploy()
    {
        // Deploy both the app and worker to App Engine.
        if (self::$appGcloudWrapper->deploy() === false) {
            throw new \Exception('Failed to deploy app');
        }
        return self::$workerGcloudWrapper->deploy();
    }

    /**
     * Delete a deployed App Engine app.
     */
    private static function doDelete()
    {
        self::$appGcloudWrapper->delete();
        self::$workerGcloudWrapper->delete();
    }

    /**
     * Return the URI of the deployed App Engine app.
     */
    private function getBaseUri()
    {
        return self::$appGcloudWrapper->getBaseUrl();
    }

    private static function beforeDeploy()
    {
        $projectId = self::requireEnv('GOOGLE_PROJECT_ID');
        $versionId = self::requireEnv('GOOGLE_VERSION_ID');
        self::$appGcloudWrapper = new GcloudWrapper($projectId, $versionId . '-app');
        self::$workerGcloudWrapper = new GcloudWrapper($projectId, $versionId . '-worker');

        self::$appDir = FileUtil::cloneDirectoryIntoTmp(__DIR__ . '/../app');
        self::$appGcloudWrapper->setDir(self::$appDir);

        self::$workerDir = FileUtil::cloneDirectoryIntoTmp(__DIR__ . '/../worker');
        self::$workerGcloudWrapper->setDir(self::$workerDir);
    }

    public function testIndex()
    {
        $resp = $this->client->get('/');
        $this->assertEquals('200', $resp->getStatusCode());
        $this->assertContains('<h3>Books</h3>', (string) $resp->getBody());
    }

    public function testRequestTranslation()
    {
        $timestamp = time();
        $resp = $this->client->post('/request-translation', [
            'lang' => 'es',
            'v' => 'Living the crazy life ' . $timestamp,
        ]);
        $this->assertEquals('200', $resp->getStatusCode());
        $this->runEventuallyConsistentTest(function () use ($timestamp, $text) {
            $resp = $this->client->get('/');
            $this->assertContains('la vida loca ' . $timestamp, (string) $resp->getBody());
        });
    }
}

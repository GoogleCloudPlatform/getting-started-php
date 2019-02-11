<?php
/*
 * Copyright 2015 Google Inc. All Rights Reserved.
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

namespace Google\Cloud\Bookshelf;

use Google\Cloud\TestUtils\TestTrait;
use Google\Cloud\TestUtils\DeploymentTrait;
use Google\Cloud\TestUtils\EventuallyConsistentTestTrait;
use Google\Cloud\TestUtils\KubectlWrapper;
use Google\Cloud\TestUtils\FileUtil;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/DeploymentTrait.php';
require_once __DIR__ . '/KubectlWrapper.php';

/**
 * Class DeployGkeTest
 */
class DeployGkeTest extends TestCase
{
    use TestTrait;
    use DeploymentTrait;
    use EventuallyConsistentTestTrait;

    private static $kubectlWrapper;
    private static $serviceYaml;
    private static $uniqueId;

    public static function setUpBeforeClass()
    {
        self::$kubectlWrapper = new KubectlWrapper();
        self::$uniqueId = getenv('GKE_UNIQUE_ID')
            ?: FileUtil::randomName(4);
    }

    public function testIndex()
    {
        // Sometimes the IP is not ready immediately after the deployment
        $this->catchAllExceptions = true;
        $this->runEventuallyConsistentTest(function() {
            $resp = $this->client->get('/books/');
            $this->assertEquals('200', $resp->getStatusCode());
            $this->assertContains('<h3>Books</h3>', (string) $resp->getBody());
        });
    }

    private static function beforeDeploy()
    {
        // Ensure we have the environment variables we need before doing anything
        $dbConn = self::requireEnv('CLOUDSQL_CONNECTION_NAME');
        $dbUser = self::requireEnv('CLOUDSQL_USER');
        $dbPass = self::requireEnv('CLOUDSQL_PASSWORD');

        // Copy the project dir
        $tmpDir = sys_get_temp_dir() . '/test-' . FileUtil::randomName(8);
        mkdir($tmpDir);
        echo "Copying project dir to $tmpDir\n";
        passthru(sprintf('cp -R %s %s', __DIR__ . '/..', $tmpDir));
        copy(self::requireEnv('GOOGLE_APPLICATION_CREDENTIALS'), $tmpDir . '/credentials.json');

        // update "Dockerfile" with project ID and unique names
        $dockerfile = $tmpDir . '/gke_deployment/Dockerfile';
        file_put_contents($dockerfile, str_replace(
            [
                '/path/to/service-account-credentials.json',
                'YOUR_CLOUDSQL_CONNECTION_NAME',
                'YOUR_CLOUDSQL_USER',
                'YOUR_CLOUDSQL_PASSWORD',
            ],
            [
                '${APP_DIR}/credentials.json',
                $dbConn,
                $dbUser,
                $dbPass,
            ],
            file_get_contents($dockerfile)
        ));

        // build and push the dockerfile
        passthru(sprintf('docker build -t gcr.io/%s/bookshelf %s -f %s',
            self::$projectId,
            $tmpDir,
            $dockerfile
        ));
        passthru(sprintf('docker push gcr.io/%s/bookshelf',
            self::$projectId
        ));

        // update "bookshelf.yaml" with project ID and unique names
        self::$serviceYaml = $tmpDir . '/gke_deployment/bookshelf.yaml';
        file_put_contents(self::$serviceYaml, str_replace(
            [
                '$GCLOUD_PROJECT',
                'bookshelf-service',
                'bookshelf-frontend',
            ],
            [
                self::$projectId,
                sprintf('bookshelf-service-%s', self::$uniqueId),
                sprintf('bookshelf-frontend-%s', self::$uniqueId),
            ],
            file_get_contents(self::$serviceYaml)
        ));
    }

    private static function doDeploy()
    {
        self::$kubectlWrapper->deployService(
            sprintf('bookshelf-service-%s', self::$uniqueId),
            self::$serviceYaml
        );
    }

    private static function doDelete()
    {
        self::$kubectlWrapper->delete(
            self::$serviceYaml
        );
    }

    private static function getBaseUri()
    {
        return self::$kubectlWrapper->getBaseUrl();
    }
}

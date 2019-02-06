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

namespace Google\Cloud\GettingStarted;

use Google\Cloud\TestUtils\FileUtil;
use Google\Cloud\TestUtils\AppEngineDeploymentTrait;;
use Google\Cloud\TestUtils\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class DeployGaeTest
 */
class DeployGaeTest extends TestCase
{
    use TestTrait;
    use AppEngineDeploymentTrait;

    private static function beforeDeploy()
    {
        // ensure we have the environment variables we need before doing anything
        $dbConn = self::requireEnv('CLOUDSQL_CONNECTION_NAME');
        $dbData = self::requireEnv('CLOUDSQL_DATABASE_NAME');
        $dbUser = self::requireEnv('CLOUDSQL_USER');
        $dbPass = self::requireEnv('CLOUDSQL_PASSWORD');

        $tmpDir = FileUtil::cloneDirectoryIntoTmp(__DIR__ . '/..');

        // update "app.yaml" for app engine config
        $appYamlPath = __DIR__ . '/../' . 'gae_deployment/app.yaml';
        $appYaml = file_get_contents($appYamlPath);
        file_put_contents($tmpDir . '/app.yaml', str_replace(
            [
                'CLOUDSQL_CONNECTION_NAME:',
                'CLOUDSQL_DATABASE_NAME:',
                'CLOUDSQL_USER:',
                'CLOUDSQL_PASSWORD:',
            ],
            [
                'CLOUDSQL_CONNECTION_NAME: ' . $dbConn,
                'CLOUDSQL_DATABASE_NAME: ' . $dbData,
                'CLOUDSQL_USER: ' . $dbUser,
                'CLOUDSQL_PASSWORD: ' . $dbPass,
            ],
            $appYaml
        ));

        // set the directory in gcloud
        self::$gcloudWrapper->setDir($tmpDir);
    }

    public function testIndex()
    {
        $resp = $this->client->get('/books/');
        $this->assertEquals('200', $resp->getStatusCode());
        $this->assertContains('<h3>Books</h3>', (string) $resp->getBody());
    }
}

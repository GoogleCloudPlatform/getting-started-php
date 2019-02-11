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
use Google\Cloud\TestUtils\AppEngineDeploymentTrait;
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
        $dbUser = self::requireEnv('CLOUDSQL_USER');
        $dbPass = self::requireEnv('CLOUDSQL_PASSWORD');

        $tmpDir = sys_get_temp_dir() . '/test-' . FileUtil::randomName(8);
        mkdir($tmpDir);
        echo "Copying project dir to $tmpDir\n";
        passthru(sprintf('cp -R %s %s', __DIR__ . '/..', $tmpDir));

        // update "app.yaml" for app engine config
        $appYamlPath = __DIR__ . '/../gae_deployment/app.yaml';
        $appYaml = file_get_contents($appYamlPath);
        file_put_contents($tmpDir . '/app.yaml', str_replace(
            [
                'YOUR_CLOUDSQL_CONNECTION_NAME',
                'YOUR_CLOUDSQL_USER',
                'YOUR_CLOUDSQL_PASSWORD',
            ],
            [
                $dbConn,
                $dbUser,
                $dbPass,
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

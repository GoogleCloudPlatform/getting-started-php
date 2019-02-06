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
use Google\Cloud\TestUtils\AppEngineDeploymentTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class DeployTest
 */
class DeployGceTest extends TestCase
{
    use TestTrait;
    use AppEngineDeploymentTrait;

    private static function beforeDeploy()
    {
        // set "app-e2e.yaml" for app engine config
        // set cloudsql connection name
        $appYamlPath = __DIR__ . '/../app-e2e.yaml';
        $appYaml = file_get_contents(__DIR__ . '/../app.yaml');
        file_put_contents($appYamlPath, str_replace(
            [
                'CLOUDSQL_CONNECTION_NAME:',
                'CLOUDSQL_DATABASE_NAME:',
                'CLOUDSQL_USER:',
                'CLOUDSQL_PASSWORD:',
            ],
            [
                'CLOUDSQL_CONNECTION_NAME: ' . self::requireEnv('CLOUDSQL_CONNECTION_NAME'),
                'CLOUDSQL_DATABASE_NAME: ' . self::requireEnv('CLOUDSQL_DATABASE_NAME'),
                'CLOUDSQL_USER: ' . self::requireEnv('CLOUDSQL_USER'),
                'CLOUDSQL_PASSWORD: ' . self::requireEnv('CLOUDSQL_PASSWORD'),
            ],
            $appYaml
        ));
    }

    private static function doDeploy()
    {
        // deploy using "app-e2e.yaml"
        return self::$gcloudWrapper->deploy('app-e2e.yaml');
    }

    public function testIndex()
    {
        $resp = $this->client->get('/books/');
        $this->assertEquals('200', $resp->getStatusCode());
        $this->assertContains('<h3>Books</h3>', (string) $resp->getBody());
    }
}

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

namespace Google\Cloud\Samples\Bookshelf;

use Google\Cloud\TestUtils\AppEngineDeploymentTrait;
use Symfony\Component\Yaml\Dumper;

/**
 * Class E2eTest
 */
abstract class E2eTest extends \PHPUnit_Framework_TestCase
{
    use SkipTestsIfMissingCredentialsTrait,
        AppEngineDeploymentTrait,
        GetConfigTrait;

    private static function beforeDeploy()
    {
        static::copySettingsYaml();
        static::copyAppYaml();
    }

    private static function doDeploy()
    {
        // deploy using "app-e2e.yaml"
        return self::$gcloudWrapper->deploy('app-e2e.yaml');
    }

    protected static function copySettingsYaml()
    {
        // set "settings-e2e.yml" for application config
        $config = static::getCustomConfig();
        $dumper = new Dumper();
        $yaml = $dumper->dump($config + self::getConfig());
        file_put_contents(__DIR__ . '/../../config/settings-e2e.yml', $yaml);
    }

    protected static function copyAppYaml()
    {
        // set "app-e2e.yaml" for app engine config
        $appYamlPath = __DIR__ . '/../../app-e2e.yaml';
        copy(__DIR__ . '/../app-e2e.yaml', $appYamlPath);
    }

    public function testIndex()
    {
        $resp = $this->client->get('/');
        $this->assertEquals('200', $resp->getStatusCode(),
            'index status code');
        $this->assertContains('Book', (string) $resp->getBody(),
            'index content');
    }
}

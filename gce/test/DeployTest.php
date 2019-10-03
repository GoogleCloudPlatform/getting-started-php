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

use Google\Cloud\TestUtils\FileUtil;
use Google\Cloud\TestUtils\DeploymentTrait;
use Google\Cloud\TestUtils\EventuallyConsistentTestTrait;
use Google\Cloud\TestUtils\TestTrait;
use Google\Cloud\Utils\ExponentialBackoff;
use Google\Cloud\Logging\LoggingClient;
use Google\Cloud\ErrorReporting\V1beta1\ErrorStatsServiceClient;
use Google\Cloud\ErrorReporting\V1beta1\QueryTimeRange;
use Google\Cloud\ErrorReporting\V1beta1\QueryTimeRange\Period;
use PHPUnit\Framework\TestCase;

/**
 * Class DeployTest
 */
class DeployTest extends TestCase
{
    use TestTrait;
    use DeploymentTrait;
    use EventuallyConsistentTestTrait;

    private static $projectDir;
    private static $instanceName;

    private static function beforeDeploy()
    {
        self::$instanceName = 'test-instance-' . FileUtil::randomName(4);
        self::$projectDir = FileUtil::cloneDirectoryIntoTmp(__DIR__ . '/..');
        chdir(self::$projectDir);
        file_put_contents('scripts/deploy.sh', str_replace(
            'my-app-instance',
            self::$instanceName,
            file_get_contents('scripts/deploy.sh')
        ));
        file_put_contents('scripts/teardown.sh', str_replace(
            'my-app-instance',
            self::$instanceName,
            file_get_contents('scripts/teardown.sh')
        ));
    }

    private static function doDeploy()
    {
        passthru('bash scripts/deploy.sh');
        $backoff = new ExponentialBackoff(10);
        $backoff->execute(function () {
            $cmd = sprintf(
                'gcloud compute instances get-serial-port-output %s 2>&1',
                self::$instanceName
            );
            exec($cmd, $output);
            $output = implode("\n", $output);
            if (false === strpos($output, 'Finished running startup scripts')) {
                echo "Waiting for startup script to complete...\n";
                throw new \Exception('Startup script has not completed');
            }
        });
    }

    private static function doDelete()
    {
        passthru('bash scripts/teardown.sh');
    }

    public function testIndex()
    {
        $resp = $this->client->get('/');
        $this->assertEquals('200', $resp->getStatusCode());
        $this->assertContains('Hello, World!', (string) $resp->getBody());
    }

    /**
     * Return the URI of the deployed App Engine app.
     */
    private function getBaseUri()
    {
        $cmd = sprintf(
            'gcloud compute instances describe %s | grep natIP | awk \'{print $2}\'',
            self::$instanceName
        );
        exec($cmd, $output);
        if (empty($output[0])) {
            throw new \Exception('Instance IP not found');
        }
        return 'http://' . $output[0];
    }
}

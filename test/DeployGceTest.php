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

use Google\Cloud\TestUtils\DeploymentTrait;
use Google\Cloud\TestUtils\ExecuteCommandTrait;
use Google\Cloud\TestUtils\FileUtil;
use Google\Cloud\TestUtils\TestTrait;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/DeploymentTrait.php';

/**
 * Class DeployTest
 */
class DeployGceTest extends TestCase
{
    use DeploymentTrait;
    use ExecuteCommandTrait;
    use TestTrait;

    private static $instanceName;

    public function testIndex()
    {
        $resp = $this->client->get('/books/');
        $this->assertEquals('200', $resp->getStatusCode());
        $this->assertContains('<h3>Books</h3>', (string) $resp->getBody());
    }

    public static function setUpBeforeClass()
    {
        // Allow setting of the instance name for testing
        self::$instanceName = getenv('GOOGLE_INSTANCE_NAME')
            ?: 'getting-started-' . FileUtil::randomName(4);
        self::$logger = new \Monolog\Logger('test');
    }

    private static function beforeDeploy()
    {
        // ensure we have the environment variables we need before doing anything
        $dbConn = self::requireEnv('CLOUDSQL_CONNECTION_NAME');
        $dbUser = self::requireEnv('CLOUDSQL_USER');
        $dbPass = self::requireEnv('CLOUDSQL_PASSWORD');

        $tmpDir = sys_get_temp_dir() . '/test-' . FileUtil::randomName(8);
        mkdir($tmpDir);
        $startupScriptTmp = $tmpDir . '/startup-script.sh';
        copy(__DIR__ . '/../gce_deployment/startup-script.sh', $startupScriptTmp);
        echo "Copied startup-script.sh to $tmpDir\n";

        // update "startup-script.sh" with the CloudSQL configuration
        file_put_contents($startupScriptTmp, str_replace(
            [
                '"YOUR_CLOUDSQL_CONNECTION_NAME"',
                '"YOUR_CLOUDSQL_USER"',
                '"YOUR_CLOUDSQL_PASSWORD"',
            ],
            [
                sprintf('"%s"', $dbConn),
                sprintf('"%s"', $dbUser),
                sprintf('"%s"', $dbPass),
            ],
            file_get_contents($startupScriptTmp)
        ));

        // set to current directory
        chdir($tmpDir);
    }

    private static function doDeploy()
    {
        // Create port 80 firewall rule if it doesn't exist
        $output = self::execute('gcloud compute firewall-rules list --filter "name~\'default-allow-http-80\'"');
        if (!trim($output)) {
            self::execute('gcloud compute firewall-rules create default-allow-http-80'
                . ' --allow tcp:80'
                . ' --source-ranges 0.0.0.0/0'
                . ' --target-tags http-server'
                . ' --description "Allow port 80 access to http-server"'
            );
        }

        // Create the instance
        self::execute(sprintf('gcloud compute instances create %s', self::$instanceName)
            . ' --image-family debian-9'
            . ' --image-project debian-cloud'
            . ' --machine-type g1-small'
            . ' --scopes userinfo-email,cloud-platform'
            . ' --metadata-from-file startup-script=startup-script.sh'
            . ' --zone us-central1-f'
            . ' --tags http-server'
        );

        // Ensure the instance is deployed and the startup script completed before continuing
        $startTime = time();
        $timeoutSeconds = 600;
        $status = self::createProcess(sprintf(
            'gcloud compute instances get-serial-port-output %s --zone us-central1-f',
            self::$instanceName
        ));
        do {
            $output = trim($status->run());
            if (time() - $startTime > $timeoutSeconds) {
                echo $output;
                throw new \Exception("Startup script exceeded timeout of $timeoutSeconds seconds");
            }
            echo substr($output, strrpos($output, "\n", -2));
            sleep(5);
        } while (false === strpos($output, 'Finished running startup-script.sh'));
    }

    private static function doDelete()
    {
        // Delete the instance
        $timeout = 300;
        self::execute(sprintf('gcloud compute instances delete -q %s', self::$instanceName), $timeout);
    }

    private static function getBaseUri()
    {
        $output = self::execute(sprintf(
            "gcloud compute instances list --filter=\"name~'%s'\" | awk 'NR>1 {print $5}'",
            self::$instanceName
        ));
        return 'http://' . explode("\n", $output)[0];
    }
}

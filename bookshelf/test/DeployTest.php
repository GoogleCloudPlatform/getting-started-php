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
use Google\Cloud\TestUtils\AppEngineDeploymentTrait;
use Google\Cloud\TestUtils\EventuallyConsistentTestTrait;
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
    use AppEngineDeploymentTrait;
    use EventuallyConsistentTestTrait;

    private static function beforeDeploy()
    {
        $tmpDir = FileUtil::cloneDirectoryIntoTmp(__DIR__ . '/..');
        self::$gcloudWrapper->setDir($tmpDir);
        chdir($tmpDir);
    }

    public function testIndex()
    {
        $resp = $this->client->get('/');
        $this->assertEquals('200', $resp->getStatusCode());
        $this->assertContains('<h3>Books</h3>', (string) $resp->getBody());
    }

    public function testLogging()
    {
        $this->eventuallyConsistentRetryCount = 5;

        $logTimestamp = sprintf('%s-%s', time(), rand());
        $message = 'test-logging-' . $logTimestamp;
        $resp = $this->client->get('/logs?message=' . $message);
        $this->assertEquals('200', $resp->getStatusCode());

        $logging = new LoggingClient([
            'projectId' => self::$projectId,
        ]);

        $filter = sprintf(
            'logName = "%s" AND timestamp >= "%s"',
            sprintf('projects/%s/logs/stderr', self::$projectId),
            date(\DateTime::RFC3339, strtotime('-1 minute'))
        );

        $this->runEventuallyConsistentTest(function () use ($logging, $message, $filter) {
            $entries = $logging->entries(['filter' => $filter]);

            // Create a string from all the entry logs
            $logString = '';
            foreach ($entries as $entry) {
                $info = $entry->info();
                $logString .= $info['textPayload'] ?? implode(' ', $info['jsonPayload']);
            }

            $this->assertContains($message, $logString);
        });
    }

    public function testErrorHandling()
    {
        $this->eventuallyConsistentRetryCount = 5;

        $logTimestamp = sprintf('%s-%s', time(), rand());
        $message = 'test-error-handling-' . $logTimestamp;
        $resp = $this->client->get('/errors?message=' . $message, ['http_errors' => false]);
        $this->assertEquals('500', $resp->getStatusCode());

        $errorStats = new ErrorStatsServiceClient();
        $projectName = $errorStats->projectName(self::$projectId);
        $timeRange = (new QueryTimeRange())
            ->setPeriod(Period::PERIOD_1_HOUR);

        // Iterate through all elements
        $this->runEventuallyConsistentTest(function () use ($errorStats, $projectName, $timeRange, $message) {
            $messages = [];
            $response = $errorStats->listGroupStats($projectName, $timeRange, ['pageSize' => 100]);
            foreach ($response->iterateAllElements() as $groupStat) {
                $response = $errorStats->listEvents($projectName, $groupStat->getGroup()->getGroupId(), [
                    'timeRange' => $timeRange,
                    'pageSize' => 100,
                ]);
                foreach ($response->iterateAllElements() as $event) {
                    $messages[] = $event->getMessage();
                }
            }

            $this->assertContains($message, implode("\n", $messages));
        });
    }
}

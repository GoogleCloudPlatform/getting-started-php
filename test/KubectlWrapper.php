<?php
/**
 * Copyright 2016 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Google\Cloud\TestUtils;

use Google\Cloud\Core\ExponentialBackoff;
use Symfony\Component\Process\Process;

/**
 * Class KubectlWrapper
 * @package Google\Cloud\TestUtils
 *
 * A class representing App Engine application.
 */
class KubectlWrapper
{
    /** @var string */
    private $version;

    /** @var bool */
    private $deployed;

    /** @var string */
    private $dir;

    /** @var string */
    private $ip;

    /**
     * Constructor of KubectlWrapper.
     *
     * @param string|null $dir optional
     */
    public function __construct($dir = null) {
        if ($dir === null) {
            $dir = getcwd();
        }
        $this->deployed = false;
        $this->dir = $dir;
    }

    /**
     * A setter for $dir, it's handy for using different directory for the
     * test.
     *
     * @param string $dir
     */
    public function setDir($dir)
    {
        $this->dir = $dir;
    }

    /**
     * Return the base URL of the deployed app.
     *
     * @param string $service optional
     * @return mixed returns the base URL of the deployed app, or false when
     *     the app is not deployed.
     */
    public function getBaseUrl()
    {
        if (!$this->deployed) {
            $this->errorLog('The app has not been deployed.');
            return false;
        }
        // For container engine deployment.
        if (!$this->ip) {
            $this->errorLog('The app failed to deploy.');
            return false;
        }
        return 'http://' . $this->ip;
    }

    public function delete($targets) {
        // remove the service if it exists
        $deleteCmd = 'kubectl delete -f ' . $targets;
        $this->execute($deleteCmd);
    }

    public function deployService($serviceName, $targets) {
        if ($this->deployed) {
            $this->errorLog('The app has been already deployed.');
            return false;
        }

        if (chdir($this->dir) === false) {
            $this->errorLog('Can not chdir to ' . $this->dir);
            return false;
        }

        // Create the resource
        $createCmd = sprintf('kubectl create -f %s', $targets);
        $ret = $this->execute($createCmd);
        // Wait until the service is deployed
        $getCmd = 'kubectl get service ' . $serviceName;
        $backoff = new ExponentialBackoff(10);
        $backoff->execute(function() use ($getCmd) {
            $process = new Process($getCmd);
            $process->run();
            $line = explode("\n", $process->getOutput())[1];
            $status = preg_split('/\s+/', $line)[3];
            if ($status === '<pending>') {
                $this->errorLog("Waiting for resources to deploy...");
                throw new \Exception("Waiting for resources to deploy...");
            }
            $this->ip = $status;
        });

        return $this->deployed = (bool) $this->ip;
    }

    private function execute($cmd, $retries = 0)
    {
        for ($i = 0; $i <= $retries; $i++) {
            exec($cmd, $output, $ret);
            if ($ret === 0) {
                return true;
            } elseif ($i <= $retries) {
                $this->errorLog('Retrying the command: ' . $cmd);
            }
        }
        return false;
    }

    private function errorLog($message)
    {
        fwrite(STDERR, $message . "\n");
    }
}

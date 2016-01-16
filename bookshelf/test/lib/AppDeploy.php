<?php

/**
 * Copyright 2015, Google, Inc.
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Google\Cloud\Samples\Bookshelf;

class AppDeploy
{
    private static $instance;
    protected static $attempted;
    public static $url;

    public static function check()
    {
        if (!self::$instance) {
            self::$instance = new AppDeploy();
        }

        return self::$instance->doCheck();
    }

    public function doCheck()
    {
        if (empty(self::$url)) {
            // $stepName = getenv('STEP_NAME');
            $stepName = 'bookshelf';

            if (empty($stepName)) {
                // we are missing arguments to deploy to e2e
                throw new \Exception('cannot run e2e tests - missing required STEP_NAME');
            }

            if (self::$attempted) {
                // we've tried to run the tests and failed
                throw new \Exception('cannot run e2e tests - deployment failed');
            }
        }

        self::$attempted = true;

        $buildId = getenv('TRAVIS_BUILD_ID');

        return $this->deploy($stepName, $buildId);
    }

    public function deploy($stepName, $buildId = null, $useComputeEngine = false)
    {
        $buildId = $buildId ?: rand(1000, 9999);

        $version = "{$stepName}-{$buildId}";

        // read in our credentials file
        $keyPath = getenv('GOOGLE_APPLICATION_CREDENTIALS');
        $keyFile = file_get_contents($keyPath);
        $keyJson = json_decode($keyFile, true);

        $accountName = $keyJson['client_email'];
        $projectId = $keyJson['project_id'];

        // authenticate with gcloud using our credentials file
        $this->exec("gcloud config set project {$projectId}");
        $this->exec("gcloud config set account {$accountName}");

        // deploy this $stepName to gcloud
        // try 3 times in case of intermittent deploy error
        $appYamlPath = sprintf('%s/../../../%s/app.yaml', __DIR__, $stepName);
        for ($i = 0; $i < 3; $i++) {
            $result = $this->exec("gcloud preview app deploy {$appYamlPath} --version={$version} -q --no-promote");
            if ($result == 0) {
                break;
            }
        }

        // if status is not 0, we tried 3 times and failed
        if ($result != 0) {
            $this->output("Failed to deploy to gcloud");

            return false;
        }

        // sleeping 1 to ensure URL is callable
        sleep(1);

        // run the specs for the step, but use the remote URL
        self::$url = "https://{$version}-dot-{$projectId}.appspot.com";

        // return 0, no errors
        return true;
    }

    public function cleanup($stepName, $buildId = nil)
    {
        // determine build number
        $buildId = $buildId ?: getenv('TRAVIS_BUILD_ID');
        if (empty($buildId)) {
            $this->output("you must pass a build ID or define ENV[\"TRAVIS_BUILD_ID\"]");

            return 1;
        }

        // run gcloud command
        $result = $this->exec("gcloud preview app modules delete default --version={$stepName}-{$buildId} -q");

        // return the result of the gcloud delete command
        if ($result != 0){
            $this->output("Failed to delete e2e version");

            return false;
        }

        // return 0, no errors
        return true;
    }


    private function exec($cmd)
    {
        $this->output("> {$cmd}");
        exec($cmd, $output, $return_var);
        foreach ($output as $line) {
            $this->output($line);
        }

        return $return_var;
    }

    private function output($line)
    {
        fwrite(STDOUT, $line . "\n");
    }
}

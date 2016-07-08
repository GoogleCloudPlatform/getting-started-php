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

class Gcloud
{
    /**
     * Deploy the app and return the version id or false if failed.
     *
     * @return mixed
     */
    public function deployApp($stepName, $deploySingleInstance = true,
                              $useComputeEngine = false)
    {
        $buildId = getenv('TRAVIS_BUILD_ID');
        if ($buildId === false) {
            $buildId = rand(1000, 9999);
        }

        $version = "{$stepName}-{$buildId}";

        // deploy this $stepName to gcloud
        // try 3 times in case of intermittent deploy error
        $appYamlPath = sprintf('%s/../../app-e2e.yaml', __DIR__);
        copy(sprintf('%s/../app-e2e.yaml', __DIR__), $appYamlPath);
        for ($i = 0; $i < 3; $i++) {
            $result = $this->exec("gcloud app deploy {$appYamlPath} --version={$version} -q --no-promote");
            if ($result == 0) {
                break;
            }
        }
        unlink($appYamlPath);

        // if status is not 0, we tried 3 times and failed
        if ($result != 0) {
            $this->output("Failed to deploy to gcloud");

            return false;
        }

        // sleeping 1 to ensure URL is callable
        sleep(1);

        return $version;
    }

    public function deleteApp($stepName, $version)
    {
        // run gcloud command
        $result = $this->exec("gcloud app versions delete --service default {$version} -q");

        // return the result of the gcloud delete command
        if ($result != 0) {
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

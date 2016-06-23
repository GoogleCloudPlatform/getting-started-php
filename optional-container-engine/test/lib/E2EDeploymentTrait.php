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

use Symfony\Component\Yaml\Dumper;

/**
 * Class E2EDeploymentTrait
 * @package Google\Cloud\Samples\Bookshelf
 *
 * Use this trait to deploy the project to GCP for an end-to-end test.
 */
trait E2EDeploymentTrait
{
    use GetConfigTrait;
    use SkipTestsIfMissingCredentialsTrait;

    /** @staticvar array $versions */
    public static $versions = array();

    /** @staticvar Gcloud $gcloud */
    public static $gcloud;

    private static function dumpConfig($config = [])
    {
        $config = $config + self::getConfig();
        $dumper = new Dumper();
        $yaml = $dumper->dump($config);
        // TODO: Use different filename
        file_put_contents(__DIR__ . '/../../config/settings-e2e.yml', $yaml);
    }

    /**
     * Tries to deploy the app for a given step and store the url for later use.
     *
     * @return bool
     */
    protected static function deployApp($step, $config = [])
    {
        // TODO: allow changing the data backend.
        if (!self::hasCredentials()) {
            // Just return here for avoiding errors.
            return false;
        }
        self::dumpConfig($config);
        $version = self::$gcloud->deployApp($step);
        if ($version === false) {
            return false;
        }
        self::$versions[$step] = $version;
        return true;
    }

    /**
     * Tries to delete the app for the given step.
     *
     * @return bool
     */
    protected static function deleteApp($step)
    {
        if (!array_key_exists($step, self::$versions)) {
            return false;
        }
        return self::$gcloud->deleteApp($step, self::$versions[$step]);
    }

    /**
     * Returns the top URL for the given step.
     *
     * @return mixed
     */
    protected static function getUrl($step)
    {
        $projectId = getenv('GOOGLE_PROJECT_ID');
        if (empty($projectId)) {
            return false;
        }
        if (!array_key_exists($step, self::$versions)) {
            return false;
        }
        return sprintf(
            'https://%s-dot-%s.appspot.com',
            self::$versions[$step],
            $projectId
        );
    }
}
E2EDeploymentTrait::$gcloud = new Gcloud();

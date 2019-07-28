<?php
/*
 * Copyright 2016 Google Inc. All Rights Reserved.
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

namespace Google\Cloud\TestUtils;

use GuzzleHttp\Client;

/**
 * Trait ComputeEngineDeploymentTrait
 * @package Google\Cloud\TestUtils
 *
 * Use this trait to deploy the project to GCP for an end-to-end test.
 */
trait DeploymentTrait
{
    /** @var \GuzzleHttp\Client */
    private $client;

    /**
     * Called before deploying the app. The concrete test class can override
     * this.
     */
    private static function beforeDeploy()
    {
    }

    private static function doDeploy()
    {
        throw new \Exception('This method must be implemented your test');
    }

    /**
     * Called after deploying the app. The concrete test class can override
     * this.
     */
    private static function afterDeploy()
    {
    }

    /**
     * Deploy the application.
     *
     * @beforeClass
     */
    public static function deployApp()
    {
        if (getenv('RUN_DEPLOYMENT_TESTS') !== 'true') {
            self::markTestSkipped(
                'To run this test, set RUN_DEPLOYMENT_TESTS env to true.'
            );
        }
        if (getenv('GOOGLE_SKIP_DEPLOYMENT') !== 'true') {
            static::beforeDeploy();
            if (static::doDeploy() === false) {
                self::fail('Deployment failed.');
            }
            if ((int) $delay = getenv('GOOGLE_DEPLOYMENT_DELAY')) {
                sleep($delay);
            }
            static::afterDeploy();
        }
    }

    /**
     * Delete the application.
     *
     * @afterClass
     */
    public static function deleteApp()
    {
        if (
            getenv('GOOGLE_KEEP_DEPLOYMENT') !== 'true'
            && getenv('GOOGLE_SKIP_DEPLOYMENT') !== 'true'
        ) {
            self::doDelete();
        }
    }

    private static function doDelete()
    {
        throw new \Exception('This method must be implemented in your test');
    }

    /**
     * Set up the client.
     *
     * @before
     */
    public function setUpClient()
    {
        $this->client = new Client(['base_uri' => self::getBaseUri()]);
    }

    private static function getBaseUri()
    {
        throw new \Exception('This method must be implemented in your test');
    }
}

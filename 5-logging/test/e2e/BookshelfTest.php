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

use Behat\Mink\Driver\GoutteDriver;
use Behat\Mink\Session;

/**
 * Class BookshelfTest
 */
class BookshelfTest extends \PHPUnit_Framework_TestCase
{
    protected static $step;
    use E2EDeploymentTrait;

    public static function setUpBeforeClass()
    {
        if (self::$step = getenv('STEP_NAME')) {
            self::deployApp(self::$step);
        }
    }

    public static function tearDownAfterClass()
    {
        if (self::$step) {
            self::deleteApp(self::$step);
        }
    }

    public function setUp()
    {
        if (!self::$step) {
            $this->markTestSkipped('must set STEP_NAME for e2e testing');
        }
        $this->url = self::getUrl(self::$step);
        $driver = new GoutteDriver();
        $this->session = new Session($driver);
    }

    public function testIndex()
    {
        $this->assertNotNull(self::$versions[self::$step]);
        $this->session->visit($this->url . '/');
        $this->assertEquals('200', $this->session->getStatusCode(),
                            'Book index status code');
        // TODO: content check
        $page = $this->session->getPage();
    }
}

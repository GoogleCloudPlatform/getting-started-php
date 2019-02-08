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

use Google\Cloud\TestUtils\TestTrait;
use Google\Cloud\TestUtils\DeploymentTrait;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/DeploymentTrait.php';

/**
 * Class DeployGkeTest
 */
class DeployGkeTest extends TestCase
{
    use TestTrait;
    use DeploymentTrait;

    public function testIndex()
    {
        self::markTestSkipped('Not implemented yet');

        $resp = $this->client->get('/books/');
        $this->assertEquals('200', $resp->getStatusCode());
        $this->assertContains('<h3>Books</h3>', (string) $resp->getBody());
    }

    private static function doDeploy()
    {
    }

    private static function doDelete()
    {
    }

    private static function getBaseUri()
    {
    }
}

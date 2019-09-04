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

use PHPUnit\Framework\TestCase;

/**
 * Test for application controllers
 */
class ControllersTest extends TestCase
{
    private static $collection;

    public static function setUpBeforeClass() : void
    {
        $_SERVER['REQUEST_URI'] = '/';
        ob_start();
        // Use output buffers to prevent print statements from showing up in test output.
        require_once __DIR__ . '/../index.php';
        ob_clean();
    }

    public function testInvalidJwt()
    {
        validate_assertion('fake_jwt', 'fake_expected_audience');
        $this->expectOutputRegex('/Failed to validate assertion: Cannot decode compact serialisation/');
    }

    public function testCerts()
    {
        $certs = certs();
        $this->assertTrue(false !== $json = json_decode($certs, true));
        $this->assertArrayHasKey('keys', $json);
    }
}

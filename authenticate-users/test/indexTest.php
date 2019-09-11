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
class indexTest extends TestCase
{
    /**
     * Test private/public keys generated from http://jwt.io
     *
     * Private Key:
     *  -----BEGIN PUBLIC KEY-----
     *  MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAEEVs/o5+uQbTjL3chynL4wXgUg2R9
     *  q9UU8I5mEovUf86QZ7kOBIjJwqnzD1omageEHWwHdBO6B+dFabmdT9POxg==
     *  -----END PUBLIC KEY-----
     *
     * Public Key:
     *  -----BEGIN PRIVATE KEY-----
     *  MIGHAgEAMBMGByqGSM49AgEGCCqGSM49AwEHBG0wawIBAQQgevZzL1gdAFr88hb2
     *  OF/2NxApJCzGCEDdfSp6VQO30hyhRANCAAQRWz+jn65BtOMvdyHKcvjBeBSDZH2r
     *  1RTwjmYSi9R/zpBnuQ4EiMnCqfMPWiZqB4QdbAd0E7oH50VpuZ1P087G
     *  -----END PRIVATE KEY-----
     */
    private static $testCert = [
        'kty' => 'EC',
        'crv' => 'P-256',
        'x' => 'EVs_o5-uQbTjL3chynL4wXgUg2R9q9UU8I5mEovUf84',
        'y' => 'kGe5DgSIycKp8w9aJmoHhB1sB3QTugfnRWm5nU_TzsY',
        'kid' => '19J8y7Z',
    ];

    public static function setUpBeforeClass() : void
    {
        require_once __DIR__ . '/../index.php';
    }

    public function testInvalidJwt()
    {
        validate_assertion('fake_jwt', '{"keys":[]}', '');
        $this->expectOutputRegex('/Failed to validate assertion: Cannot decode compact serialisation/');
    }

    public function testInvalidAudience()
    {
        $testAssertion = 'eyJhbGciOiJFUzI1NiIsInR5cCI6IkpXVCIsImF1ZCI6ImZvbyJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiYWRtaW4iOnRydWUsImlhdCI6MTUxNjIzOTAyMiwiYXVkIjoiZm9vIiwiZW1haWwiOiJmb29AZ29vZ2xlLmNvbSJ9.rKr6N3u3inkeTIlVaJ24iIb_8C-x-WKcDw65cwaoxb27ZclFSFQktFCGLW1ochruuL0OD8-GViv1vOSyKpXb_g';
        $testAudience = 'invalidaudience';

        list($email, $id) = validate_assertion(
            $testAssertion,
            json_encode(['keys' => [self::$testCert]]),
            $testAudience
        );

        $this->expectOutputRegex('/Failed to validate assertion: Audience did not match/');
    }

    public function testValidAssertion()
    {
        $testAssertion = 'eyJhbGciOiJFUzI1NiIsInR5cCI6IkpXVCIsImF1ZCI6ImZvbyJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiYWRtaW4iOnRydWUsImlhdCI6MTUxNjIzOTAyMiwiYXVkIjoiZm9vIiwiZW1haWwiOiJmb29AZ29vZ2xlLmNvbSJ9.rKr6N3u3inkeTIlVaJ24iIb_8C-x-WKcDw65cwaoxb27ZclFSFQktFCGLW1ochruuL0OD8-GViv1vOSyKpXb_g';
        $testAudience = 'foo';

        list($email, $id) = validate_assertion(
            $testAssertion,
            json_encode(['keys' => [self::$testCert]]),
            $testAudience
        );

        $this->assertEquals('foo@google.com', $email);
        $this->assertEquals('1234567890', $id);
        $this->expectOutputRegex('//');
    }

    public function testCerts()
    {
        $certs = certs();
        $this->assertTrue(false !== $json = json_decode($certs, true));
        $this->assertArrayHasKey('keys', $json);
    }
}

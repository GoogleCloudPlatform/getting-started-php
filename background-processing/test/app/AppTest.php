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

use Google\Cloud\TestUtils\TestTrait;
use Laravel\Lumen\Testing\TestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Test for application controllers
 */
class AppTest extends TestCase
{
    use TestTrait;

    /**
     * Creates the application.
     *
     * @return \Laravel\Lumen\Application
     */
    public function createApplication()
    {
        $app = require __DIR__ . '/../../app/index.php';
        $app['debug'] = true;
        return $app;
    }

    public function testIndex()
    {
        $response = $this->call('GET', '/');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains(
            'Translate with Background Processing',
            $response->getContent()
        );
        $crawler = new Crawler($response->getContent());
        $this->assertEquals(1, $crawler->selectButton('Submit')->count());
    }

    public function testRequestTranslationWithInvalidLanguage()
    {
        // Try with no language parameter
        $response = $this->call('POST', '/request-translation');
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertContains(
            'Unsupported Language:',
            $response->getContent()
        );

        $response = $this->call('POST', '/request-translation', [
            'lang'=> 'klingonese'
        ]);
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertContains(
            'Unsupported Language: klingonese',
            $response->getContent()
        );
    }

    public function testSubmitTranslation()
    {
        $response = $this->call('POST', '/request-translation', [
            'lang' => 'en',
            'v' => 'This is a test translation',
        ]);
        $this->assertEquals(200, $response->getStatusCode());
    }
}

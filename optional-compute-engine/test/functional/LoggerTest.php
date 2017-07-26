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

use Silex\WebTestCase;
use Monolog\Handler\TestHandler;

/**
 * Class ControllersTest.
 */
class LoggerTest extends WebTestCase
{
    /**
     * Creates the application.
     *
     * @return \Symfony\Component\HttpKernel\HttpKernelInterface
     */
    public function createApplication()
    {
        $app = require __DIR__ . '/../../src/app.php';
        require __DIR__ . '/../../src/controllers.php';

        return $app;
    }

    public function testLogger()
    {
        $this->assertInstanceOf('Monolog\Logger', $this->app['monolog']);
        $this->assertInstanceOf('Monolog\Handler\ErrorLogHandler', $this->app['monolog.handler']);
    }

    public function testDeleteLogsId()
    {
        $model = $this->getMock('Google\Cloud\Samples\Bookshelf\DataModel\DataModelInterface');
        $model
            ->expects($this->once())
            ->method('read')
            ->will($this->returnValue(array('id' => '123', 'image_url' => null)));
        $model
            ->expects($this->once())
            ->method('delete');

        $this->app['bookshelf.model'] = $model;
        $this->app['monolog.handler'] = new TestHandler;

        $client = $this->createClient();
        $crawler = $client->request('POST', '/books/123/delete');
        $this->assertTrue($this->app['monolog.handler']->hasNotice('Deleted Book: 123'));
    }
}

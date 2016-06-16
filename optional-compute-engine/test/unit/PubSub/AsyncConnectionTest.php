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
namespace Google\Cloud\Samples\Bookshelf\PubSub;

class AsyncConnectionTest extends \PHPUnit_Framework_TestCase
{
    public function testPull()
    {
        $connection = new AsyncConnection();
        $requestWrapper = $this->getMockBuilder('Google\Cloud\RequestWrapper')
            ->disableOriginalConstructor()
            ->getMock();
        $requestWrapper->expects($this->once())
            ->method('send');
        $connection->setRequestWrapper($requestWrapper);

        $connection->pull([]);
    }

    public function testTick()
    {
        $handler = $this->getMockBuilder('GuzzleHttp\Handler\CurlMultiHandler')
            ->disableOriginalConstructor()
            ->getMock();
        $handler->expects($this->once())
            ->method('tick');

        $connection = new AsyncConnection();

        // set the handler property using ReflectionClass
        $class = new \ReflectionClass($connection);
        $property = $class->getProperty('handler');
        $property->setAccessible(true);
        $property->setValue($connection, $handler);

        $connection->tick();
    }
}

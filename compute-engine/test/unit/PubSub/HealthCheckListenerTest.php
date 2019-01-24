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

class HealthCheckListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testOnOpen()
    {
        $logger = $this->getMockBuilder('Monolog\Logger')
            ->disableOriginalConstructor()
            ->getMock();
        $conn = $this->getMock('Ratchet\ConnectionInterface');
        $conn->expects($this->once())
            ->method('send')
            ->will($this->returnCallback(function ($o) use (&$output) {
                $output = $o;
            }));
        $listener = new HealthCheckListener($logger);
        $listener->onOpen($conn);

        $this->assertContains('HTTP/1.1 200 OK', $output);
        $this->assertContains('Pubsub worker is running!', $output);
    }

    public function testOnError()
    {
        $logger = $this->getMockBuilder('Monolog\Logger')
            ->disableOriginalConstructor()
            ->getMock();
        $logger->expects($this->once())
            ->method('error')
            ->will($this->returnCallback(function ($m) use (&$message) {
                $message = $m;
            }));
        $conn = $this->getMock('Ratchet\ConnectionInterface');
        $e = new \Exception('This is the message');
        $listener = new HealthCheckListener($logger);
        $listener->onError($conn, $e);

        $this->assertEquals(
            sprintf('An error has occurred: %s', $e->getMessage()),
            $message
        );
    }

    public function testOnMessage()
    {
        $conn = $this->getMock('Ratchet\ConnectionInterface');
        $listener = new HealthCheckListener();
        $listener->onMessage($conn, 'unused message');
    }

    public function testOnClose()
    {
        $conn = $this->getMock('Ratchet\ConnectionInterface');
        $listener = new HealthCheckListener();
        $listener->onClose($conn);
    }
}

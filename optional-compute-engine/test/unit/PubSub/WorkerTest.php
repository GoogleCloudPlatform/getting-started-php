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

use Google\Cloud\PubSub\Message;

class WorkerTest extends \PHPUnit_Framework_TestCase
{
    public function testInvoke()
    {
        $bookId = 123;
        $ackId = 456;
        $logger = $this->getMockBuilder('Psr\Log\LoggerInterface')->disableOriginalConstructor()->getMock();
        $response = $this->getMockBuilder('Guzzle\Http\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();
        $response->expects($this->once())
            ->method('getBody')
            ->will($this->returnValue(json_encode([
                'receivedMessages' => [
                    [
                        'message' => ['attributes' => ['id' => $bookId]],
                        'ackId' => $ackId,
                    ],
                ]
            ])));
        $subscription = $this->getMockBuilder('Google\Cloud\PubSub\Subscription')
            ->disableOriginalConstructor()
            ->getMock();
        $pubSubMessage = new Message(['attributes' => ['id' => $bookId]], array('ackId' => $ackId));
        $subscription->expects($this->once())
            ->method('acknowledgeBatch')
            ->with([$pubSubMessage]);
        $promise = $this->getMockBuilder('GuzzleHttp\Promise\PromiseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $promise->expects($this->once())
            ->method('then')
            ->will($this->returnCallback(function ($callback) use ($response, $promise) {
                // mock the returning of the response
                $callback($response);
                return $promise;
            }));
        $connection = $this->getMockBuilder('Google\Cloud\Samples\Bookshelf\PubSub\AsyncConnection')
            ->disableOriginalConstructor()
            ->getMock();
        $connection->expects($this->once())
            ->method('pull')
            ->will($this->returnValue($promise));
        $job = $this->getMockBuilder('Google\Cloud\Samples\Bookshelf\PubSub\LookupBookDetailsJob')
            ->disableOriginalConstructor()
            ->getMock();
        $job->expects($this->once())
            ->method('work')
            ->with($bookId);
        $worker = new Worker($subscription, $job, $logger);

        // set the connection
        $class = new \ReflectionClass($worker);
        $property = $class->getProperty('connection');
        $property->setAccessible(true);
        $property->setValue($worker, $connection);

        $timer = null; // not used
        $worker($timer);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Testing an exception is thrown!
     */
    public function testExceptionIsThrown()
    {
        $logger = $this->getMockBuilder('Psr\Log\LoggerInterface')->disableOriginalConstructor()->getMock();
        $subscription = $this->getMockBuilder('Google\Cloud\PubSub\Subscription')
            ->disableOriginalConstructor()
            ->getMock();
        $promise = $this->getMockBuilder('GuzzleHttp\Promise\PromiseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $thenFn = function ($callback) use ($promise, &$called) {
            throw new \Exception('Testing an exception is thrown!');
        };
        $promise->expects($this->once())
            ->method('then')
            ->will($this->returnCallback($thenFn));
        $connection = $this->getMockBuilder('Google\Cloud\Samples\Bookshelf\PubSub\AsyncConnection')
            ->disableOriginalConstructor()
            ->getMock();
        $connection->expects($this->once())
            ->method('pull')
            ->will($this->returnValue($promise));
        $job = $this->getMockBuilder('Google\Cloud\Samples\Bookshelf\PubSub\LookupBookDetailsJob')
            ->disableOriginalConstructor()
            ->getMock();
        $worker = new Worker($subscription, $job, $logger);

        // set the connection
        $class = new \ReflectionClass($worker);
        $property = $class->getProperty('connection');
        $property->setAccessible(true);
        $property->setValue($worker, $connection);

        $timer = null; // not used
        $worker($timer);
    }

    public function testPromiseRejected()
    {
        $logger = $this->getMockBuilder('Psr\Log\LoggerInterface')->disableOriginalConstructor()->getMock();
        $subscription = $this->getMockBuilder('Google\Cloud\PubSub\Subscription')
            ->disableOriginalConstructor()
            ->getMock();
        $promise = $this->getMockBuilder('GuzzleHttp\Promise\PromiseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $promise->expects($this->exactly(1))
            ->method('getState')
            ->will($this->returnValue('rejected'));
        $promise->expects($this->exactly(1))
            ->method('wait');
        $job = $this->getMockBuilder('Google\Cloud\Samples\Bookshelf\PubSub\LookupBookDetailsJob')
            ->disableOriginalConstructor()
            ->getMock();
        $worker = new Worker($subscription, $job, $logger);

        // set the promise
        $class = new \ReflectionClass($worker);
        $property = $class->getProperty('promise');
        $property->setAccessible(true);
        $property->setValue($worker, $promise);

        $timer = null; // not used
        $worker($timer);
    }

    public function testMarkTaskComplete()
    {
        $logger = $this->getMockBuilder('Psr\Log\LoggerInterface')->disableOriginalConstructor()->getMock();
        $subscription = $this->getMockBuilder('Google\Cloud\PubSub\Subscription')
            ->disableOriginalConstructor()
            ->getMock();
        $promise = $this->getMockBuilder('GuzzleHttp\Promise\PromiseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $thenFn = function ($callback) use ($promise, &$called) {
            // skip the callback, mark as called
            $called++;
            return $promise;
        };
        $promise->expects($this->exactly(2))
            ->method('then')
            ->will($this->returnCallback($thenFn));
        $promise->expects($this->exactly(2))
            ->method('getState')
            ->will($this->onConsecutiveCalls(
                $this->returnValue('pending'),
                $this->returnValue('fulfilled')
            ));
        $connection = $this->getMockBuilder('Google\Cloud\Samples\Bookshelf\PubSub\AsyncConnection')
            ->disableOriginalConstructor()
            ->getMock();
        $connection->expects($this->exactly(2))
            ->method('pull')
            ->will($this->returnValue($promise));
        $job = $this->getMockBuilder('Google\Cloud\Samples\Bookshelf\PubSub\LookupBookDetailsJob')
            ->disableOriginalConstructor()
            ->getMock();
        $worker = new Worker($subscription, $job, $logger);

        // set the connection
        $class = new \ReflectionClass($worker);
        $property = $class->getProperty('connection');
        $property->setAccessible(true);
        $property->setValue($worker, $connection);

        $timer = null; // not used
        $worker($timer);
        $this->assertEquals(1, $called);

        // call again to ensure the method is not called again
        $worker($timer);
        $this->assertEquals(1, $called);

        $worker($timer);
        $this->assertEquals(2, $called);
    }
}

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

class LookupBookDetailsJobTest extends \PHPUnit_Framework_TestCase
{
    public function testWork()
    {
        $bookId = 123;
        $volume = new \Google_Service_Books_Volume([
            'volumeInfo' => [
                'imageLinks' => ['thumbnail' => 'testImage.jpg']
            ]
        ]);
        $logger = $this->getMockBuilder('Psr\Log\LoggerInterface')->disableOriginalConstructor()->getMock();
        $client = $this->getMock('Google_Client');
        $client->expects($this->exactly(2))
            ->method('getLogger')
            ->will($this->returnValue($logger));
        $client->expects($this->once())
            ->method('execute')
            ->will($this->returnValue([$volume]));
        $model = $this->getMock('Google\Cloud\Samples\Bookshelf\DataModel\DataModelInterface');
        $model->expects($this->once())
            ->method('read')
            ->will($this->returnValue([
                'title' => 'My Book Title',
            ]));
        $model->expects($this->once())
            ->method('update')
            ->with(['title' => 'My Book Title', 'image_url' => 'testImage.jpg'])
            ->will($this->returnValue(true));

        $job = new LookupBookDetailsJob($model, $client, $logger);
        $result = $job->work($bookId);
        $this->assertTrue($result);
    }

    public function testNoBookMatch()
    {
        $bookId = 123;
        $logger = $this->getMockBuilder('Psr\Log\LoggerInterface')->disableOriginalConstructor()->getMock();
        $client = $this->getMock('Google_Client');
        $client->expects($this->once())
            ->method('getLogger')
            ->will($this->returnValue($logger));
        $client->expects($this->once())
            ->method('execute')
            ->will($this->returnValue([]));
        $model = $this->getMock('Google\Cloud\Samples\Bookshelf\DataModel\DataModelInterface');
        $model->expects($this->once())
            ->method('read')
            ->will($this->returnValue([
                'title' => 'My Book Title',
            ]));

        $job = new LookupBookDetailsJob($model, $client);
        $result = $job->work($bookId);
        $this->assertFalse($result);
    }

    public function testNoBookImages()
    {
        $bookId = 123;
        $volume = new \Google_Service_Books_Volume([
            'volumeInfo' => [
                'imageLinks' => ['thumbnail' => '']
            ]
        ]);
        $logger = $this->getMockBuilder('Psr\Log\LoggerInterface')->disableOriginalConstructor()->getMock();
        $client = $this->getMock('Google_Client');
        $client->expects($this->once())
            ->method('getLogger')
            ->will($this->returnValue($logger));
        $client->expects($this->once())
            ->method('execute')
            ->will($this->returnValue([$volume]));
        $model = $this->getMock('Google\Cloud\Samples\Bookshelf\DataModel\DataModelInterface');
        $model->expects($this->once())
            ->method('read')
            ->will($this->returnValue([
                'title' => 'My Book Title',
            ]));

        $job = new LookupBookDetailsJob($model, $client);
        $result = $job->work($bookId);
        $this->assertFalse($result);
    }

    public function testNoBookImageInSecondResult()
    {
        $bookId = 123;
        $volume1 = new \Google_Service_Books_Volume([
            'volumeInfo' => [
                'imageLinks' => ['thumbnail' => '']
            ]
        ]);
        $volume2 = new \Google_Service_Books_Volume([
            'volumeInfo' => [
                'imageLinks' => ['thumbnail' => 'testImage.jpg']
            ]
        ]);
        $logger = $this->getMockBuilder('Psr\Log\LoggerInterface')->disableOriginalConstructor()->getMock();
        $client = $this->getMock('Google_Client');
        $client->expects($this->exactly(2))
            ->method('getLogger')
            ->will($this->returnValue($logger));
        $client->expects($this->once())
            ->method('execute')
            ->will($this->returnValue([$volume1, $volume2]));
        $model = $this->getMock('Google\Cloud\Samples\Bookshelf\DataModel\DataModelInterface');
        $model->expects($this->once())
            ->method('read')
            ->will($this->returnValue([
                'title' => 'My Book Title',
            ]));
        $model->expects($this->once())
            ->method('update')
            ->with(['title' => 'My Book Title', 'image_url' => 'testImage.jpg'])
            ->will($this->returnValue(true));

        $job = new LookupBookDetailsJob($model, $client);
        $result = $job->work($bookId);
        $this->assertTrue($result);
    }
}

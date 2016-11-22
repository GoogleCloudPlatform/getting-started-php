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
namespace Google\Cloud\Samples\Bookshelf\FileSystem;

use Google\Cloud\Samples\Bookshelf\SkipTestsIfMissingCredentialsTrait;
use Google\Cloud\Samples\Bookshelf\GetConfigTrait;

class CloudStorageTest extends \PHPUnit_Framework_TestCase
{
    use SkipTestsIfMissingCredentialsTrait;
    use GetConfigTrait;

    public function testOne()
    {
        $config = $this->getConfig();
        $projectId = $config['google_project_id'];
        $bucketName = $projectId . '.appspot.com';
        $storage = new CloudStorage($projectId, $bucketName);
        $url = $storage->storeFile(__DIR__ . '/../../lib/CatHat.jpg', 'image/jpg');
        try {
            $this->assertStringStartsWith(
                "https://www.googleapis.com/download/storage/v1/b/$bucketName/o/",
                $url
            );
        } finally {  // clean up
            $storage->deleteFile($url);
        }
    }
}

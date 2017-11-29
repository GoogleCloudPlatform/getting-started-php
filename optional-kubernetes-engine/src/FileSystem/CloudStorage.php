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

use Google\Cloud\Storage\StorageClient;

/**
 * class CloudStorage stores images in Google Cloud Storage.
 */
class CloudStorage
{
    private $bucket;

    /**
     * CloudStorage constructor.
     *
     * @param string         $projectId The Google Cloud project id
     * @param string         $bucketName The cloud storage bucket name
     */
    public function __construct($projectId, $bucketName)
    {
        $storage = new StorageClient([
            'projectId' => $projectId,
        ]);
        $this->bucket = $storage->bucket($bucketName);
    }

    /**
     * Uploads a file to storage and returns the url of the new file.
     *
     * @param $localFilePath string
     * @param $contentType string
     *
     * @return string A URL pointing to the stored file.
     */
    public function storeFile($localFilePath, $contentType)
    {
        $f = fopen($localFilePath, 'r');
        $object = $this->bucket->upload($f, [
            'metadata' => ['contentType' => $contentType],
            'predefinedAcl' => 'publicRead',
        ]);
        return $object->info()['mediaLink'];
    }

    /**
     * Deletes a file.
     *
     * @param string $url A URL returned by a call to StorageFile.
     */
    public function deleteFile($url)
    {
        $path_components = explode('/', parse_url($url, PHP_URL_PATH));
        $name = $path_components[count($path_components) - 1];
        $object = $this->bucket->object($name);
        $object->delete();
    }
}

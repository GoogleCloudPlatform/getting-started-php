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

/**
 * Class CloudStorage implements ImageStorage using Google Cloud Storage
 * as the backend.
 */
class CloudStorage implements ImageStorage
{
    /**
     * CloudStorage constructor.
     *
     * @param \Google_Client $client     When null, a new Google_client is created
     *                                   that uses default application credentials.
     * @param string         $bucketName When null, uses environment variable
     *                                   GOOGLE_STORAGE_BUCKET.
     */
    public function __construct(\Google_Client $client = null, $bucketName = null)
    {
        if (!$client) {
            $client = new \Google_Client();
            $client->useApplicationDefaultCredentials();
            $client->setApplicationName('php bookshelf');
            $client->setScopes(\Google_Service_Storage::DEVSTORAGE_READ_WRITE);
        }
        if (!$bucketName) {
            $bucketName = getenv('GOOGLE_STORAGE_BUCKET');
        }
        $this->service = new \Google_Service_Storage($client);
        $this->bucketName = $bucketName;
    }

    public function storeFile($localFilePath, $contentType)
    {
        $obj = new \Google_Service_Storage_StorageObject();
        // Generate a unique file name so we don't try to write to files to
        // the same name.
        $name = uniqid('', true);
        $obj->setName($name);
        $obj = $this->service->objects->insert($this->bucketName, $obj, array(
            'data' => file_get_contents($localFilePath),
            'uploadType' => 'media',
            'name' => $name,
            'predefinedAcl' => 'publicread',
        ));

        return $obj->getMediaLink();
    }

    public function deleteFile($link)
    {
        $path_components = explode('/', parse_url($link, PHP_URL_PATH));
        $name = $path_components[count($path_components) - 1];
        $this->service->objects->delete($this->bucketName, $name);
    }
}

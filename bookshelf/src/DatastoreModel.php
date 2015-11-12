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

use Google_Service_Datastore;

/**
 * Class DatastoreModel implements the DataModel with a Google Data Store.
 *
 * Incomplete and untested.
 */
class DatastoreModel implements DataModel
{
    public function __construct($datastoreDatasetId = null)
    {
        if (!$datastoreDatasetId) {
            $datastoreDatasetId = getenv('GOOGLE_PROJECT_ID');
        }
        $this->datasetId = $datastoreDatasetId;
        $client = new \Google_Client();
        $client->setScopes([
            Google_Service_Datastore::CLOUD_PLATFORM,
            Google_Service_Datastore::DATASTORE,
            Google_Service_Datastore::USERINFO_EMAIL,
        ]);
        $client->useApplicationDefaultCredentials();
        $this->datastore = new \Google_Service_Datastore($client);
    }

    public function listBooks($limit = 10, $cursor = null)
    {
        $query = new \Google_Service_Datastore_Query();
        $query->setKinds(['Book']);
        $query->setOrder('title');
        $query->setLimit($limit);
        $query->setStartCursor($cursor);
        $request = new \Google_Service_Datastore_RunQueryRequest();
        $request->setQuery($query);
        $response = $this->datastore->datasets->
            runQuery($this->datasetId, $request);
        /** @var \Google_Service_Datastore_QueryResultBatch $batch */
        $batch = $response->getBatch();

        return array(
            'books' => $response,
            'next_page_token' => $batch->getEndCursor(),
        );
    }

    public function create($book, $id = null)
    {
        // TODO: Implement create() method.
    }

    public function read($id)
    {
        // TODO: Implement read() method.
    }

    public function update($book, $id = null)
    {
        // TODO: Implement update() method.
    }

    public function delete($id)
    {
        // TODO: Implement delete() method.
    }
}

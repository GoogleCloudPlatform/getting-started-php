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

namespace Google\Cloud\Samples\Bookshelf\DataModel;

use Google_Service_Datastore;

/**
 * Class Datastore implements the DataModel with a Google Data Store.
 */
class Datastore implements DataModelInterface
{
    protected $columns = [
        'id'            => 'integer',
        'title'         => 'string',
        'author'        => 'string',
        'publishedDate' => 'timestamp',
        'imageUrl'      => 'string',
        'description'   => 'string',
        'createdBy'     => 'string',
        'createdById'   => 'string',
    ];

    public function __construct($datastoreDatasetId)
    {
        $this->datasetId = $datastoreDatasetId;
        // Datastore API has intermittent failures, so we set the
        // Google Client to retry in the event of a 503 Backend Error
        $retryConfig = [ 'retries' => 2 ];
        $client = new \Google_Client([ 'retry' => $retryConfig ]);
        $client->setScopes([
            Google_Service_Datastore::CLOUD_PLATFORM,
            Google_Service_Datastore::DATASTORE,
        ]);
        $client->useApplicationDefaultCredentials();
        $this->datastore = new \Google_Service_Datastore($client);
    }

    public function listBooks($limit = 10, $cursor = null)
    {
        $query = new \Google_Service_Datastore_Query([
            'kind' => [
                [
                    'name' => 'Book',
                ],
            ],
            'order' => [
                'property' => [
                    'name' => 'title',
                ],
            ],
            'limit' => $limit,
            'startCursor' => $cursor,
        ]);

        $request = new \Google_Service_Datastore_RunQueryRequest();
        $request->setQuery($query);
        $response = $this->datastore->projects->
            runQuery($this->datasetId, $request);

        /** @var \Google_Service_Datastore_QueryResultBatch $batch */
        $batch = $response->getBatch();
        $endCursor = $batch->getEndCursor();

        $books = [];
        foreach ($batch->getEntityResults() as $entityResult) {
            $entity = $entityResult->getEntity();
            $book = $this->propertiesToBook($entity->getProperties());
            $book['id'] = $entity->getKey()->getPath()[0]->getId();
            $books[] = $book;
        }

        return array(
            'books' => $books,
            'cursor' => $endCursor === $cursor ? null : $endCursor,
        );
    }

    public function create($book, $key = null)
    {
        $this->verifyBook($book);

        if (is_null($key)) {
            $key = $this->createKey();
        }

        $properties = $this->bookToProperties($book);

        $entity = new \Google_Service_Datastore_Entity([
            'key' => $key,
            'properties' => $properties
        ]);

        // Use "NON_TRANSACTIONAL" for simplicity (as we're only making one call)
        $request = new \Google_Service_Datastore_CommitRequest([
            'mode' => 'NON_TRANSACTIONAL',
            'mutations' => [
                [
                    'upsert' => $entity,
                ]
            ]
        ]);

        $response = $this->datastore->projects->commit($this->datasetId, $request);

        $key = $response->getMutationResults()[0]->getKey();


        // return the ID of the created datastore item
        return $key->getPath()[0]->getId();
    }

    public function read($id)
    {
        $key = $this->createKey($id);
        $request = new \Google_Service_Datastore_LookupRequest([
            'keys' => [$key]
        ]);

        $response = $this->datastore->projects->
            lookup($this->datasetId, $request);

        /** @var \Google_Service_Datastore_QueryResultBatch $batch */
        if ($found = $response->getFound()) {
            $book = $this->propertiesToBook($found[0]['entity']['properties']);
            $book['id'] = $id;

            return $book;
        }

        return false;
    }

    public function update($book)
    {
        $this->verifyBook($book);

        if (!isset($book['id'])) {
            throw new InvalidArgumentException('Book must have an "id" attribute');
        }

        $key = $this->createKey($book['id']);
        $properties = $this->bookToProperties($book);

        $entity = new \Google_Service_Datastore_Entity([
            'key' => $key,
            'properties' => $properties
        ]);

        // Use "NON_TRANSACTIONAL" for simplicity (as we're only making one call)
        $request = new \Google_Service_Datastore_CommitRequest([
            'mode' => 'NON_TRANSACTIONAL',
            'mutations' => [
                [
                    'update' => $entity
                ]
            ]
        ]);

        $response = $this->datastore->projects->commit($this->datasetId, $request);

        // return the number of updated rows
        return 1;
    }

    public function delete($id)
    {
        $key = $this->createKey($id);

        // Use "NON_TRANSACTIONAL" for simplicity (as we're only making one call)
        $request = new \Google_Service_Datastore_CommitRequest([
            'mode' => 'NON_TRANSACTIONAL',
            'mutations' => [
                [
                    'delete' => $key
                ]
            ]
        ]);

        $response = $this->datastore->projects->commit($this->datasetId, $request);

        return true;
    }

    protected function createKey($id = null)
    {
        $key = new \Google_Service_Datastore_Key([
            'path' => [
                [
                    'kind' => 'Book'
                ],
            ]
        ]);

        // If we have an ID, set it in the path
        if ($id) {
            $key->getPath()[0]->setId($id);
        }

        return $key;
    }

    private function verifyBook($book)
    {
        if ($invalid = array_diff_key($book, $this->columns)) {
            throw new \InvalidArgumentException(sprintf(
                'unsupported book properties: "%s"',
                implode(', ', $invalid)
            ));
        }
    }

    private function bookToProperties(array $book)
    {
        $properties = [];
        foreach ($book as $colName => $colValue) {
            $propName = $this->columns[$colName] . 'Value';
            if (!empty($colValue)) {
                $properties[$colName] = [
                    $propName => $colValue
                ];
            }
        }

        return $properties;
    }

    private function propertiesToBook(array $properties)
    {
        $book = [];
        foreach ($this->columns as $colName => $colType) {
            $book[$colName] = null;
            if (isset($properties[$colName])) {
                $propName = $colType . 'Value';
                $book[$colName] = $properties[$colName][$propName];
            }
        }

        return $book;
    }
}

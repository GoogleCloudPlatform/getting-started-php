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

namespace Google\Cloud\Bookshelf;

use Google\Cloud\Firestore\FirestoreClient;

/**
 * CloudSql can implement a mysql or postgres database.
 * This example uses MySQL, but Postgres can be used by
 * changing the DSN.
 * @see getMysqlDsn
 *
 */
class CloudFirestore
{
    private $collection;
    private $columnNames = [
        'id',
        'title',
        'author',
        'published_date',
        'image_url',
        'description',
        'created_by',
        'created_by_id',
    ];

    /**
     * Creates the SQL books table if it doesn't already exist.
     */
    public function __construct($projectId, $collectionName)
    {
        // Create the Cloud Firestore client
        $db = new FirestoreClient([
            'projectId' => $projectId,
        ]);
        $this->collection = $db->collection($collectionName);
    }

    public function listBooks($limit = 10, $cursor = null)
    {
        $query = $this->collection
            ->limit($limit);

        if ($cursor && ($cursorDoc = $this->read($cursor))) {
            $query = $query->startAfter($cursorDoc);
        }

        $rows = [];
        $lastDoc = null;
        $newCursor = null;
        $documents = $query->documents();
        foreach ($documents as $document) {
            array_push($rows, $document);
            $lastDoc = $document;
            if (count($rows) == $limit) {
                $newCursor = $lastDoc->id();
            }
        }

        return array(
            'books' => $rows,
            'cursor' => $newCursor,
        );
    }

    public function create($book)
    {
        $this->verifyBook($book);
        $bookRef = $this->collection->newDocument();
        $bookRef->set($book);

        return $bookRef->id();
    }

    public function read($id)
    {
        $bookRef = $this->collection->document($id);
        $snapshot = $bookRef->snapshot();

        if (!$snapshot->exists()) {
            return null;
        }
        return $snapshot;
    }

    public function update($book)
    {
        $this->verifyBook($book);
        $bookRef = $this->collection->document($book['id']);
        $updateData = [];
        foreach ($this->columnNames as $value) {
            $updateData[] = [
                'path' => $value,
                'value' => $book[$value] ?? null,
            ];
        }
        $bookRef->update($updateData);
        return true;
    }

    public function delete($id)
    {
        $bookRef = $this->collection->document($id);
        $snapshot = $bookRef->snapshot();

        if ($snapshot->exists()) {
            $bookRef->delete();
            return true;
        }
        return false;
    }

    /**
     * Throws an exception if $book contains an invalid key.
     *
     * @param $book array
     *
     * @throws \Exception
     */
    private function verifyBook($book)
    {
        if ($invalid = array_diff_key($book, array_flip($this->columnNames))) {
            throw new \Exception(sprintf(
                'unsupported book properties: "%s"',
                implode(', ', $invalid)
            ));
        }
    }
}

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

use \MongoDB\BSON\ObjectId;

/**
 * Class MongoDb implements the DataModel with MongoDB.
 *
 * To use this class, set two environment variables; MONGO_URL and
 * MONGO_NAMESPACE.
 */
class MongoDb implements DataModelInterface
{
    /**
     * Properties of the books.
     *
     * @var array
     */
    protected $columns = array(
        'id',
        'title',
        'author',
        'published_date',
        'image_url',
        'description',
        'created_by',
        'created_by_id',
    );

    /**
     * MongoDB collection.
     *
     * @var \MongoDB\Collection
     */
    private $db;

    /**
     * Connects to the MongoDB server.
     */
    public function __construct($dbUrl, $database, $collection)
    {
        $manager = new \MongoDB\Driver\Manager($dbUrl);
        $this->db = new \MongoDB\Collection($manager, $database, $collection);
    }

    /**
     * @see DataModelInterface::listBooks
     */
    public function listBooks($limit = 10, $cursor = null)
    {
        if ($cursor) {
            $q = $this->db->find(
                array('_id' => array('$gt' => new ObjectId($cursor))),
                array('sort' => array('_id' => 1))
            );
        } else {
            $q = $this->db->find(
                array(),
                array('sort' => array('_id' => 1))
            );
        }
        $rows = array();
        $last_row = null;
        $new_cursor = null;
        foreach ($q as $row) {
            if (count($rows) == $limit) {
                $new_cursor = (string) ($last_row->_id);
                break;
            }
            array_push($rows, $this->bookToArray($row));
            $last_row = $row;
        }
        return array(
            'books' => $rows,
            'cursor' => $new_cursor,
        );
    }

    /**
     * @see DataModelInterface::create
     */
    public function create($book, $id = null)
    {
        $this->verifyBook($book);
        if ($id) {
            $book['_id'] = $id;
        }
        $result = $this->db->insertOne($book);
        return $result->getInsertedId();
    }

    /**
     * @see DataModelInterface::read
     */
    public function read($id)
    {
        $result = $this->db->findOne(
            array('_id' => new ObjectId($id)));
        if ($result) {
            return $this->bookToArray($result);
        }
        return false;
    }

    /**
     * @see DataModelInterface::update
     */
    public function update($book)
    {
        $this->verifyBook($book);
        $result = $this->db->replaceOne(
            array('_id' => new ObjectId($book['id'])),
            $this->arrayToBook($book));
        return $result->getModifiedCount();
    }

    /**
     * @see DataModelInterface::delete
     */
    public function delete($id)
    {
        $result = $this->db->deleteOne(
            array('_id' => new ObjectId($id)));
        return $result->getDeletedCount();
    }

    /**
     * Throws an exception if $book contains an invalid key.
     *
     * @param $book array
     *
     * @throws \InvalidArgumentException
     */
    private function verifyBook($book)
    {
        if ($invalid = array_diff_key($book, array_flip($this->columns))) {
            throw new \InvalidArgumentException(sprintf(
                'unsupported book properties: "%s"',
                implode(', ', $invalid)
            ));
        }
    }

    /**
     * Converts an array to a \stdClass object representing a book.
     *
     * @param $array array
     *
     * @return \stdClass
     */
    private function arrayToBook($array)
    {
        $book = new \stdClass();
        foreach ($this->columns as $column) {
            if ($column == 'id') {
                $book->_id = new ObjectId($array['id']);
            } elseif (isset($array[$column])) {
                $book->{$column} = $array[$column];
            } else {
                $book->{$column} = null;
            }
        }
        return $book;
    }

    /**
     * Converts a \stdClass object to an array representing a book.
     *
     * @param $book \stdClass
     *
     * @return array
     */
    private function bookToArray($book)
    {
        $ret = array();
        foreach ($this->columns as $column) {
            if ($column == 'id') {
                $ret['id'] = (string) $book->_id;
            } elseif (isset($book->{$column})) {
                $ret[$column] = $book->{$column};
            } else {
                $ret[$column] = null;
            }
        }
        return $ret;
    }
}

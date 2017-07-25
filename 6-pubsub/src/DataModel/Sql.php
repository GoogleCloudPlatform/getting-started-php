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

use PDO;

/**
 * Class Sql implements the DataModelInterface with a mysql or postgres database.
 *
 */
class Sql implements DataModelInterface
{
    private $dsn;
    private $user;
    private $password;

    /**
     * Creates the SQL books table if it doesn't already exist.
     */
    public function __construct($dsn, $user, $password)
    {
        $this->dsn = $dsn;
        $this->user = $user;
        $this->password = $password;

        $columns = array(
            'id serial PRIMARY KEY ',
            'title VARCHAR(255)',
            'author VARCHAR(255)',
            'published_date VARCHAR(255)',
            'image_url VARCHAR(255)',
            'description VARCHAR(255)',
            'created_by VARCHAR(255)',
            'created_by_id VARCHAR(255)',
        );

        $this->columnNames = array_map(function ($columnDefinition) {
            return explode(' ', $columnDefinition)[0];
        }, $columns);
        $columnText = implode(', ', $columns);
        $pdo = $this->newConnection();
        $pdo->query("CREATE TABLE IF NOT EXISTS books ($columnText)");
    }

    /**
     * Creates a new PDO instance and sets error mode to exception.
     *
     * @return PDO
     */
    private function newConnection()
    {
        $pdo = new PDO($this->dsn, $this->user, $this->password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $pdo;
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

    public function listBooks($limit = 10, $cursor = null)
    {
        $pdo = $this->newConnection();
        if ($cursor) {
            $query = 'SELECT * FROM books WHERE id > :cursor ORDER BY id' .
                ' LIMIT :limit';
            $statement = $pdo->prepare($query);
            $statement->bindValue(':cursor', $cursor, PDO::PARAM_INT);
        } else {
            $query = 'SELECT * FROM books ORDER BY id LIMIT :limit';
            $statement = $pdo->prepare($query);
        }
        $statement->bindValue(':limit', $limit + 1, PDO::PARAM_INT);
        $statement->execute();
        $rows = array();
        $last_row = null;
        $new_cursor = null;
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            if (count($rows) == $limit) {
                $new_cursor = $last_row['id'];
                break;
            }
            array_push($rows, $row);
            $last_row = $row;
        }

        return array(
            'books' => $rows,
            'cursor' => $new_cursor,
        );
    }

    public function create($book, $id = null)
    {
        $this->verifyBook($book);
        if ($id) {
            $book['id'] = $id;
        }
        $pdo = $this->newConnection();
        $names = array_keys($book);
        $placeHolders = array_map(function ($key) {
            return ":$key";
        }, $names);
        $sql = sprintf(
            'INSERT INTO books (%s) VALUES (%s)',
            implode(', ', $names),
            implode(', ', $placeHolders)
        );
        $statement = $pdo->prepare($sql);
        $statement->execute($book);

        return $pdo->lastInsertId();
    }

    public function read($id)
    {
        $pdo = $this->newConnection();
        $statement = $pdo->prepare('SELECT * FROM books WHERE id = :id');
        $statement->bindValue('id', $id, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    public function update($book)
    {
        $this->verifyBook($book);
        $pdo = $this->newConnection();
        $assignments = array_map(
            function ($column) {
                return "$column=:$column";
            },
            $this->columnNames
        );
        $assignmentString = implode(',', $assignments);
        $sql = "UPDATE books SET $assignmentString WHERE id = :id";
        $statement = $pdo->prepare($sql);
        $values = array_merge(
            array_fill_keys($this->columnNames, null),
            $book
        );
        return $statement->execute($values);
    }

    public function delete($id)
    {
        $pdo = $this->newConnection();
        $statement = $pdo->prepare('DELETE FROM books WHERE id = :id');
        $statement->bindValue('id', $id, PDO::PARAM_INT);
        $statement->execute();

        return $statement->rowCount();
    }

    public static function getMysqlDsn($dbName, $port, $connectionName = null)
    {
        if ($connectionName) {
            return sprintf('mysql:unix_socket=/cloudsql/%s;dbname=%s',
                $connectionName,
                $dbName);
        }

        return sprintf('mysql:host=127.0.0.1;port=%s;dbname=%s', $port, $dbName);
    }

    public static function getPostgresDsn($dbName, $port, $connectionName = null)
    {
        if ($connectionName) {
            return sprintf('pgsql:host=/cloudsql/%s;dbname=%s',
                $connectionName,
                $dbName);
        }

        return sprintf('pgsql:host=127.0.0.1;port=%s;dbname=%s', $port, $dbName);
    }
}

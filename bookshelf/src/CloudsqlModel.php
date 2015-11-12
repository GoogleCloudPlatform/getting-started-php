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

use PDO;

/**
 * Class CloudsqlModel implements the DataModel with a mysql database.
 *
 * Set the three environment variables MYSQL_DSN, MYSQL_USER,
 * and MYSQL_PASSWORD.
 */
class CloudsqlModel implements DataModel
{
    /**
     * Creates a new PDO instance and sets error mode to exception.
     *
     * @return PDO
     */
    private function newConnection()
    {
        $pdo = new PDO(getenv('MYSQL_DSN'), getenv('MYSQL_USER'),
            getenv('MYSQL_PASSWORD'));
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }

    /**
     * Creates the SQL books table if it doesn't already exist.
     */
    public function __construct()
    {
        $columns = array(
            'id INT NOT NULL AUTO_INCREMENT PRIMARY KEY ',
            'title VARCHAR(255)',
            'author VARCHAR(255)',
            'publishedDate DATE',
            'imageUrl VARCHAR(255)',
            'description VARCHAR(255)',
            'createdBy VARCHAR(255)',
            'createdById VARCHAR(255)',
        );
        $this->columnNames = array_map(function ($columnDefinition) {
            return explode(' ', $columnDefinition)[0];
        }, $columns);
        $columnText = implode(', ', $columns);
        $pdo = $this->newConnection();
        $pdo->query("CREATE TABLE IF NOT EXISTS books ($columnText)");
    }

    public function listBooks($limit = 10, $cursor = null)
    {
        $pdo = $this->newConnection();
        if ($cursor) {
            $query = 'SELECT * FROM books WHERE id > :cursor ORDER BY id'.
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
        $names = '';
        $values = [];
        $question_marks = '';
        $separator = '';
        $pdo = $this->newConnection();
        foreach ($this->columnNames as $column) {
            if (array_key_exists($column, $book)) {
                $question_marks = "$question_marks$separator?";
                $names = "$names$separator$column";
                $separator = ', ';
                array_push($values, $book[$column]);
            }
        }
        $statement = $pdo->prepare(
            "INSERT INTO books ($names) VALUES($question_marks)"
        );
        $statement->execute($values);

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
        $assignments = '';
        $values = [];
        $separator = '';
        $pdo = $this->newConnection();
        foreach ($this->columnNames as $column) {
            if (array_key_exists($column, $book)) {
                $assignments = "$assignments$separator$column=?";
                $separator = ', ';
                array_push($values, $book[$column]);
            }
        }
        $statement = $pdo->prepare(
            "UPDATE books SET $assignments WHERE id = ?"
        );
        array_push($values, $book['id']);
        $statement->execute($values);

        return $statement->rowCount();
    }

    public function delete($id)
    {
        $pdo = $this->newConnection();
        $statement = $pdo->prepare('DELETE FROM books WHERE id = ?');
        $statement->bindValue(1, $id, PDO::PARAM_INT);
        $statement->execute();

        return $statement->rowCount();
    }
}

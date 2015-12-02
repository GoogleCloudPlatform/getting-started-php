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
 * Common code to test all data models.
 *
 * @property DataModelInterface $model Must be set by parent class.
 */
trait DataModelTestTrait
{
    /**
     * Tests all the crud operations in a data model.
     * It iterates through all the books in the data model, so for performance
     * reasons, the data model should not be prefilled with hundreds of books.
     */
    public function testDataModel()
    {
        $model = $this->model;
        // Iterate over the existing books and count the rows.
        $fetch = array('cursor' => null);
        $rowCount = 0;
        do {
            $fetch = $model->listBooks(10, $fetch['cursor']);
            $rowCount += count($fetch['books']);
        } while ($fetch['cursor']);

        // Insert two books.
        $breakfastId = $model->create(array(
            'title' => 'Breakfast of Champions',
            'author' => 'Kurt Vonnegut'
        ));

        $bellId = $model->create(array(
            'title' => 'For Whom the Bell Tolls',
            'author' => 'Ernest Hemingway'
        ));

        // Try to create a book with a bad property name.
        try {
            $model->create(array(
                'bogus' => 'Teach your owl to drive!'
            ));
            $this->assertFalse(true);
        } catch (\Exception $e) {
            // Good.  An exception is expected.
        }

        // Iterate over the books again and verify there are now 2 more.
        $newCount = 0;
        do {
            // Only fetch one book at a time to test that code path.
            $fetch = $model->listBooks(1, $fetch['cursor']);
            $newCount += count($fetch['books']);
        } while ($fetch['cursor']);
        $this->assertEquals($rowCount + 2, $newCount);

        // Make sure the book we read looks like the book we wrote.
        $breakfastBook = $model->read($breakfastId);
        $this->assertEquals('Breakfast of Champions', $breakfastBook['title']);
        $this->assertEquals('Kurt Vonnegut', $breakfastBook['author']);
        $this->assertEquals($breakfastId, $breakfastBook['id']);
        $this->assertNull($breakfastBook['description']);

        // Try updating a book.
        $breakfastBook['description'] = 'A really fun read.';
        $model->update($breakfastBook);
        $breakfastBookCopy = $model->read($breakfastId);
        // And confirm it was correctly updated.
        $this->assertEquals(
            'A really fun read.',
            $breakfastBookCopy['description']
        );

        // Update it again and delete the description.
        unset($breakfastBook['description']);
        $breakfastBook['author'] = null;
        $model->update($breakfastBook);
        $breakfastBookCopy = $model->read($breakfastId);
        // And confirm it was correctly updated.
        $this->assertNull($breakfastBookCopy['description']);
        $this->assertNull($breakfastBookCopy['author']);

        // Try updating the book with a bad property name.
        try {
            $book['bogus'] = 'The power of scratching.';
            $model->update($book);
            $this->assertFalse(true);
        } catch (\Exception $e) {
            // Good.  An exception is expected.
        }

        // Clean up.
        $model->delete($breakfastId);
        $this->assertFalse($model->read($breakfastId));
        $this->assertTrue((bool)$model->read($bellId));
        $model->delete($bellId);
        $this->assertFalse($model->read($bellId));
    }
}

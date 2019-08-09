<?php
#
# Copyright 2019 Google LLC.
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#     http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.

// NOTE: These view files use output buffering to mock inheritance without the
// use of a templating language. This is done for simplicity. For production, a
// templating language such as Twig or Blade is recommended.
ob_start() ?>

<?php
/**
 * A form for creating and edit a book.
 *
 * @param $book The book to display, "null" for new books.
 * @param $action The action the form will take, either "Edit" or "Add".
 */
?>

<h3><?= $action ?> book</h3>

<form method="POST" enctype="multipart/form-data">

  <div class="form-group">
    <label for="title">Title</label>
    <input type="text" name="title" id="title" value="<?= $book ? $book->get('title') : '' ?>" class="form-control"/>
  </div>

  <div class="form-group">
    <label for="author">Author</label>
    <input type="text" name="author" id="author" value="<?= $book ? $book->get('author') : '' ?>" class="form-control"/>
  </div>

  <div class="form-group">
    <label for="published_date">Date Published</label>
    <input type="text" name="published_date" id="published_date" value="<?= $book ? $book->get('published_date') : '' ?>" class="form-control"/>
  </div>

  <div class="form-group">
    <label for="description">Description</label>
    <textarea name="description" id="description" class="form-control"><?= $book ? $book->get('description') : '' ?></textarea>
  </div>

  <div class="form-group">
    <label for="image">Cover Image</label>
    <input type="file" name="image" id="image" class="form-control"/>
  </div>

  <div class="form-group hidden">
    <label for="image_url">Cover Image URL</label>
    <input type="text" name="image_url" id="image_url" value="<?= $book ? $book->get('image_url') : '' ?>" class="form-control"/>
  </div>

  <button id="submit" type="submit" class="btn btn-success">Save</button>
</form>

<?php // The base.php template is rendered using the contents of this template
      // which is sent in the $content variable?>
<?= view('base', ['content' => ob_get_clean() ]) ?>

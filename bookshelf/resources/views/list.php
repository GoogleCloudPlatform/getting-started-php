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
 * A list view of a set of books.
 *
 * If the number of books provided equals the number of books on a page, display
 * pagination controls.
 *
 * @param $books    The list of books to display.
 * @param $pageSize The maximum number of books on a page.
 */
?>

<h3>Books</h3>
<a href="/books/add" class="btn btn-success btn-sm">
  <i class="glyphicon glyphicon-plus"></i>
  Add book
</a>

<?php foreach ($books as $i => $book): ?>
<div class="media">
  <a href="/books/<?= $book->id() ?>">
    <?php if ($imgUrl = $book->get('image_url')): ?>
      <div class="media-left">
        <img src="<?= $imgUrl ?>">
      </div>
    <?php endif ?>
    <div class="media-body">
      <h4><?= $book->get('title') ?></h4>
      <p><?= $book->get('author') ?></p>
    </div>
  </a>
</div>
<?php endforeach ?>
<?php if (!isset($book)): ?>
<p>No books found</p>
<?php elseif ($i + 1 == $pageSize): ?>
<nav>
  <ul class="pager">
    <li><a href="?page_token=<?= $book->id() ?>">More</a></li>
  </ul>
</nav>
<?php endif ?>

<?php // The base.php template is rendered using the contents of this template
      // which is sent in the $content variable?>
<?= view('base', ['content' => ob_get_clean() ]) ?>

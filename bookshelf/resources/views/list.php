<?php
#
# Copyright 2015 Google Inc.
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
ob_start()
?>

<h3>Books</h3>
<a href="/books/add" class="btn btn-success btn-sm">
  <i class="glyphicon glyphicon-plus"></i>
  Add book
</a>

<?php // [START book_list] ?>
<?php foreach ($books as $i => $book): ?>
<div class="media">
  <a href="/books/<?= $book->id() ?>">
    <?php // [START book_image] ?>
    <?php if (!empty($book['image_url'])): ?>
      <div class="media-left">
        <img src="<?= $book['image_url'] ?>">
      </div>
    <?php endif ?>
    <?php // [END book_image] ?>
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
<?php // [END book_list] ?>

<?= view('base', ['content' => ob_get_clean() ]) ?>

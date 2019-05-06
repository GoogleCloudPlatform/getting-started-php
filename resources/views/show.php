<?php
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
ob_start() ?>

<h3>Book</h3>
<form method="post" action="/books/<?= $book->id() ?>/delete" id="deleteForm">
  <div class="btn-group">
    <a href="/books/<?= $book->id() ?>/edit" class="btn btn-primary btn-sm">
      <i class="glyphicon glyphicon-edit"></i>
      Edit book
    </a>
    <button id="submit" type="submit" class="btn btn-danger btn-sm">
      <i class="glyphicon glyphicon-trash"></i>
      Delete book
    </button>
  </div>
</form>

<?php // [START book_details] ?>
<div class="media">
  <?php // [START book_image] ?>
  <?php if ($imgUrl = $book->get('image_url')): ?>
  <div class="media-left">
    <img class="book-image" src="<?= $imgUrl ?>" />
  </div>
  <?php endif ?>
  <?php // [END book_image] ?>
  <div class="media-body">
    <h4 class="book-title">
      <?= $book->get('title') ?>
      <small><?= $book->get('published_date') ?></small>
    </h4>
    <h5 class="book-author">By <?= $book->get('author') ?: 'Unknown' ?></h5>
    <p class="book-description"><?= $book->get('description') ?></p>
  </div>
</div>
<?php // [END book_details] ?>

<?= view('base', ['content' => ob_get_clean() ]) ?>

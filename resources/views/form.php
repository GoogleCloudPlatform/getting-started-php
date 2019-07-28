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
ob_start() ?>

<?php // [START book_form] ?>
<h3><?= $action ?> book</h3>

<form method="POST" enctype="multipart/form-data">

  <div class="form-group">
    <label for="title">Title</label>
    <input type="text" name="title" id="title" value="<?= $book['title'] ?? '' ?>" class="form-control"/>
  </div>

  <div class="form-group">
    <label for="author">Author</label>
    <input type="text" name="author" id="author" value="<?= $book['author'] ?? '' ?>" class="form-control"/>
  </div>

  <div class="form-group">
    <label for="published_date">Date Published</label>
    <input type="text" name="published_date" id="published_date" value="<?= $book['published_date'] ?? '' ?>" class="form-control"/>
  </div>

  <div class="form-group">
    <label for="description">Description</label>
    <textarea name="description" id="description" class="form-control"><?= $book['description'] ?? '' ?></textarea>
  </div>

  <?php // [START book_form_image] ?>
  <div class="form-group">
    <label for="image">Cover Image</label>
    <input type="file" name="image" id="image" class="form-control"/>
  </div>

  <div class="form-group hidden">
    <label for="image_url">Cover Image URL</label>
    <input type="text" name="image_url" id="image_url" value="<?= $book['image_url'] ?? '' ?>" class="form-control"/>
  </div>
  <?php // [END book_form_image] ?>

  <button id="submit" type="submit" class="btn btn-success">Save</button>
</form>
<?php // [END book_form] ?>

<?= view('base', ['content' => ob_get_clean() ]) ?>

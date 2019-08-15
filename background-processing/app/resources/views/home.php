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
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Translations</title>
    <!-- [START getting_started_background_js] -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="https://code.getmdl.io/1.3.0/material.indigo-pink.min.css">
    <script defer src="https://code.getmdl.io/1.3.0/material.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
    <script>
        $(document).ready(function() {
            $("#translate-form").submit(function(e) {
                e.preventDefault();
                // Get value, make sure it's not empty.
                if ($("#v").val() == "") {
                    return;
                }
                $.ajax({
                    type: "POST",
                    url: "/request-translation",
                    data: $(this).serialize(),
                    success: function(data) {
                        // Show snackbar.
                        console.log(data);
                        var notification = document.querySelector('.mdl-js-snackbar');
                        $("#snackbar").removeClass("mdl-color--red-100");
                        $("#snackbar").addClass("mdl-color--green-100");
                        notification.MaterialSnackbar.showSnackbar({
                            message: 'Translation requested'
                        });
                    },
                    error: function(data) {
                        // Show snackbar.
                        console.log("Error requesting translation");
                        var notification = document.querySelector('.mdl-js-snackbar');
                        $("#snackbar").removeClass("mdl-color--green-100");
                        $("#snackbar").addClass("mdl-color--red-100");
                        notification.MaterialSnackbar.showSnackbar({
                            message: 'Translation request failed'
                        });
                    }
                });
            });
        });
    </script>
    <style>
        .lang {
            width: 50px;
        }
        .translate-form {
            display: inline;
        }
    </style>
    <!-- [END getting_started_background_js] -->
</head>
<!-- [START getting_started_background_html] -->
<body>
  <div class="mdl-layout mdl-js-layout mdl-layout--fixed-header">
    <header class="mdl-layout__header">
      <div class="mdl-layout__header-row">
        <!-- Title -->
        <span class="mdl-layout-title">Translate with Background Processing</span>
      </div>
    </header>
    <main class="mdl-layout__content">
      <div class="page-content">
        <div class="mdl-grid">
          <div class="mdl-cell mdl-cell--1-col"></div>
          <div class="mdl-cell mdl-cell--3-col">
            <form id="translate-form" class="translate-form">
              <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input class="mdl-textfield__input" type="text" id="v" name="v">
                <label class="mdl-textfield__label" for="v">Text to translate...</label>
              </div>
              <select class="mdl-textfield__input lang" name="lang">
                <option value="de">de</option>
                <option value="en">en</option>
                <option value="es">es</option>
                <option value="fr">fr</option>
                <option value="ja">ja</option>
                <option value="sw">sw</option>
              </select>
              <button class="mdl-button mdl-js-button mdl-button--raised mdl-button--accent" type="submit"
                  name="submit">Submit</button>
            </form>
          </div>
          <div class="mdl-cell mdl-cell--8-col">
            <table class="mdl-data-table mdl-js-data-table mdl-shadow--2dp">
              <thead>
                <tr>
                  <th class="mdl-data-table__cell--non-numeric"><strong>Original</strong></th>
                  <th class="mdl-data-table__cell--non-numeric"><strong>Translation</strong></th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($translations as $translation): ?>
                <tr>
                  <td class="mdl-data-table__cell--non-numeric">
                      <span class="mdl-chip mdl-color--primary">
                          <span class="mdl-chip__text mdl-color-text--white"><?= $translation['originalLang'] ?></span>
                      </span>
                  <?= $translation['original'] ?>
                  </td>
                  <td class="mdl-data-table__cell--non-numeric">
                      <span class="mdl-chip mdl-color--accent">
                          <span class="mdl-chip__text mdl-color-text--white"><?= $translation['lang'] ?></span>
                      </span>
                      <?= $translation['translated'] ?>
                  </td>
                </tr>
              <?php endforeach ?>
              </tbody>
            </table>
            <br/>
            <button class="mdl-button mdl-js-button mdl-button--raised" type="button" onClick="window.location.reload();">Refresh</button>
          </div>
        </div>
      </div>
      <div aria-live="assertive" aria-atomic="true" aria-relevant="text" class="mdl-snackbar mdl-js-snackbar" id="snackbar">
          <div class="mdl-snackbar__text mdl-color-text--black"></div>
          <button type="button" class="mdl-snackbar__action"></button>
      </div>
    </main>
  </div>
</body>
</html>

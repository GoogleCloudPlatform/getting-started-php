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

namespace Google\Cloud\Samples\Bookshelf\User;

use Symfony\Component\HttpFoundation\Request;

/**
 * class SimepleUser controlls the user in the session
 */
class SimpleUser
{
  public $id;
  public $name;
  public $imageUrl;

  public function __construct($id = null, $name = null, $imageUrl = null)
  {
    $this->id = $id;
    $this->name = $name;
    $this->imageUrl = $imageUrl;
  }

  public function getLoggedIn()
  {
    return !empty($this->id);
  }

  public static function createFromRequest(Request $request)
  {
    if ($userInfo = $request->cookies->get('google_user_info')) {
      $userInfo = json_decode($userInfo, true);

      return new SimpleUser($userInfo['sub'], $userInfo['name'], $userInfo['picture']);
    }

    return new SimpleUser;
  }
}
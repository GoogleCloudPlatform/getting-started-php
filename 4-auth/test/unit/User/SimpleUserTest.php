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

class SimpleUserTest extends \PHPUnit_Framework_TestCase
{
    public function testLoggedIn()
    {
        $id = 'fake-id';
        $user = new SimpleUser($id);

        $this->assertTrue($user->getLoggedIn());
        $this->assertNull($user->name);
        $this->assertNull($user->imageUrl);
    }

    public function testNotLoggedIn()
    {
        $user = new SimpleUser();

        $this->assertFalse($user->getLoggedIn());
        $this->assertNull($user->name);
        $this->assertNull($user->imageUrl);
    }

    public function testCreateFromRequest()
    {
        $userInfo = [
            'sub'       => 'fake-id',
            'name'      => 'Test Guy',
            'picture'   => 'http://fa.ke/image.jpg',
        ];

        $request = new Request();
        $request->cookies->set('google_user_info', json_encode($userInfo));

        $user = SimpleUser::createFromRequest($request);

        $this->assertTrue($user->getLoggedIn());
        $this->assertEquals($userInfo['sub'], $user->id);
        $this->assertEquals($userInfo['name'], $user->name);
        $this->assertEquals($userInfo['picture'], $user->imageUrl);
    }

    public function testCreateFromRequestWithoutCookie()
    {
        $request = new Request();
        $user = SimpleUser::createFromRequest($request);

        $this->assertFalse($user->getLoggedIn());
        $this->assertNull($user->name);
        $this->assertNull($user->imageUrl);
    }
}

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

require_once('BookshelfTestBase.php');

/**
 * Class MongoDbTest
 */
class MongoDbTest extends BookshelfTestBase
{
    protected static function setBackEnd()
    {
        putenv('BOOKSHELF_BACKEND=mongodb');
    }

    public function testIndex()
    {
        $this->assertNotNull(self::$versions[self::$step]);
        $this->session->visit($this->url . '/');
        $this->assertEquals('200', $this->session->getStatusCode(),
                            'Root URL status code.');
        // TODO: content check
        $page = $this->session->getPage();
    }
}

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
namespace Google\Cloud\Samples\Bookshelf\DataModel;

use Google\Cloud\Samples\Bookshelf\GetConfigTrait;
use Google\Cloud\Samples\Bookshelf\SkipTestsIfMissingCredentialsTrait;

class MongoDbTest extends \PHPUnit_Framework_TestCase
{
    use DataModelTestTrait;
    use GetConfigTrait;
    use SkipTestsIfMissingCredentialsTrait;

    public function setUp()
    {
        parent::setUp();

        $config = self::getConfig();

        $this->model = new MongoDb(
            $config['mongo_url'],
            $config['mongo_database'],
            $config['mongo_collection']
        );
    }
}

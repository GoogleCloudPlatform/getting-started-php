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

class PostgresTest extends \PHPUnit_Framework_TestCase
{
    use DataModelTestTrait;
    use GetConfigTrait;
    use SkipTestsIfMissingCredentialsTrait;

    public function setUp()
    {
        parent::setUp();

        $config = $this->getConfig();

        $postgres_dsn_local = sprintf('pgsql:host=127.0.0.1;port=%s;dbname=%s',
            $config['postgres_port'],
            $config['postgres_database_name']);

        $this->model = new Sql(
            $postgres_dsn_local,
            $config['postgres_user'],
            $config['postgres_password']
        );
    }
}

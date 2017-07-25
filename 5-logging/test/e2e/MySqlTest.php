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

/**
 * Class MySqlTest
 */
class MySqlTest extends E2eTest
{
    protected static function copyAppYaml()
    {
        // set "app-e2e.yaml" for app engine config
        // set cloudsql connection name
        $config = self::getConfig();
        $appYamlPath = __DIR__ . '/../../app-e2e.yaml';
        $appYaml = file_get_contents(__DIR__ . '/../app-e2e.yaml');
        file_put_contents($appYamlPath, str_replace(
            ['# ', 'CLOUDSQL_CONNECTION_NAME'],
            ['', $config['mysql_connection_name']],
            $appYaml
        ));
    }

    protected static function getCustomConfig()
    {
        return ['bookshelf_backend' => 'mysql'];
    }
}

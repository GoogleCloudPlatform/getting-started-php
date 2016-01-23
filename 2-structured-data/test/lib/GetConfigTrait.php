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

use Symfony\Component\Yaml\Yaml;

/**
 * Class GetConfigTrait
 * @package Google\Cloud\Samples\Bookshelf
 *
 * Use this trait to load the project configuration
 */
trait GetConfigTrait
{
    protected function getConfig()
    {
        $config = array(
            'google_client_id' => getenv('GOOGLE_CLIENT_ID'),
            'google_client_secret' => getenv('GOOGLE_CLIENT_SECRET'),
            'google_project_id' => getenv('GOOGLE_PROJECT_ID'),
            'google_storage_bucket' => getenv('GOOGLE_STORAGE_BUCKET'),
            'bookshelf_backend' => 'cloudsql',
            'mysql_dsn' => getenv('MYSQL_DSN'),
            'mysql_user' => getenv('MYSQL_USER'),
            'mysql_password' => getenv('MYSQL_PASSWORD'),
            'mongo_url' => getenv('MONGO_URL'),
            'mongo_namespace' => getenv('MONGO_NAMESPACE'),
        );

        return $config;
    }
}

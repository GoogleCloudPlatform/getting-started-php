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
 * Class SkipTestsIfMissingCredentials
 * @package Google\Cloud\Samples\Bookshelf
 *
 * Use this trait to skip all tests when credentials have not been set in
 * the environment.
 */
trait SkipTestsIfMissingCredentialsTrait
{
    /**
     * @return bool True if we found application credentials in the environment.
     */
    public static function hasCredentials()
    {
        static $hasCredentials = null;
        if ($hasCredentials == null) {
            $path = getenv('GOOGLE_APPLICATION_CREDENTIALS');
            $hasCredentials = $path && file_exists($path) &&
                filesize($path) > 0;
        }
        return $hasCredentials;
    }

    /**
     * Set up the client.
     *
     * @beforeClass
     */
    public static function checkCredentials()
    {
        if (!self::hasCredentials()) {
            self::markTestSkipped('No application credentials were found.');
        }
    }
}

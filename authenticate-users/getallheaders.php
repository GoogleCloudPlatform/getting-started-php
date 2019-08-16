<?php

# Copyright 2019 Google LLC All Rights Reserved.
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

# [START getting_started_auth_getallheaders]
/**
 * Shim for getallheaders in PHP < 7.3 for NGINX.
 */
if (!function_exists('getallheaders')) {
    function getallheaders()
    {
        $headers = [];
        foreach ($_SERVER as $key => $val) {
            if (substr($key, 0, 5) == 'HTTP_') {
                $newKey = str_replace(' ', '-', ucwords(
                    strtolower(str_replace('_', ' ', substr($key, 5)))
                ));
                $headers[$newKey] = $val;
            }
        }
        return $headers;
    }
}
# [END getting_started_auth_getallheaders]

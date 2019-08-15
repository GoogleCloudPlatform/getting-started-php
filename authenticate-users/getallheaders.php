<?php

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

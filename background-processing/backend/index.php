<?php

require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;

(function () {
    $target = getenv('FUNCTION_TARGET', true);
    if ($target === false) {
        throw new \Exception('FUNCTION_TARGET is not set');
    }

    $request = Request::createFromGlobals();
    $message = json_decode($request->getContent(), true);
    if (empty($message['message']['data'])) {
        throw new \Exception('No message received');
    }
    $data = json_decode(base64_decode($message['message']['data']), true);
    if (!$data) {
        throw new \Exception('Error decoding data from message');
    }
    call_user_func_array($target, [$data]);
})();

<?php

namespace Google\Cloud\Samples\Bookshelf\PubSub;

use Google\Auth\HttpHandler\HttpHandlerFactory;
use Google\Cloud\PubSub\Connection\Rest;
use Google\Cloud\PubSub\PubSubClient;
use Google\Cloud\RequestWrapper;
use Google\Cloud\RestTrait;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\CurlMultiHandler;

/**
 * Class AsyncConnection makes async calls to the PubSub API
 */
class AsyncConnection extends Rest
{
    use RestTrait;

    private $handler;

    public function __construct()
    {
        // call parent constructor
        parent::__construct();

        // create the asyncronous curl handler
        $this->handler = new CurlMultiHandler;
        $httpClient = new Client([
            'handler' => HandlerStack::create($this->handler)
        ]);
        // have the request wrapper call guzzle asyncronously
        $this->setRequestWrapper(new RequestWrapper([
            'scopes' => PubSubClient::FULL_CONTROL_SCOPE,
            'httpHandler' =>
                function ($request, $options = []) use ($httpClient) {
                    return $httpClient->sendAsync($request, $options);
                },
            'authHttpHandler' => HttpHandlerFactory::build(),
        ]));
    }

    /**
     * Delivers an asyncronous request to a pubsub subscroption
     *
     * @param array $options
     * @return GuzzleHttp\Promise\Promise
     */
    public function pull(array $options)
    {
        $requestOptions = array_intersect_key($options, [
            'httpOptions' => null,
            'retries' => null,
        ]);

        $request = $this->requestBuilder->build('subscriptions', 'pull', $options);

        return $this->requestWrapper->send(
            $request,
            $requestOptions
        );
    }

    public function tick()
    {
        // advance the curl event loop
        return $this->handler->tick();
    }
}

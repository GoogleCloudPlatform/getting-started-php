<?php

namespace Google\Cloud\Samples\Bookshelf\PubSub;

use Google\Cloud\PubSub\Subscription;
use Google\Cloud\PubSub\Message;
use Psr\Log\LoggerInterface;

/**
 * Class Worker calls the PubSub API and runs a job when work is received.
 */
class Worker
{
    private $callback;
    private $connection;
    private $promise;
    private $subscription;

    public function __construct(Subscription $subscription, $job, LoggerInterface $logger)
    {
        // [START callback]
        $callback = function ($response) use ($job, $subscription, $logger) {
            $ackMessages = [];
            $messages = json_decode($response->getBody(), true);
            if (isset($messages['receivedMessages'])) {
                foreach ($messages['receivedMessages'] as $message) {
                    $pubSubMessage = new Message($message['message'], array('ackId' => $message['ackId']));
                    $attributes = $pubSubMessage->attributes();
                    $logger->info(sprintf('Message received for book ID "%s" ', $attributes['id']));
                    // Do the actual work in the LookupBookDetailsJob class
                    $job->work($attributes['id']);
                    $ackMessages[] = $pubSubMessage;
                }
            }
            // Acknowledge the messsages have been handled
            if (!empty($ackMessages)) {
                $subscription->acknowledgeBatch($ackMessages);
            }
        };
        // [END callback]
        $this->callback = $callback;
        $this->subscription = $subscription;
        $this->connection = new AsyncConnection();
    }

    public function __invoke($timer)
    {
        // advance the event loop for our async call to pubsub
        $this->connection->tick();

        // check the status of the promise and handle completion or error
        if (!$this->promise || 'fulfilled' == $state = $this->promise->getState()) {
            $this->asyncPubsubPull();
        } elseif ('rejected' == $state) {
            // this will throw the exception and stop the event loop
            $this->promise->wait();
        }
    }

    private function asyncPubsubPull()
    {
        $callback = $this->callback;
        // [START promise]
        $promise = $this->connection->pull([
            'maxMessages' => 1000,
            'subscription' => $this->subscription->info()['name'],
        ]);
        $promise->then($callback);
        // [END promise]
        $this->promise = $promise;
    }
}

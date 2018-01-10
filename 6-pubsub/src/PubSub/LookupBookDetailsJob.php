<?php

namespace Google\Cloud\Samples\Bookshelf\PubSub;

use Google\Cloud\Samples\Bookshelf\DataModel\DataModelInterface;
use Google_Client;
use Google_Service_Books;

/**
 * Class LookupBookDetailsJob looks up book details using the Google API PHP
 * Client.
 */
class LookupBookDetailsJob
{
    private $model;
    private $client;

    public function __construct(DataModelInterface $model, Google_Client $client)
    {
        $this->model = $model;
        $this->client = $client;
    }

    public function work($id)
    {
        if ($book = $this->model->read($id)) {
            // [START lookup_books]
            $service = new Google_Service_Books($this->client);
            $options = ['orderBy' => 'relevance'];
            $results = $service->volumes->listVolumes($book['title'], $options);
            // [END lookup_books]
            // [START update_image]
            foreach ($results as $result) {
                $volumeInfo = $result->getVolumeInfo();
                if ($volumeInfo === null) {
                    return false;
                }
                $imageInfo = $volumeInfo->getImageLinks();
                if ($imageInfo === null) {
                    return false;
                }
                if ($thumbnail = $imageInfo->getThumbnail()) {
                    $book['image_url'] = $thumbnail;
                    $this->client->getLogger()->info(sprintf(
                        'Updating book "%s" with thumbnail "%s"',
                        $id, basename($thumbnail)));
                    return $this->model->update($book);
                }
            }
            // [END update_image]
        }

        return false;
    }
}

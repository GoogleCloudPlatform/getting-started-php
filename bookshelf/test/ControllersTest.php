<?php
/*
 * Copyright 2019 Google LLC
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

use Google\Cloud\Firestore\FirestoreClient;
use Google\Cloud\TestUtils\TestTrait;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Test for application controllers
 */
class ControllersTest extends Laravel\Lumen\Testing\TestCase
{
    use TestTrait;

    private static $collection;

    /**
     * Creates the application.
     *
     * @return \Laravel\Lumen\Application
     */
    public function createApplication()
    {
        $app = require __DIR__ . '/../index.php';
        $app['debug'] = true;
        return $app;
    }

    /** @beforeClass */
    public static function instantiateFirestoreCollection()
    {
        self::checkProjectEnvVars();

        // Create a new collection for this tests so we can remove it after
        $collectionName = 'books-' . time() . rand();
        putenv('FIRESTORE_COLLECTION=' . $collectionName);

        // Set the page size to 1 so we can test pagination
        putenv('BOOKSHELF_PAGE_SIZE=1');

        $firestore = new FirestoreClient([
            'projectId' => self::$projectId,
        ]);
        self::$collection = $firestore->collection($collectionName);
    }

    /** @afterClass */
    public static function deleteFirestoreCollection()
    {
        // Delete the collection
        foreach (self::$collection->documents() as $document) {
            printf('Deleting document %s' . PHP_EOL, $document->id());
            $document->reference()->delete();
        }
    }

    public function testIndex()
    {
        $response = $this->call('GET', '/');
        $this->assertEquals(200, $response->getStatusCode());
        $crawler = new Crawler($response->getContent());
        $this->assertEquals(1, $crawler->selectLink('Add book')->count());
    }

    public function testCreateBook()
    {
        $response = $this->call('GET', '/books/add');
        $this->assertEquals(200, $response->getStatusCode());

        // Fill the form and submit it
        $response = $this->call('POST', '/books/add', [
            'title' => 'The Cat in the Hat',
            'author' => 'Dr. Suess',
            'published_date' => '1957-01-01',
            'description' => '',
        ], [], [
            'image' => new UploadedFile(
                __DIR__ . '/../images/moby-dick.jpg',
                'moby-dick.jpg',
                'image/jpg',
                null,
                true
            ),
        ]);
        $this->assertEquals(302, $response->getStatusCode());
        $crawler = new Crawler($response->getContent());
        $redirectUri = $crawler->filter('a')->attr('href');
        $response = $this->call('GET', $redirectUri);

        $this->assertContains(
            'moby-dick.jpg',
            $response->getContent()
        );
    }

    public function testCreateBookWithInvalidFields()
    {
        $response = $this->call('POST', '/books/add', [
            'title' => 'The Cat in the Hat',
            'invalid_field' => 'abc',
        ]);
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertContains('unsupported field: invalid_field', $response->getContent());
    }

    /**
     * @depends testCreateBook
     */
    public function testPagination()
    {
        // Create another book
        $response = $this->call('POST', '/books/add', [
            'title' => 'Treasure Island',
            'author' => 'Robert Louis Stevenson',
            'published_date' => '1883-01-01',
            'description' => '',
            'image_url' => '',
        ]);
        $this->assertEquals(302, $response->getStatusCode());

        // Now go through the pages one by one and confirm we have the books
        $response = $this->call('GET', '/');
        $this->assertEquals(200, $response->getStatusCode());
        $crawler = new Crawler($response->getContent());

        $this->assertEquals(1, $crawler
            ->filter('h4:contains("The Cat in the Hat")')->count());
        $more = $crawler->filter('a:contains("More")');
        $this->assertEquals(1, $more->count());

        $response = $this->call('GET', $more->attr('href'));
        $this->assertEquals(200, $response->getStatusCode());
        $crawler = new Crawler($response->getContent());

        $this->assertEquals(1, $crawler
            ->filter('h4:contains("Treasure Island")')->count());
    }

    /**
     * @depends testCreateBook
     */
    public function testEditBook()
    {
        $response = $this->call('GET', '/');
        $this->assertEquals(200, $response->getStatusCode());
        $crawler = new Crawler($response->getContent());

        // Find the first book and get the edit URL
        $firstBook = $crawler->filter('a:contains("The Cat in the Hat")');
        $this->assertEquals(1, $firstBook->count());

        // Edit the book
        $response = $this->call('POST', $firstBook->attr('href') . '/edit', [
            'title' => 'The Cat in the Hat (edited)',
            'description' => '**New Description**'
        ]);

        $this->assertEquals(302, $response->getStatusCode());
        $crawler = new Crawler($response->getContent());
        $redirectUri = $crawler->filter('a')->attr('href');
        $response = $this->call('GET', $redirectUri);

        $this->assertContains('The Cat in the Hat (edited)', $response->getContent());
        $this->assertContains('**New Description**', $response->getContent());
    }

    public function testLogging()
    {
        $response = $this->call('GET', '/logs');
        $this->assertEquals(302, $response->getStatusCode());

        $response = $this->call('GET', '/errors');
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertContains('This is an intentional exception.', $response->getContent());
    }
}

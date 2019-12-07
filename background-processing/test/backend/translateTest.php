<?php
/**
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     https://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

use Google\Cloud\Firestore\FirestoreClient;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../backend/functions.php';

class translateTest extends TestCase
{
    public function testTranslateString()
    {
        $text = 'Living the crazy life ' . time();
        $data = [
            'text' => $text,
            'language' => 'es',
        ];
        $result = translateString($data);

        $firestore = new FirestoreClient();
        $docRef = $firestore->collection('translations')->document('es:' . base64_encode($text));

        $this->assertTrue($docRef->snapshot()->exists());

        $this->assertEquals($text, $docRef->snapshot()['original']);
        $this->assertEquals('en', $docRef->snapshot()['originalLang']);
        $this->assertEquals('es', $docRef->snapshot()['lang']);
        $this->assertContains('la vida loca', $docRef->snapshot()['translated']);
    }
}

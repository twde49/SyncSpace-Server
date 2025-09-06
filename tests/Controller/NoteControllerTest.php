<?php

namespace App\Tests\Controller;

use App\Tests\AuthenticatedWebTestCase;

class NoteControllerTest extends AuthenticatedWebTestCase
{
    

    public function testSomething(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/notes');

        $this->assertResponseIsSuccessful();
    }
}

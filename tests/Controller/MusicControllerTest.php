<?php

namespace App\Tests\Controller;

use App\Tests\AuthenticatedWebTestCase;

class MusicControllerTest extends AuthenticatedWebTestCase
{
    

    public function testSomething(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/music/playlists');

        $this->assertResponseIsSuccessful();
    }
}

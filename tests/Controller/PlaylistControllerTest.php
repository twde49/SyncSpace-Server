<?php

namespace App\Tests\Controller;

use App\Tests\AuthenticatedWebTestCase;

class PlaylistControllerTest extends AuthenticatedWebTestCase
{
    

    public function testSomething(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/music/playlist/index');

        $this->assertResponseIsSuccessful();
    }
}

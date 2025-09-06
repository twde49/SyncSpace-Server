<?php

namespace App\Tests\Controller;

use App\Tests\AuthenticatedWebTestCase;

class YoutubeSearchControllerTest extends AuthenticatedWebTestCase
{
    public function testSomething(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/music/youtube/search?query=imagineDragons');

        $this->assertResponseIsSuccessful();
    }
}

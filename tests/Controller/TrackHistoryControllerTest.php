<?php

namespace App\Tests\Controller;

use App\Tests\AuthenticatedWebTestCase;

class TrackHistoryControllerTest extends AuthenticatedWebTestCase
{
    public function testSomething(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/music/history');

        $this->assertResponseIsSuccessful();
    }
}

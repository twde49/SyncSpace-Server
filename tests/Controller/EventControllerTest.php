<?php

namespace App\Tests\Controller;

use App\Tests\AuthenticatedWebTestCase;

class EventControllerTest extends AuthenticatedWebTestCase
{
    

    public function testSomething(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/events/all');

        $this->assertResponseIsSuccessful();
    }
}

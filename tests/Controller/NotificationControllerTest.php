<?php

namespace App\Tests\Controller;

use App\Tests\AuthenticatedWebTestCase;

class NotificationControllerTest extends AuthenticatedWebTestCase
{
    

    public function testSomething(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/notifications/all');

        $this->assertResponseIsSuccessful();
    }
}

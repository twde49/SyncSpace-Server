<?php

namespace App\Tests\Controller;

use App\Tests\AuthenticatedWebTestCase;

class ChatUserModuleControllerTest extends AuthenticatedWebTestCase
{
    

    public function testSomething(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/conversations');

        $this->assertResponseIsSuccessful();
    }
}

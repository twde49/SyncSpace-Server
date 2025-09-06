<?php

namespace App\Tests\Controller;

use App\Tests\AuthenticatedWebTestCase;

class ContactControllerTest extends AuthenticatedWebTestCase
{
    

    public function testSomething(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('POST', '/api/contact/', [], [], [], json_encode([
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'message' => 'Hello'
        ]));

        $this->assertResponseIsSuccessful();
    }
}

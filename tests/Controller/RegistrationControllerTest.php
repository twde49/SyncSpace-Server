<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegistrationControllerTest extends WebTestCase
{
    public function testSomething(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/register', [], [], [], json_encode([
            'email' => 'test@example.com',
            'password' => 'password',
            'firstName' => 'John',
            'lastName' => 'Doe',
        ]));

        $this->assertResponseIsSuccessful();
    }
}

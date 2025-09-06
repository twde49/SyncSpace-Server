<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MailerControllerTest extends WebTestCase
{
    public function testSomething(): void
    {
        $client = static::createClient();
        $client->request('POST', '/email', [], [], [], json_encode([
            'to' => 'test@example.com',
            'subject' => 'Test Subject',
            'body' => 'Test Body',
        ]));

        $this->assertResponseIsSuccessful();
    }
}

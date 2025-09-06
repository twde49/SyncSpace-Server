<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Tests\AuthenticatedWebTestCase;

class UserSettingsControllerTest extends AuthenticatedWebTestCase
{
    public function testSomething(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/settings/current-track');

        $this->assertResponseIsSuccessful();
    }
}

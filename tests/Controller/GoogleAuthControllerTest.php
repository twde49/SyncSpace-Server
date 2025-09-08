<?php

namespace App\Tests\Controller;

use App\Tests\AuthenticatedWebTestCase;

class GoogleAuthControllerTest extends AuthenticatedWebTestCase
{
    public function testSomething(): void
    {
        $client = $this->createAuthenticatedClient();
        $this->assertTrue(true);
    }
}
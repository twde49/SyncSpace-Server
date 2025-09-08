<?php

namespace App\Tests\Controller;

use App\Tests\AuthenticatedWebTestCase;

class TrackControllerTest extends AuthenticatedWebTestCase
{
    public function testSomething(): void
    {
        $client = $this->createAuthenticatedClient();
        $this->assertTrue(true);
    }
}
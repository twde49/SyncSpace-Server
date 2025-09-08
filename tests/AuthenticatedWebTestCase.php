<?php

declare(strict_types=1);

namespace App\Tests;

use App\Entity\User;
use App\Entity\UserSettings;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthenticatedWebTestCase extends WebTestCase
{
    protected ?KernelBrowser $client = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->runCommand('doctrine:database:drop --env=test --force --if-exists');
        $this->runCommand('doctrine:database:create --env=test --if-not-exists');
        $this->runCommand('doctrine:migrations:migrate --env=test --no-interaction');
    }

    protected function runCommand(string $command): void
    {
        $command = sprintf('php %s/../bin/console %s', __DIR__, $command);

        $process = \Symfony\Component\Process\Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getOutput().$process->getErrorOutput());
        }
    }

    protected function createAuthenticatedClient(string $password = 'password'): KernelBrowser
    {
        $this->client = static::createClient();
        $container = $this->client->getContainer();

        /** @var EntityManagerInterface $em */
        $em = $container->get('doctrine.orm.entity_manager');
        /** @var UserPasswordHasherInterface $passwordHasher */
        $passwordHasher = $container->get('security.password_hasher');

        $email = uniqid().'@test.com';
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName('test');
        $user->setLastName('test');
        $user->setRoles(['ROLE_USER']);
        $user->setIsValidated(true);
        $userSettings = new UserSettings();
        $user->setUserSettings($userSettings);
        $em->persist($userSettings);
        $user->setPassword($passwordHasher->hashPassword($user, $password));
        $em->persist($user);
        $em->flush();

        $this->client->request(
            'POST',
            '/api/login_check',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'username' => $email,
                'password' => $password,
            ])
        );

        $response = $this->client->getResponse();
        $this->assertTrue($response->isSuccessful(), 'Login request failed: ' . $response->getContent());
        
        $content = $response->getContent();
        $this->assertJson($content);
        $data = json_decode($content, true);
        $this->assertArrayHasKey('token', $data, 'Login response does not contain a token: ' . $content);
        $this->client->setServerParameter('HTTP_Authorization', sprintf('Bearer %s', $data['token']));

        return $this->client;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->runCommand('doctrine:database:drop --env=test --force --if-exists');
        $this->client = null;
    }
}
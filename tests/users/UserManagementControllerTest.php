<?php

namespace App\Tests\users;

use Faker\Factory;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserManagementControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private UserRepository $userRepository;
    private EntityManagerInterface $manager;
    private Factory $faker;

    private array $userTestData;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $container = static::getContainer();

        /** @var EntityManager $em */
        $em = $container->get("doctrine")->getManager();
        $this->userRepository = $container->get(UserRepository::class);

        $this->faker = Factory::create();
        $this->userTestData = [
            "email" => $this->faker->email(),
            "password" => $this->faker->password()
        ];

        $em->flush();
    }

    public function testRegister(): void
    {
        $this->client->request("GET", "/register");
        self::assertResponseIsSuccessful();
        self::assertPageTitleContains("Register");

        $this->client->submitForm("Register", [
            "registration_form[email]" => $this->userTestData["email"],
            "registration_form[plainPassword]" => $this->userTestData["password"],
            "registration_form[agreeTerms]" => true,
        ]);

        self::assertNotNull($this->userRepository->findBy(['email'=>$this->userTestData["email"]]));
    }


    public function testLogin(): void
    {
        $this->client->request("GET", "/login");
        self::assertResponseIsSuccessful();
        self::assertPageTitleContains("Log in");

        $this->client->submitForm("Sign in", [
            "email" => $this->userTestData["email"],
            "password" => $this->userTestData["password"],
        ]);
        self::assertResponseRedirects();
    }

    public function testLogout(): void
    {
        $this->client->request("GET", "/logout");
        self::assertResponseRedirects();
    }


    public function testLoginApi(): void
    {
        $this->client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(
            ['username' => $this->userTestData["email"], 'password' => $this->userTestData["password"]]
        ));

        dd($this->client->getResponse(),$this->userTestData["email"],$this->userTestData["password"]);
        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode(), 'Login check should return HTTP 200');

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('token', $data, 'Response should contain a JWT token');
    }
}

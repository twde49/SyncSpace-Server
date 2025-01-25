<?php

namespace App\Tests\users;

use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
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

    private array $userTestData = [];

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    protected function setUp(): void
    {
        $this->client = static::createClient();

        $container = static::getContainer();

        /** @var EntityManager $em */
        $em = $container->get("doctrine")->getManager();
        $this->userRepository = $container->get(UserRepository::class);

        $faker = Factory::create();
        if ($this->userTestData == []){
            $this->userTestData = [
                "email" => $faker->email(),
                "password" => $faker->password(),
                "first_name" => $faker->firstName(),
                "last_name" => $faker->lastName(),
            ];
        }


        $em->flush();
    }

    public function testRegisterApi(): void
    {
        dump("1",$this->userTestData);
        $this->client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(
            [
                'email' => $this->userTestData["email"],
                'password' => $this->userTestData["password"],
                'firstName' => $this->userTestData["first_name"],
                'lastName' => $this->userTestData["last_name"]
            ]
        ));
        self::assertResponseIsSuccessful();


        self::assertNotNull($this->userRepository->findBy(['email'=>$this->userTestData["email"]]));
    }


    public function testLoginApi(): void
    {
        dump("2",$this->userTestData);
        $this->client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(
            ['username' => $this->userTestData["email"], 'password' => $this->userTestData["password"]]
        ));

        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode(), 'Login check should return HTTP 200');

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('token', $data, 'Response should contain a JWT token');
    }
}

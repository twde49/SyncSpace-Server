<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Mercure\HubInterface;

class MercureController extends AbstractController
{
    private HubInterface $hub;

    public function __construct(HubInterface $hub)
    {
        $this->hub = $hub;
    }

    #[Route('/send-message', name: 'send_message', methods: ['POST'])]
    public function sendMessage(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $message = $data['message'] ?? '';

        // Publish the message
        $update = new Update(
            'http://localhost:4000/messages',
            json_encode(['message' => $message])
        );

        // Use the HubInterface to publish the update
        $this->hub->publish($update);

        return new JsonResponse(['status' => 'Message sent']);
    }
}

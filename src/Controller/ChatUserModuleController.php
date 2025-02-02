<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Repository\ConversationRepository;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use App\Service\ConversationService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;

#[Route("api")]
class ChatUserModuleController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private ConversationService $conversationService;

    public function __construct(EntityManagerInterface $entityManager, ConversationService $conversationService)
    {
        $this->entityManager = $entityManager;
        $this->conversationService = $conversationService;
    }

    /**
     * @throws GuzzleException
     */
    #[
        Route(
            "/conversation/new",
            name: "create_conversation",
            methods: ["POST"]
        )
    ]
    public function createConversation(
        Request                $request,
        UserRepository         $userRepository,
        ConversationRepository $conversationRepository,
        SerializerInterface $serializer
    ): Response
    {
        $data = $request->toArray();
        $userIds = $data["userIds"] ?? [];
        $conversation = new Conversation();
        $conversation->setCreatedBy($this->getUser());
        $conversation->addUser($this->getUser());
        $conversation->setName($data["name"] ?? null);
        foreach ($userIds as $userId) {
            $user = $userRepository->find($userId);
            if (!$user) {
                return $this->json(
                    [
                        "error" =>
                            "the user with id " .
                            $userId .
                            " seem to not exist",
                    ],
                    404
                );
            }
            $conversation->addUser($user);
        }
        switch (count($conversation->getUsers())) {
            case 1:
                return $this->json(
                    ["error" => "conversation must have at least 2 users"],
                    400
                );
            case 2:
                $conversation->setType("private");
                break;
            default:
                $conversation->setType("group");
                break;
        }
        $conversation->setAvatar($data["avatar"] ?? null);

        $this->entityManager->persist($conversation);
        $this->entityManager->flush();

        $client = new Client();
        $allConversations = $conversationRepository->findAll();
        $context = ['groups' => 'conversation:read'];
        $normalizedConversations = $serializer->normalize($allConversations, null, $context);

        $client->post('http://localhost:6969/webhook/update-conversations', [
            'json' => ['conversations' => $normalizedConversations]
        ]);


        return $this->json(
            [
                "message" => "Your new conversation has been created",
                "content" => $conversation
            ],
            201,
            [],
            ['groups' => 'conversation:read']
        );
    }

    #[Route("/conversation/{id}", name: "show_conversation", methods: ["GET"])]
    public function showConversation(?Conversation $conversation): Response
    {
        if (!$conversation) {
            return $this->json(["error" => "Conversation not found"], 404);
        }
        return $this->json(
            $conversation,
            200,
            [],
            ["groups" => "conversation:read"]
        );
    }

    #[Route("/conversations", name: "show_all_conversations", methods: ["GET"])]
    public function showAllConversations(
        UserRepository $userRepository
    ): Response
    {
        $user = $userRepository->find($this->getUser());
        $conversations = $user->getConversations();
        foreach ($conversations as $conversation) {
            $this->conversationService->setLatestActiveUser($conversation);
            $this->conversationService->setLatestMessage($conversation);
            $this->entityManager->persist($conversation);
        }
        $this->entityManager->flush();
        return $this->json(
            $conversations,
            200,
            [],
            ["groups" => "conversation:read"]
        );
    }

    #[
        Route(
            "/conversation/remove/{id}",
            name: "remove_conversation",
            methods: ["DELETE"]
        )
    ]
    public function deleteConversation(Conversation $conversation): Response
    {
        $this->entityManager->remove($conversation);
        $this->entityManager->flush();
        return $this->json(["message" => "Conversation deleted"], 200);
    }

    #[
        Route(
            "/conversation/edit/{id}",
            name: "edit_conversation",
            methods: ["PUT"]
        )
    ]
    public function editConversation(?Conversation $conversation): Response
    {
        $request = Request::createFromGlobals();
        $data = $request->toArray();

        if (!$conversation) {
            return $this->json(["error" => "Conversation not found"], 404);
        }

        if (isset($data["name"])) {
            $conversation->setName($data["name"]);
        }

        if (isset($data["avatar"])) {
            $conversation->setAvatar($data["avatar"]);
        }

        $this->entityManager->persist($conversation);
        $this->entityManager->flush();

        return $this->json(["message" => "Conversation updated"], 200);
    }

    /**
     * @throws GuzzleException
     */
    #[
        Route(
            "/conversation/{id}/message/new",
            name: "send_message_post",
            methods: ["POST"]
        )
    ]
    public function sendMessage(?Conversation $conversation, Request $request, MessageRepository $messageRepository, SerializerInterface $serializer): Response
    {
        $data = $request->toArray();
        if (!$conversation) {
            return $this->json(["error" => "Conversation not found"], 404);
        }

        if (!$conversation->getUsers()->contains($this->getUser())) {
            return $this->json(["error" => "You're not part of this conversation"], 403);
        }

        $message = new Message();
        $message->setContent($data["content"]);
        $message->setType($data["type"] ?? "text");
        $message->setSender($this->getUser());
        $message->setConversation($conversation);
        $message->setAttachment($data["attachment"]);
        $conversation->setLastActivity(new \DateTimeImmutable());
        $this->entityManager->persist($message);
        $conversation->setLastMessage($message);
        $this->entityManager->flush();

        $client = new Client();
        $messages = $messageRepository->findBy(['conversation' => $conversation]);
        $context = ['groups' => 'conversation:read'];
        $jsonMessages = $serializer->serialize($messages, 'json', $context);

        $client->post('http://localhost:6969/webhook/update-messages', [
            'json' => ['messages' => $jsonMessages]]);


        $response = [
            "detail" => "Your message has been sent",
            "Status" => 201,
            "content" => $message->getContent(),
        ];
        return $this->json($response);
    }

    #[
        Route(
            "/conversation/message/remove/{id}",
            name: "remove_message",
            methods: ["DELETE"]
        )
    ]
    public function deleteMessage(?Message $message): Response
    {
        if (!$message) {
            return $this->json(["error" => "Message not found"], 404);
        }
        $this->entityManager->remove($message);
        $this->entityManager->flush();
        return $this->json(["message" => "Message deleted"], 200);
    }

    #[
        Route(
            "/conversation/message/edit/{id}",
            name: "edit_message",
            methods: ["PUT"]
        )
    ]
    public function editMessage(?Message $message, Request $request): Response
    {
        if (!$message) {
            return $this->json(["error" => "Message not found"], 404);
        }
        $data = $request->toArray();
        if (isset($data["content"])) {
            $message->setContent($data["content"]);
        }
        if (isset($data["attachment"])) {
            $message->setAttachment($data["attachment"]);
        }
        $this->entityManager->persist($message);
        $this->entityManager->flush();
        return $this->json(["message" => "Message updated"], 200);
    }
}

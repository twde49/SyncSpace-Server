<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\User;
use App\Repository\ConversationRepository;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use App\Service\ConversationService;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('api')]
class ChatUserModuleController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private ParameterBagInterface $params;
    
    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $params)
    {
        $this->params = $params;
        $this->entityManager = $entityManager;
    }

    /**
     * @throws GuzzleException
     */
    #[
        Route(
            '/conversation/new',
            name: 'create_conversation',
            methods: ['POST']
        )
    ]
    public function createConversation(
        Request $request,
        UserRepository $userRepository,
        ConversationRepository $conversationRepository,
        NormalizerInterface $normalizer,
        NotificationService $notificationService,
    ): Response {
        $data = $request->toArray();
        $userIds = $data['userIds'] ?? [];
        /* @var User $user */
        $user = $this->getUser();
        
        $conversationRepository->checkIfAlreadyExists($userIds, $user->getId());
        
        $conversation = new Conversation();
        $conversation->setCreatedBy($this->getUser());
        $conversation->addUser($this->getUser());
        $conversation->setName($data['name'] ?? null);
        $conversation->setLastActiveUser($this->getUser());
        foreach ($userIds as $userId) {
            $user = $userRepository->find($userId);
            if (!$user) {
                return $this->json(
                    [
                        'error' => 'the user with id '.
                            $userId.
                            ' seem to not exist',
                    ],
                    404
                );
            }
            $conversation->addUser($user);
        }
        switch (count($conversation->getUsers())) {
            case 1:
                return $this->json(
                    ['error' => 'conversation must have at least 2 users'],
                    400
                );
            case 2:
                $conversation->setType('private');
                break;
            default:
                $conversation->setType('group');
                break;
        }
        $conversation->setAvatar($data['avatar'] ?? null);

        $this->entityManager->persist($conversation);
        $this->entityManager->flush();

        $client = new Client();
        $allConversations = $conversationRepository->findAll();
        $context = ['groups' => 'conversation:read'];
        $normalizedConversations = $normalizer->normalize(
            $allConversations,
            null,
            $context
        );

        $websocketbaseurl = $this->getParameter('websocket_url');
        $client->post($websocketbaseurl.'/webhook/refreshConversations');

        foreach ($conversation->getUsers() as $user) {
            if ($user !== $this->getUser()) {
                $notificationService->sendNotification(
                    'Vous avez été ajouté à une nouvelle conversation',
                    'Nouvelle conversation avec '.
                        implode(
                            ', ',
                            array_map(function ($user) {
                                return $user->getFirstName().
                                    ' '.
                                    $user->getLastName();
                            }, $conversation->getUsers()->toArray())
                        ),
                    $user
                );
            }
        }

        return $this->json(
            [
                'message' => 'Your new conversation has been created',
                'content' => $conversation,
            ],
            201,
            [],
            ['groups' => 'conversation:read']
        );
    }

    #[Route('/conversation/{id}', name: 'show_conversation', methods: ['GET'])]
    public function showConversation(?Conversation $conversation): Response
    {
        if (!$conversation) {
            return $this->json(['error' => 'Conversation not found'], 404);
        }

        return $this->json(
            $conversation,
            200,
            [],
            ['groups' => 'conversation:read']
        );
    }

    #[Route('/conversations', name: 'show_all_conversations', methods: ['GET'])]
    public function showAllConversations(
        UserRepository $userRepository,
        ConversationService $conversationService,
    ): Response {
        $user = $userRepository->find($this->getUser());
        $conversations = $user->getConversations();
        foreach ($conversations as $conversation) {
            $conversationService->setLatestActiveUser($conversation);
            $conversationService->setLatestMessage($conversation);
            $this->entityManager->persist($conversation);
        }
        $conversations = array_reverse($conversations->toArray());
        $this->entityManager->flush();

        return $this->json(
            $conversations,
            200,
            [],
            ['groups' => 'conversation:read']
        );
    }

    #[
        Route(
            '/conversation/remove/{id}',
            name: 'remove_conversation',
            methods: ['DELETE']
        )
    ]
    public function deleteConversation(Conversation $conversation): Response
    {
        $conversation->setLastMessage(null);
        $this->entityManager->flush();

        foreach ($conversation->getMessages() as $message) {
            $this->entityManager->remove($message);
        }
        $this->entityManager->flush();

        $this->entityManager->remove($conversation);
        $this->entityManager->flush();

        $client = new Client();
        $client->request('POST', $this->params->get('websocket_url'). '/webhook/refreshConversations');

        return $this->json(['message' => 'Conversation deleted'], 200);
    }

    #[
        Route(
            '/conversation/edit/{id}',
            name: 'edit_conversation',
            methods: ['PUT']
        )
    ]
    public function editConversation(?Conversation $conversation): Response
    {
        $request = Request::createFromGlobals();
        $data = $request->toArray();

        if (!$conversation) {
            return $this->json(['error' => 'Conversation not found'], 404);
        }

        if (isset($data['name'])) {
            $conversation->setName($data['name']);
        }

        if (isset($data['avatar'])) {
            $conversation->setAvatar($data['avatar']);
        }

        $this->entityManager->persist($conversation);
        $this->entityManager->flush();

        return $this->json(['message' => 'Conversation updated'], 200);
    }

    /**
     * @throws GuzzleException
     */
    #[
        Route(
            '/conversation/{id}/message/new',
            name: 'send_message_post',
            methods: ['POST']
        )
    ]
    public function sendMessage(
        ?Conversation $conversation,
        Request $request,
        MessageRepository $messageRepository,
        SerializerInterface $serializer,
        NotificationService $notificationService,
    ): Response {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $data = $request->toArray();
        if (!$conversation) {
            return $this->json(['error' => 'Conversation not found'], 404);
        }

        if (!$conversation->getUsers()->contains($this->getUser())) {
            return $this->json(
                ['error' => "You're not part of this conversation"],
                403
            );
        }

        $message = new Message();
        $message->setContent($data['content']);
        $message->setType($data['type'] ?? 'text');
        $message->setSender($this->getUser());
        $message->setConversation($conversation);
        $message->setAttachment($data['attachment']);
        $conversation->setLastActivity(new \DateTimeImmutable());
        $this->entityManager->persist($message);
        $conversation->setLastMessage($message);
        $this->entityManager->flush();

        $client = new Client();
        
        $context = ['groups' => 'conversation:read'];
        $jsonMessage = $serializer->serialize($message, 'json', $context);
        
        $client->post($this->params->get('websocket_url'). '/webhook/newMessage', [
            'json' => ['message' => $jsonMessage, 'conversationId' => $conversation->getId()],
        ]);
        
        $client->post($this->params->get('websocket_url'). '/webhook/refreshConversations');

        foreach ($conversation->getUsers() as $user) {
            if ($user !== $currentUser) {
                $notificationService->sendNotification(
                    'Nouveau message de '.
                    $currentUser->getFirstName().
                    ' '.
                    $currentUser->getLastName(),
                    $message->getContent(),
                    $user
                );
            }
        }

        $response = [
            'detail' => 'Your message has been sent',
            'Status' => 201,
            'content' => $message->getContent(),
        ];

        return $this->json($response);
    }

    #[
        Route(
            '/conversation/message/remove/{id}',
            name: 'remove_message',
            methods: ['DELETE']
        )
    ]
    public function deleteMessage(?Message $message): Response
    {
        if (!$message) {
            return $this->json(['error' => 'Message not found'], 404);
        }

        if ($message->getSender() !== $this->getUser()) {
            return $this->json(
                ['error' => 'You are not authorized to delete this message'],
                403
            );
        }

        $conversation = $message->getConversation();

        if ($conversation->getLastMessage() === $message) {
            $conversation->setLastMessage(null);
            $this->entityManager->persist($conversation);
            $this->entityManager->flush();
        }

        $conversation->removeMessage($message);
        $this->entityManager->persist($conversation);
        $this->entityManager->flush();

        $this->entityManager->remove($message);
        $this->entityManager->flush();

        return $this->json(['message' => 'Message deleted'], 200);
    }

    #[
        Route(
            '/conversation/message/edit/{id}',
            name: 'edit_message',
            methods: ['PUT']
        )
    ]
    public function editMessage(?Message $message, Request $request, MessageRepository $messageRepository, SerializerInterface $serializer): Response
    {
        if (!$message) {
            return $this->json(['error' => 'Message not found'], 404);
        }
        $data = $request->toArray();
        if (isset($data['content'])) {
            $message->setContent($data['content']);
        }
        if (isset($data['attachment'])) {
            $message->setAttachment($data['attachment']);
        }
        $this->entityManager->persist($message);
        $this->entityManager->flush();

        $client = new Client();
        $messages = $messageRepository->findBy([
            'conversation' => $message->getConversation(),
        ]);
        $context = ['groups' => 'conversation:read'];
        $jsonMessages = $serializer->serialize($messages, 'json', $context);

        $client->post($this->params->get('websocket_url'). '/webhook/update-messages', [
            'json' => ['messages' => $jsonMessages],
        ]);

        $client->post($this->params->get('websocket_url'). '/webhook/refreshConversations');

        return $this->json(['message' => 'Message updated'], 200);
    }

    #[
        Route(
            '/conversation/user/search',
            name: 'search_user',
            methods: ['POST']
        )
    ]
    public function searchUser(Request $request): Response
    {
        $data = $request->toArray();
        $query = $this->entityManager
            ->getRepository(User::class)
            ->createQueryBuilder('u');
        $query->where('LOWER(u.firstName) LIKE LOWER(:firstName)');
        $query->setParameter(
            'firstName',
            '%'.strtolower($data['username']).'%'
        );
        $users = $query->getQuery()->getResult();
        $searchedResult = array_values($users);

        return $this->json($searchedResult, 200, [], ['groups' => 'user:read']);
    }

    #[Route('/user/setOnline/{email}', methods: ['PUT'])]
    public function setOnline(string $email, UserRepository $userRepository): Response
    {
        $user = $userRepository->findOneBy(['email' => $email]);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }
        $user->setOnline(true);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json(['message' => 'User set online'], 200);
    }

    #[Route('/user/setOffline/{email}', methods: ['PUT'])]
    public function setOffline(string $email, UserRepository $userRepository): Response
    {
        $user = $userRepository->findOneBy(['email' => $email]);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }
        $user->setOnline(false);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json(['message' => 'User set offline'], 200);
    }
}

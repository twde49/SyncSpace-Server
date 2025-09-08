<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\Note;
use App\Entity\User;
use App\Repository\NoteRepository;
use App\Repository\UserRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('api/note')]
class NoteController extends AbstractController
{
    public function __construct(private readonly ParameterBagInterface $params)
    {
    }

    /**
     * Returns a list of notes for the current user.
     *
     * @param NoteRepository $repository The repository used to fetch notes from the database
     *
     * @return Response JSON response containing the list of notes for the current user
     */
    #[Route('s', name: 'index_note', methods: 'GET')]
    public function indexNotes(NoteRepository $repository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json($repository->findNotesAscOrderByUser($user), Response::HTTP_OK, [], ['groups' => ['note:read', 'conversation:read']]);
    }

    /**
     * Returns a list of notes shared with the current user.
     */
    /**
     * Returns a list of notes shared with the current user.
     *
     * @param NoteRepository $repository The repository used to fetch notes from the database
     *
     * @return Response JSON response containing the list of shared notes
     */
    #[Route('s/shared', name: 'shared_index_note', methods: 'GET')]
    public function indexSharedNotes(NoteRepository $repository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json($user->getSharedNotes(), Response::HTTP_OK, [], ['groups' => ['note:read', 'conversation:read']]);
    }

    /**
     * Creates or updates a note for the current user.
     *
     * @param Note|null              $note       The note to update if it exists, or null for creating a new note
     * @param EntityManagerInterface $manager    The entity manager used to persist and manage the note
     * @param Request                $request    The HTTP request containing the note data
     * @param SerializerInterface    $serializer The serializer used to deserialize the note data from JSON format
     *
     * @return Response JSON response containing the newly created or updated note data
     */
    #[Route('/save/{id}', name: 'update_note', methods: ['PUT'])]
    #[Route('/save', name: 'save_note', methods: ['POST'])]
    public function saveNote(
        ?Note $note,
        EntityManagerInterface $manager,
        Request $request,
        SerializerInterface $serializer,
    ): Response {
        if ($note) {
            $updatedNote = $serializer->deserialize(
                $request->getContent(),
                Note::class,
                'json',
                ['object_to_populate' => $note]
            );
            $updatedNote->setUpdatedAt(new \DateTimeImmutable());
            $manager->persist($updatedNote);
            $manager->flush();

            return $this->json($updatedNote, Response::HTTP_OK, [], ['groups' => ['note:read', 'conversation:read']]);
        } else {
            $newNote = $serializer->deserialize(
                $request->getContent(),
                Note::class,
                'json'
            );
            $newNote->setAuthor($this->getUser());

            $manager->persist($newNote);
            $manager->flush();

            return $this->json($newNote, Response::HTTP_OK, [], ['groups' => ['note:read', 'conversation:read']]);
        }
    }

    /**
     * Removes a note by its ID.
     *
     * @param Note|null              $note    The note to be removed, or null if no note exists with the given ID
     * @param EntityManagerInterface $manager The entity manager responsible for removing the note from the database
     *
     * @return Response JSON response indicating whether the note was removed successfully or not found
     */
    #[Route('/remove/{id}', name: 'remove_note', methods: 'DELETE')]
    public function removeNote(?Note $note, EntityManagerInterface $manager): Response
    {
        if (!$note) {
            return $this->json('No note found with this id', Response::HTTP_NOT_FOUND);
        }
        $manager->remove($note);
        $manager->flush();

        return $this->json('Note have been deleted');
    }

    /**
     * Shares a note with specified users.
     */
    /**
     * Shares a note with specified users.
     *
     * @param Note                   $note           The note to share
     * @param Request                $request        The HTTP request containing user IDs
     * @param UserRepository         $userRepository The user repository
     * @param EntityManagerInterface $manager        The entity manager
     *
     * @return Response JSON response containing the shared note
     */
    #[Route('/share/{id}', name: 'share_note', methods: 'POST')]
    public function shareNote(Note $note, Request $request, UserRepository $userRepository, EntityManagerInterface $manager): Response
    {
        $data = $request->toArray();
        $userIds = $data['userIds'] ?? [];

        foreach ($userIds as $userId) {
            $user = $userRepository->find($userId);
            if ($user) {
                $note->addSharedWith($user);
            }
        }
        $manager->persist($note);
        $manager->flush();

        return $this->json($note, Response::HTTP_OK, [], ['groups' => ['note:read', 'conversation:read']]);
    }

    /**
     * Creates a new shared note in a conversation.
     */
    /**
     * Creates a new shared note in a conversation.
     *
     * @param Conversation           $conversation        The conversation to associate the note with
     * @param Request                $request             The HTTP request containing note data and user IDs
     * @param UserRepository         $userRepository      The user repository
     * @param EntityManagerInterface $manager             The entity manager
     * @param SerializerInterface    $serializer          The serializer
     * @param NotificationService    $notificationService The notification service
     *
     * @return Response JSON response containing the created shared note
     */
    #[Route('/new/share/{id}', name: 'create_shared_note', methods: 'POST')]
    public function createNewSharedNote(Conversation $conversation, Request $request, UserRepository $userRepository, EntityManagerInterface $manager, SerializerInterface $serializer, NotificationService $notificationService): Response
    {
        $data = $request->toArray();
        $title = $data['title'] ?? '';
        $userIds = $data['userIds'] ?? [];

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $note = new Note();
        $note->setTitle($title);
        $note->setAuthor($currentUser);
        $note->setContent('');

        $message = new Message();
        $message->setSender($currentUser);
        $message->setContent($title);
        $message->setSentAt(new \DateTimeImmutable());
        $message->setConversation($conversation);
        $message->setType('note');

        foreach ($userIds as $userId) {
            $user = $userRepository->find($userId);
            if ($user !== $currentUser) {
                $note->addSharedWith($user);
            }
        }

        $manager->persist($note);
        $manager->persist($message);
        $manager->flush();

        $client = new Client();

        $context = ['groups' => 'conversation:read'];
        $jsonMessage = $serializer->serialize($message, 'json', $context);

        $client->post($this->params->get('websocket_url').'/webhook/newMessage', [
            'json' => ['message' => $jsonMessage, 'conversationId' => $conversation->getId()],
        ]);

        $client->post($this->params->get('websocket_url').'/webhook/refreshConversations');

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

        return $this->json($note, Response::HTTP_OK, [], ['groups' => ['note:read', 'conversation:read']]);
    }
}

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
    private ParameterBagInterface $params;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }

    /**
     * Retrieves a list of notes associated with the current user, sorted in ascending order.
     *
     * Route: GET /s (name: "index_note")
     *
     * This method fetches notes belonging to the authenticated user from the database
     * and returns them as a JSON response. The list is sorted in ascending order.
     *
     * @param NoteRepository $repository the repository used to fetch notes from the database
     *
     * @return Response a JSON response containing the list of notes for the current user,
     *                  using serialization groups `note:read` and `conversation:read`,
     *                  along with an HTTP 200 status code
     */
    #[Route('s', name: 'index_note', methods: 'GET')]
    public function indexNotes(NoteRepository $repository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json($repository->findNotesAscOrderByUser($user), Response::HTTP_OK, [], ['groups' => ['note:read', 'conversation:read']]);
    }

    #[Route('s/shared', name: 'shared_index_note', methods: 'GET')]
    public function indexSharedNotes(NoteRepository $repository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json($user->getSharedNotes(), Response::HTTP_OK, [], ['groups' => ['note:read', 'conversation:read']]);
    }

    /**
     * Saves or updates a note based on the request provided.
     *
     * If a note object is provided as a parameter, the method updates the note
     * with the data from the request. Otherwise, it creates a new note and associates
     * it with the current user.
     *
     * Routes:
     * - PUT /save/{id} (name: "update_note"): Updates an existing note with the given ID.
     * - POST /save (name: "save_note"): Creates a new note.
     *
     * @param Note|null              $note       the note to update if it exists, or null for creating a new note
     * @param EntityManagerInterface $manager    the entity manager used to persist and manage the note
     * @param Request                $request    the HTTP request containing the note data
     * @param SerializerInterface    $serializer the serializer used to deserialize the note data from JSON format
     *
     * @return Response a JSON response containing the newly created or updated note data and HTTP status code
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
     * Deletes a note based on the provided note entity.
     *
     * This method removes a note from the database if it exists. If a note with the provided ID
     * is not found, it returns a JSON response with an HTTP 404 status code.
     *
     * Route: DELETE /remove/{id} (name: "remove_note")
     *
     * @param Note|null              $note    the note to be removed, or null if no note exists with the given ID
     * @param EntityManagerInterface $manager the entity manager responsible for removing the note from the database
     *
     * @return Response a JSON response indicating whether the note was removed successfully
     *                  or no note was found with the given ID
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

<?php

namespace App\Controller;

use App\Entity\Note;
use App\Entity\User;
use App\Repository\NoteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('api/note')]
class NoteController extends AbstractController
{
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
}

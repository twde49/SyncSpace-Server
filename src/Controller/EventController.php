<?php

namespace App\Controller;

use App\Entity\Event;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\EventRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/events')]
class EventController extends AbstractController
{
    #[Route('/all', name: 'app_event_index', methods: ['GET'])]
    public function index(EventRepository $eventRepository): Response
    {
        $events = $eventRepository->findAll();
        $concernedEvents = [];
        foreach($events as $event) {
            if ($event->getOrganizer() === $this->getUser() || $event->getParticipants()->contains($this->getUser())) {
                $concernedEvents[] = $event;
            }
        }
        
        return $this->json($concernedEvents, 200,[],['groups'=>'event:read']);
    }
    
    #[Route('/create', name: 'app_event_create', methods: ['POST'])]
    public function createEvent(Request $request, EntityManagerInterface $manager, SerializerInterface $serializer,UserRepository $userRepository):Response
    {        
        $data = json_decode($request->getContent(), true);
        
        $newEvent = $serializer->deserialize($request->getContent(), Event::class, 'json');
        foreach($data['participantsIds'] as $participantId) {
            $participant = $userRepository->find($participantId);
            if ($participant) {
                $newEvent->addParticipant($participant);
            }
        }
        $newEvent->setAllDay($data['isAllDay']);
        $newEvent->setOrganizer($this->getUser());
        $manager->persist($newEvent);
        $manager->flush();
        
        return $this->json($newEvent, Response::HTTP_CREATED, [], ['groups' => ['event:read']]);
    }
    
    #[Route('/remove/{id}', name: 'app_event_remove',methods: ['DELETE'])]
    public function removeEvent(?Event $event, EntityManagerInterface $manager):Response
    {
        if (!$event) {
            return $this->json(['error' => 'Event not found'], Response::HTTP_NOT_FOUND);
        }
                
        if ($event->getOrganizer() !== $this->getUser()) {
            return $this->json(['error' => 'You are not authorized to remove this event'], Response::HTTP_FORBIDDEN);
        }
        
        $manager->remove($event);
        $manager->flush();
        
        return $this->json('Successfully removed event', Response::HTTP_OK);
    }
}

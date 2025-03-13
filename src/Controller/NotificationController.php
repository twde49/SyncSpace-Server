<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Repository\NotificationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/notifications')]
class NotificationController extends AbstractController
{
    #[Route('/all', name: 'app_notification_index', methods: ['GET'])]
    public function index(NotificationRepository $notificationRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $notifications = $notificationRepository->getUnreadNotifications($user);

        return $this->json($notifications, Response::HTTP_OK, [], ['groups' => ['notification:read']]);
    }

    #[Route('/new', name: 'app_notification_create', methods: ['POST'])]
    public function createNotification(Request $request, UserRepository $userRepository, EntityManagerInterface $manager): Response
    {
        $data = json_decode($request->getContent(), true);

        $notification = new Notification();
        $notification->setTitle($data['title']);
        $notification->setContent($data['content']);
        $relatedToUser = $userRepository->find($data['userId']);
        $notification->setRelatedTo($relatedToUser);
        $manager->persist($notification);
        $manager->flush();

        return $this->json($notification, Response::HTTP_CREATED, [], ['groups' => ['notification:read']]);
    }

    #[Route('/{id}/read', name: 'app_notification_read', methods: ['PUT'])]
    public function readNotification(Notification $notification, EntityManagerInterface $manager): Response
    {
        $notification->setRead(true);
        $manager->flush();

        return $this->json($notification, Response::HTTP_OK, [], ['groups' => ['notification:read']]);
    }

    #[Route('/readAll', name: 'app_notification_read_all', methods: ['PUT'])]
    public function readAllNotifications(EntityManagerInterface $manager, NotificationRepository $notificationRepository): Response
    {
        /**
         * @var User $user
         */
        $user = $this->getUser();
        $notifications = $notificationRepository->findBy(['relatedTo' => $user]);
        foreach ($notifications as $notification) {
            $notification->setRead(true);
        }
        $manager->flush();

        return $this->json([], Response::HTTP_OK, [], ['groups' => ['notification:read']]);
    }
}

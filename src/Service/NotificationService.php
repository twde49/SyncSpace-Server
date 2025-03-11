<?php
namespace App\Service;
use App\Entity\Notification;
use App\Entity\User;
use GuzzleHttp\Client;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService {

    private $params;
    private $normalizer;
    private $notificationRepository;
    private $manager;

    public function __construct(ParameterBagInterface $params, NormalizerInterface $normalizer, NotificationRepository $notificationRepository, EntityManagerInterface $manager)
    {
        $this->params = $params;
        $this->normalizer = $normalizer;
        $this->notificationRepository = $notificationRepository;
        $this->manager = $manager;
    }

    public function sendNotification(string $title, string $content, User $toUser): void
    {
        $notification = new Notification();
        $notification->setTitle($title);
        $notification->setContent($content);
        $notification->setRelatedTo($toUser);
        $this->manager->persist($notification);
        $this->manager->flush();
        
        $client = new Client();
        $context = ['groups' => 'notification:read'];
        $normalizedNotification = $this->normalizer->normalize($notification, null, $context);

        $websocketbaseurl = $this->params->get('websocket_url');
        $client->post($websocketbaseurl . "/webhook/send-notification", [
            'json' => ['notification' => $normalizedNotification, 'user_email' => $toUser->getEmail()]
        ]);
        
    }
}
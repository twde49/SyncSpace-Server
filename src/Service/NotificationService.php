<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class NotificationService
{
    /**
     * Constructor for NotificationService.
     *
     * @param ParameterBagInterface  $params                 The parameter bag interface
     * @param NormalizerInterface    $normalizer             The normalizer interface
     * @param NotificationRepository $notificationRepository The notification repository
     * @param EntityManagerInterface $manager                The entity manager
     */
    public function __construct(private readonly ParameterBagInterface $params, private readonly NormalizerInterface $normalizer, private readonly NotificationRepository $notificationRepository, private readonly EntityManagerInterface $manager)
    {
    }

    /**
     * Sends a notification to a user.
     *
     * @param string $title   The title of the notification
     * @param string $content The content of the notification
     * @param User   $toUser  The user to send the notification to
     */
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
        $client->post($websocketbaseurl.'/webhook/send-notification', [
            'json' => ['notification' => $normalizedNotification, 'user_email' => $toUser->getEmail()],
        ]);
    }
}

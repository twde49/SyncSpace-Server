<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;

class AuthenticationSuccessListener
{
    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $data = $event->getData();
        $user = $event->getUser();

        if (!$user instanceof User) {
            return;
        }

        if (!$user->isValidated()) {
            $data['user'] = [
                'userId' => $user->getId(),
                'message' => 'User not validated',
            ];
            $event->setData($data);

            return;
        }

        $data['user'] = [
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'userEmail' => $user->getEmail(),
            'parameters' => [
                'theme' => $user->getUserSettings()->getTheme(),
                'modulesLayout' => $user->getUserSettings()->getModulesLayout(),
                'notificationsEnabled' => $user->getUserSettings()->isNotificationsEnabled(),
                'geolocationEnabled' => $user->getUserSettings()->isGeolocationEnabled(),
            ],
        ];

        $event->setData($data);
    }
}

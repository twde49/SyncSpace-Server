<?php

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

        $data['user'] = [
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'userEmail' => $user->getEmail(),
        ];

        $event->setData($data);
    }
}

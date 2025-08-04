<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class MailerService
{
    /**
     * @return TemplatedEmail
     */
    public function sendVerificationMail(User $user)
    {
        return (new TemplatedEmail())
        ->from('no-reply@thibautstachnick.com')
        ->to($user->getEmail())
        ->subject('Vérification de votre compte SyncSpace')
        ->htmlTemplate('emails/validationCode.html.twig')
        ->context([
            'code' => $user->getVerificationCode(),
        ]);
    }
}
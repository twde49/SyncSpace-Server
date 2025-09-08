<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class MailerService
{
    /**
     * Sends a verification email to the specified user.
     *
     * @param User $user The user entity to send the verification email to
     *
     * @return TemplatedEmail The templated email object
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

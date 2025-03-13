<?php

namespace App\Service;

use App\Entity\Conversation;

class ConversationService
{
    public function setLatestActiveUser(Conversation $conversation): Conversation
    {
        $latestMessage = null;
        foreach ($conversation->getMessages() as $message) {
            if (null === $latestMessage || $message->getSentAt() > $latestMessage->getSentAt()) {
                $latestMessage = $message;
            }
        }
        if (null !== $latestMessage) {
            return $conversation->setLastActiveUser($latestMessage->getSender());
        }

        return $conversation->setLastActiveUser(null);
    }

    public function setLatestMessage(Conversation $conversation): void
    {
        $messages = $conversation->getMessages();
        $lastMessage = null;
        foreach ($messages as $message) {
            if (null === $lastMessage || $message->getSentAt() > $lastMessage->getSentAt()) {
                $lastMessage = $message;
            }
        }
        if (null !== $lastMessage) {
            $conversation->setLastMessage($lastMessage);
        }
    }
}

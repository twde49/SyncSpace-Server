<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Conversation;

class ConversationService
{
    /**
     * Sets the latest active user for a conversation based on the most recent message.
     *
     * @param Conversation $conversation The conversation entity
     *
     * @return Conversation The updated conversation entity
     */
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

    /**
     * Sets the latest message for a conversation.
     *
     * @param Conversation $conversation The conversation entity
     */
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

<?php

namespace App\Notification;

use App\Entity\Comment;
use Symfony\Component\Notifier\Message\EmailMessage;
use Symfony\Component\Notifier\Notification\EmailNotificationInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\EmailRecipientInterface;

class CommentPostedNotification extends Notification implements EmailNotificationInterface
{
    public function __construct(
        private Comment $comment,
        private string $conferenceSlug,
    ) {
        parent::__construct('Your comment posted!');
    }

    public function asEmailMessage(EmailRecipientInterface $recipient, string $transport = null): ?EmailMessage
    {
        $message = EmailMessage::fromNotification($this, $recipient);
        $message->transport($transport);

        $message->getMessage()
            ->htmlTemplate('emails/comment_posted_notification.html.twig')
            ->context([
                'conferenceSlug' => $this->conferenceSlug,
                'comment' => [
                    'author' => $this->comment->getAuthor(),
                    'email' => $this->comment->getEmail(),
                    'text' => $this->comment->getText(),
                ],
            ]);

        return $message;
    }
}

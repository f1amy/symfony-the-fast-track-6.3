<?php

namespace App\Notification;

use App\Entity\Comment;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\EmailMessage;
use Symfony\Component\Notifier\Notification\ChatNotificationInterface;
use Symfony\Component\Notifier\Notification\EmailNotificationInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\EmailRecipientInterface;
use Symfony\Component\Notifier\Recipient\RecipientInterface;

class CommentReviewNotification extends Notification implements EmailNotificationInterface, ChatNotificationInterface
{
    public function __construct(
        private Comment $comment,
        private string $reviewUrl,
    ) {
        parent::__construct('New comment posted');
    }

    public function asEmailMessage(EmailRecipientInterface $recipient, string $transport = null): ?EmailMessage
    {
        $message = EmailMessage::fromNotification($this, $recipient);
        $message->transport($transport);

        $message->getMessage()
            ->htmlTemplate('emails/comment_notification.html.twig')
            ->context([
                'comment' => [
                    'id' => $this->comment->getId(),
                    'author' => $this->comment->getAuthor(),
                    'email' => $this->comment->getEmail(),
                    'text' => $this->comment->getText(),
                    'state' => $this->comment->getStateMarking(),
                ],
            ]);

        return $message;
    }

    public function asChatMessage(RecipientInterface $recipient, string $transport = null): ?ChatMessage
    {
        if ('fakechat' !== $transport) {
            return null;
        }

        $message = ChatMessage::fromNotification($this);
        $message->transport($transport);

        $subject = $this->composeChatSubject();
        $message->subject($subject);

        return $message;
    }

    public function getChannels(RecipientInterface $recipient): array
    {
        if ($this->isPositiveComment()) {
            return ['email', 'chat/fakechat'];
        }

        $this->importance(Notification::IMPORTANCE_LOW);

        return ['email'];
    }

    private function isPositiveComment(): int|false
    {
        return preg_match('{\b(great|awesome)\b}i', $this->comment->getText());
    }

    private function composeChatSubject(): string
    {
        $rejectUrl = sprintf('%s?reject=1', $this->reviewUrl);

        return sprintf(
            '%s, Accept: %s, Reject: %s',
            $this->getSubject(),
            $this->reviewUrl,
            $rejectUrl
        );
    }
}

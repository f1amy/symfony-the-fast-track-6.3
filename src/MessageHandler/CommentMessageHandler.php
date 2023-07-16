<?php

namespace App\MessageHandler;

use App\Entity\Comment;
use App\Message\CommentMessage;
use App\Repository\CommentRepository;
use App\Service\SpamChecker;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\WorkflowInterface;

#[AsMessageHandler]
class CommentMessageHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SpamChecker $spamChecker,
        private CommentRepository $commentRepository,
        private MessageBusInterface $bus,
        private WorkflowInterface $commentStateMachine,
        private MailerInterface $mailer,
        #[Autowire('%admin_email%')] private string $adminEmail,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(CommentMessage $message): void
    {
        $comment = $this->commentRepository->find($message->getId());
        if (!$comment) {
            // comment does not exist
            return;
        }

        if ($this->commentStateMachine->can($comment, 'accept')) {
            $this->checkCommentForSpam($comment, $message);
        } elseif (
            $this->commentStateMachine->can($comment, 'publish')
            || $this->commentStateMachine->can($comment, 'publish_ham')
        ) {
            $this->sendReviewNotification($comment);
        } else {
            $this->logger->debug(
                'Dropping comment message',
                [
                    'comment' => $comment->getId(),
                    'state' => $comment->getState(),
                ]
            );
        }
    }

    private function checkCommentForSpam(Comment $comment, CommentMessage $message): void
    {
        $score = $this->spamChecker->getSpamScore($comment, $message->getContext());

        $this->logger->info('Comment Spam score', [
            'commentId' => $comment->getId(),
            'spamScore' => $score,
        ]);

        $transition = match ($score) {
            2 => 'reject_spam',
            1 => 'might_be_spam',
            default => 'accept',
        };

        $this->commentStateMachine->apply($comment, $transition);
        $this->entityManager->flush();

        $this->bus->dispatch($message);
    }

    /**
     * @throws TransportExceptionInterface
     */
    private function sendReviewNotification(Comment $comment): void
    {
        $notification = new NotificationEmail();

        $notification->subject('New comment posted')
            ->htmlTemplate('emails/comment_notification.html.twig')
            ->to($this->adminEmail)
            ->context([
                'comment' => [
                    'id' => $comment->getId(),
                    'author' => $comment->getAuthor(),
                    'email' => $comment->getEmail(),
                    'text' => $comment->getText(),
                    'state' => $comment->getStateMarking(),
                ],
            ]);

        $this->mailer->send($notification);
    }
}

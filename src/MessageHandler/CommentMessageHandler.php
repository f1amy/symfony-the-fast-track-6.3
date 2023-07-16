<?php

namespace App\MessageHandler;

use App\Entity\Comment;
use App\Message\CommentMessage;
use App\Notification\CommentReviewNotification;
use App\Repository\CommentRepository;
use App\Service\ImageOptimizer;
use App\Service\SpamChecker;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Notifier\NotifierInterface;
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
        private NotifierInterface $notifier,
        private ImageOptimizer $imageOptimizer,
        #[Autowire('%app.photo_dir%')] private string $photoDir,
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
        } elseif ($this->commentStateMachine->can($comment, 'optimize')) {
            $this->resizeCommentImage($comment);
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

    private function sendReviewNotification(Comment $comment): void
    {
        $notification = new CommentReviewNotification($comment);
        $reviewers = $this->notifier->getAdminRecipients();

        $this->notifier->send($notification, ...$reviewers);
    }

    /**
     * @param Comment $comment
     * @return void
     */
    private function resizeCommentImage(Comment $comment): void
    {
        if ($comment->getPhotoFilename()) {
            $filename = sprintf('%s/%s', $this->photoDir, $comment->getPhotoFilename());
            $this->imageOptimizer->resize($filename);
        }

        $this->commentStateMachine->apply($comment, 'optimize');
        $this->entityManager->flush();
    }
}

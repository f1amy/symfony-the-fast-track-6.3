<?php

namespace App\MessageHandler;

use App\Enum\PublishState;
use App\Message\CommentMessage;
use App\Repository\CommentRepository;
use App\Service\SpamChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CommentMessageHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SpamChecker $spamChecker,
        private CommentRepository $commentRepository,
    ) {
    }

    public function __invoke(CommentMessage $message): void
    {
        $comment = $this->commentRepository->find($message->getId());
        if (!$comment) {
            // comment does not exist
            return;
        }

        $spamScore = $this->spamChecker->getSpamScore($comment, $message->getContext());
        if (2 === $spamScore) {
            $comment->setState(PublishState::Spam);
        } else {
            $comment->setState(PublishState::Published);
        }

        $this->entityManager->flush();
    }
}

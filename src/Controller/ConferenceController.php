<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Conference;
use App\Form\CommentType;
use App\Message\CommentMessage;
use App\Repository\CommentRepository;
use App\Repository\ConferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ConferenceController extends AbstractController
{
    private const HOMEPAGE_CACHE_IN_SECONDS = 3600;
    private const CONFERENCE_HEADER_CACHE_IN_SECONDS = 3600;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $bus,
    ) {
    }

    #[Route('/', name: 'homepage')]
    public function index(ConferenceRepository $conferenceRepository): Response
    {
        $response = $this->render('conference/index.html.twig', [
            'conferences' => $conferenceRepository->findAll(),
        ]);

        return $response->setSharedMaxAge(self::HOMEPAGE_CACHE_IN_SECONDS);
    }

    #[Route('/conference_header', name: 'conference_header')]
    public function conferenceHeader(ConferenceRepository $conferenceRepository): Response
    {
        $response = $this->render('conference/header.html.twig', [
            'conferences' => $conferenceRepository->findAll(),
        ]);

        return $response->setSharedMaxAge(self::CONFERENCE_HEADER_CACHE_IN_SECONDS);
    }

    #[Route('/conference/{slug}', name: 'conference')]
    public function show(
        Request $request,
        Conference $conference,
        CommentRepository $commentRepository,
        NotifierInterface $notifier,
        #[Autowire('%app.photo_dir%')] string $photoDir,
    ): Response {
        $comment = new Comment();
        $commentForm = $this->createForm(CommentType::class, $comment);

        $commentForm->handleRequest($request);
        if ($commentForm->isSubmitted() && $commentForm->isValid()) {
            $comment->setConference($conference);

            if ($photo = $commentForm['photo']->getData()) {
                $randomString = bin2hex(random_bytes(6));
                $filename = sprintf('%s.%s', $randomString, $photo->guessExtension());

                $photo->move($photoDir, $filename);
                $comment->setPhotoFilename($filename);
            }

            $this->entityManager->persist($comment);
            $this->entityManager->flush();

            $context = [
                'user_ip' => $request->getClientIp(),
                'user_agent' => $request->headers->get('user-agent'),
                'referrer' => $request->headers->get('referer'),
                'permalink' => $request->getUri(),
            ];
            $reviewUrl = $this->generateUrl(
                'admin_review_comment',
                ['id' => $comment->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
            $commentMessage = new CommentMessage($comment->getId(), $reviewUrl, $context);
            $this->bus->dispatch($commentMessage);

            $feedbackNotification = new Notification(
                'Thank you for the feedback; your comment will be posted after moderation.',
                ['browser']
            );
            $notifier->send($feedbackNotification);

            return $this->redirectToRoute('conference', ['slug' => $conference->getSlug()]);
        }

        if ($commentForm->isSubmitted()) {
            $submissionErrorNotification = new Notification(
                'Can you check your submission? There are some problems with it.',
                ['browser']
            );

            $notifier->send($submissionErrorNotification);
        }

        $offset = max(0, $request->query->getInt('offset', 0));
        $commentPaginator = $commentRepository->getCommentPaginator($conference, $offset);

        $previous = $offset - CommentRepository::PAGINATOR_PER_PAGE;
        $next = min(count($commentPaginator), $offset + CommentRepository::PAGINATOR_PER_PAGE);

        return $this->render('conference/show.html.twig', [
            'conference' => $conference,
            'comments' => $commentPaginator,
            'previous' => $previous,
            'next' => $next,
            'commentForm' => $commentForm,
        ]);
    }
}

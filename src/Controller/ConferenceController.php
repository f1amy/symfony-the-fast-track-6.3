<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Conference;
use App\Form\CommentType;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ConferenceController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/', name: 'homepage')]
    public function index(): Response
    {
        return $this->render('conference/index.html.twig');
    }

    #[Route('/conference/{slug}', name: 'conference')]
    public function show(
        Request $request,
        Conference $conference,
        CommentRepository $commentRepository,
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

            return $this->redirectToRoute('conference', ['slug' => $conference->getSlug()]);
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

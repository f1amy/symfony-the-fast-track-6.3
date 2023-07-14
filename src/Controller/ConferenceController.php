<?php

namespace App\Controller;

use App\Entity\Conference;
use App\Repository\CommentRepository;
use App\Repository\ConferenceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ConferenceController extends AbstractController
{
    #[Route('/', name: 'homepage')]
    public function index(ConferenceRepository $conferenceRepository): Response
    {
        $conferences = $conferenceRepository->findAll();

        return $this->render('conference/index.html.twig', [
            'conferences' => $conferences,
        ]);
    }

    #[Route('/conference/{id}', name: 'conference')]
    public function show(Conference $conference, CommentRepository $commentRepository): Response
    {
        $comments = $commentRepository->findBy(['conference' => $conference], ['createdAt' => 'DESC']);

        return $this->render('conference/show.html.twig', [
            'conference' => $conference,
            'comments' => $comments,
        ]);
    }
}

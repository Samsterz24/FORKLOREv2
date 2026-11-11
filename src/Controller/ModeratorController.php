<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/moderator')]
#[IsGranted('ROLE_MODERATOR')]
final class ModeratorController extends AbstractController
{
    #[Route('', name: 'moderator_dashboard', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('moderator/index.html.twig');
    }
}
<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ErrorPageController extends AbstractController
{
    #[Route('/oops', name: 'app_oops', methods: ['GET'])]
    public function oops(): Response
    {
        return $this->render('error/oops.html.twig', [], new Response('', Response::HTTP_FORBIDDEN));
    }
}
